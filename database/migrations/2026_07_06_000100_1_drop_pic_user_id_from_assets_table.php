<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Hapus kolom pic_user_id dari tabel assets. Akses & notifikasi pemeriksaan
 * aset berbasis role+store (admin/storage-staff di store terkait), bukan
 * assignment individual per user, sehingga kolom PIC tidak diperlukan.
 *
 * Catatan: assets yang sudah ada mungkin masih punya nilai pic_user_id —
 * nilai tersebut akan hilang (kolom di-drop). Tidak ada migrasi data karena
 * konsep PIC itu sendiri dihapus.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'pic_user_id')) {
            Schema::table('assets', function (Blueprint $table) {
                // Cukup drop kolom — MySQL otomatis menghapus index yang
                // melekat padanya. Index bernama assets_pic_user_id_index
                // tidak selalu ada pada instalasi yang dibuat fresh dari
                // rantai migration (mis. sagansa_test), sehingga dropIndex
                // eksplisit bisa gagal (errno 1091).
                $table->dropColumn('pic_user_id');
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (Schema::hasTable('assets') && !Schema::hasColumn('assets', 'pic_user_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->unsignedBigInteger('pic_user_id')
                    ->nullable()
                    ->index('assets_pic_user_id_index')
                    ->after('store_id');
            });
        }
    }
};
