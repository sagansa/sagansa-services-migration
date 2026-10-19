<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya-biaya proses impor selain nilai barang: bank (transfer/fee), pajak
 * (PPh impor), logistik pelabuhan (handling Koja, D/O, unloading, storage,
 * PNBP, lift-off, trucking, repair), dan lain-lain — replika baris biaya pada
 * sheet HPP METE dengan kolom estimate vs real.
 *
 * Kategori menentukan perlakuan perhitungan:
 * - tax + tax_rate  : otomatis tax_rate% × total nilai barang (mis. PPh 2,5%/11%)
 * - bank            : dikeluarkan dari metrik hpp_pajak
 * - is_foreign_currency + amount_foreign : dikonversi × kurs (mis. transfer $25)
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (!Schema::hasTable('import_costs')) {
            Schema::create('import_costs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('import_purchase_id')->constrained('import_purchases')->cascadeOnUpdate()->cascadeOnDelete();
                // Tahapan biaya ini melekat (purchase/payment/shipment/customs/arrival).
                $table->enum('stage', ['purchase', 'payment', 'shipment', 'customs', 'arrival']);
                $table->string('name');
                $table->enum('category', ['goods', 'bank', 'tax', 'logistics', 'other'])->default('other');
                // Persen (2.5 / 11) → auto-hitung dari nilai barang; null = amount manual.
                $table->decimal('tax_rate', 6, 3)->nullable();
                $table->boolean('is_foreign_currency')->default(false);
                $table->decimal('amount_foreign', 16, 2)->nullable();
                $table->decimal('amount_estimate', 20, 2)->default(0);
                // Null → fallback ke hasil estimate saat dihitung.
                $table->decimal('amount_real', 20, 2)->nullable();
                $table->timestamps();

                $table->index(['import_purchase_id', 'category']);
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        Schema::dropIfExists('import_costs');
    }
};
