<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `signers` + kolom `signature_asset` LONGTEXT NULL (DB `sagansa_document`).
 *
 * Aset tanda tangan penandatangan (fase 1c, PRD-SIGNER-ADMIN §3 #2): PNG
 * di-encode base64 TANPA prefiks data-URI (`data:image/png;base64,` dipotong
 * sebelum simpan) — konsumen menambahkan prefiks sendiri saat menyajikan.
 *
 * Disimpan di DB (bukan disk path) karena `sagansa_document` memang sudah
 * dibaca kedua pihak (apps/admin untuk upload, services/document untuk
 * render Mode A + endpoint aset) — tanpa disk bersama dan tanpa call
 * service-to-service; PNG tanda tangan ~50-300KB aman di LONGTEXT.
 * `signature_asset_path` lama dibiarkan (deprecated, tak dibaca lagi).
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_document');

        if (! $schema->hasTable('signers') || $schema->hasColumn('signers', 'signature_asset')) {
            return;
        }

        $afterPath = $schema->hasColumn('signers', 'signature_asset_path');

        $schema->table('signers', function (Blueprint $table) use ($afterPath) {
            $column = $table->longText('signature_asset')->nullable();

            if ($afterPath) {
                $column->after('signature_asset_path');
            }
        });
    }

    /**
     * Balikkan: hapus kolom aset (guard kolom agar idempoten).
     */
    public function down(): void
    {
        $schema = Schema::connection('mysql_document');

        if (! $schema->hasTable('signers') || ! $schema->hasColumn('signers', 'signature_asset')) {
            return;
        }

        $schema->table('signers', function (Blueprint $table) {
            $table->dropColumn('signature_asset');
        });
    }
};
