<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FK master domain surat: cascade → RESTRICT (DB `sagansa_document`).
 *
 * `documents.letter_type_id`, `templates.letter_type_id`,
 * `signing_requests.signer_id` semula cascadeOnDelete — baris master
 * (jenis surat / signer) yang tak sengaja terhapus ikut menghapus surat
 * terbit beserta riwayatnya, melanggar mandat arsip 10 tahun (tidak ada
 * hard delete dokumen). RESTRICT menolak penghapusan master yang masih
 * dipakai; master baru dihapus setelah tidak direferensikan.
 *
 * FK `*.document_id` (cascade) TIDAK disentuh: penghapusan dokumen
 * memang cascade internal (lampiran/issuance/signing request ikut),
 * dan tidak ada route delete dokumen — arah itu sudah benar.
 */
return new class extends Migration
{
    /**
     * @var list<array{table: string, column: string, references: string}>
     */
    private const RESTRICT_FOREIGNS = [
        ['table' => 'documents', 'column' => 'letter_type_id', 'references' => 'letter_types'],
        ['table' => 'templates', 'column' => 'letter_type_id', 'references' => 'letter_types'],
        ['table' => 'signing_requests', 'column' => 'signer_id', 'references' => 'signers'],
    ];

    public function up(): void
    {
        $this->rebuild(onDelete: 'restrict');
    }

    /**
     * Downgrade: kembalikan cascade (perilaku pra-migration ini).
     */
    public function down(): void
    {
        $this->rebuild(onDelete: 'cascade');
    }

    /**
     * Drop FK lama lalu add ulang dengan aturan ON DELETE pilihan.
     * Idempoten-guard: tabel/FK tidak ada → skip diam (pola guard
     * migration domain ini).
     */
    private function rebuild(string $onDelete): void
    {
        $schema = Schema::connection('mysql_document');

        foreach (self::RESTRICT_FOREIGNS as $fk) {
            if (! $schema->hasTable($fk['table']) || ! $this->foreignKeyExists($fk['table'], $fk['column'])) {
                continue;
            }

            $schema->table($fk['table'], function (Blueprint $table) use ($fk, $onDelete) {
                $table->dropForeign([$fk['column']]);

                $foreign = $table->foreign($fk['column'])
                    ->references('id')
                    ->on($fk['references'])
                    ->cascadeOnUpdate();

                $onDelete === 'restrict' ? $foreign->restrictOnDelete() : $foreign->cascadeOnDelete();
            });
        }
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        $connection = DB::connection('mysql_document');

        return (bool) $connection->table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $connection->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }
};
