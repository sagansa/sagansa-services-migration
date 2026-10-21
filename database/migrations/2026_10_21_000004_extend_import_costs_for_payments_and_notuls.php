<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda asal baris biaya:
 * - payment : baris derived dari import_payments (net bank/diskon).
 * - notul   : baris derived dari notul (PPh/PPN/denda/biaya lain).
 * - null    : biaya manual lama / non-pembayaran.
 *
 * Kategori discount ditambahkan agar diskon mengurangi total, bukan menambah.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('import_costs', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->after('category')->index();
        });

        DB::statement(
            'ALTER TABLE import_costs MODIFY category '
            ."ENUM('goods','bank','tax','logistics','other','discount') "
            ."NOT NULL DEFAULT 'other'"
        );
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        DB::statement("UPDATE import_costs SET category = 'other' WHERE category = 'discount'");
        DB::statement(
            'ALTER TABLE import_costs MODIFY category '
            ."ENUM('goods','bank','tax','logistics','other') "
            ."NOT NULL DEFAULT 'other'"
        );

        Schema::table('import_costs', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
