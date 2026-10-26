<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `documents.number` NOT NULL → nullable (DB `sagansa_document`).
 *
 * Draft Mode A (Task 13) lahir TANPA nomor — nomor baru di-reserve saat
 * finalize / endpoint numbering/reserve. Dengan NOT NULL, pra-nomor
 * direpresentasikan string kosong; padahal kolom ini UNIQUE sehingga
 * HANYA SATU draft tanpa nomor bisa ada se-system (insert kedua gagal
 * duplicate key → HTTP 500). MySQL & SQLite mengizinkan banyak NULL
 * di bawah indeks unique, maka pra-nomor kini direpresentasikan NULL
 * (kode service sudah menganggap null ATAU '' sebagai "belum ada nomor").
 *
 * `change()` natif Laravel memakai ALTER ... MODIFY yang mempertahankan
 * indeks unique yang ada (diverifikasi via information_schema setelah
 * migrate).
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_document');

        if (! $schema->hasTable('documents')) {
            return;
        }

        $schema->table('documents', function (Blueprint $table) {
            $table->string('number')->nullable()->change();
        });
    }

    /**
     * Kembalikan ke NOT NULL.
     *
     * Baris number IS NULL tidak bisa diboost ke '' secara aman: kolom
     * UNIQUE, dan satu baris '' mungkin sudah ada → duplicate key; mengisi
     * akhiran unik per baris berarti MENGARANG nomor surat (data palsu).
     * Maka downgrade MENOLAK (throw) selama masih ada baris NULL —
     * operator harus menyelesaikan (reserve/finalize) atau menghapus
     * draft-draft itu dulu secara sadar.
     */
    public function down(): void
    {
        $schema = Schema::connection('mysql_document');

        if (! $schema->hasTable('documents')) {
            return;
        }

        $nullCount = DB::connection('mysql_document')
            ->table('documents')
            ->whereNull('number')
            ->count();

        if ($nullCount > 0) {
            throw new \RuntimeException(
                "Tidak bisa downgrade: {$nullCount} baris documents.number masih NULL. ".
                'Selesaikan (reserve/finalize) atau hapus draft tersebut terlebih dahulu.',
            );
        }

        $schema->table('documents', function (Blueprint $table) {
            $table->string('number')->nullable(false)->change();
        });
    }
};
