<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kurs 1 USD = x VND melekat pada setiap transaksi impor (embedded).
 *
 * Diinput saat create (prefill dari referensi publik via endpoint
 * currency-rates, boleh diubah sesuai kontrak). Dipakai analisa trend
 * harga kontrak lintas valuta: kurs transaksi sendiri menang atas
 * referensi per tanggal; NULL untuk transaksi lama — trend jatuh ke
 * tabel currency_rates (manual/publik per tanggal).
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (!Schema::hasColumn('import_purchases', 'usd_vnd_rate')) {
            Schema::table('import_purchases', function (Blueprint $table) {
                $table->decimal('usd_vnd_rate', 14, 4)->nullable()
                    ->after('exchange_rate');
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (Schema::hasColumn('import_purchases', 'usd_vnd_rate')) {
            Schema::table('import_purchases', function (Blueprint $table) {
                $table->dropColumn('usd_vnd_rate');
            });
        }
    }
};
