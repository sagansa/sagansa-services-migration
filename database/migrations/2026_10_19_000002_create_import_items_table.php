<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Barang yang diimpor per proses. Grade berbeda (BB2/BB3/BBB/LP2/LP3/Bubuk)
 * dan kemasan berbeda (1kg/10kg/100kg/1 ton) adalah baris produk berbeda di
 * master — skenario yield per kemasan pada sheet HPP tertangani sebagai item
 * terpisah dengan quantity_received masing-masing.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (!Schema::hasTable('import_items')) {
            Schema::create('import_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('import_purchase_id')->constrained('import_purchases')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
                // Jumlah dibeli (satuan mengikuti unit produk, umumnya kg).
                $table->decimal('quantity', 16, 3);
                // Harga satuan dalam valuta asing (mis. 0.78 USD/kg).
                $table->decimal('unit_price_foreign', 16, 4);
                // Jumlah benar-benar diterima setelah sortir/susut — diisi di
                // tahap receiving; dasar pembagi HPP realisasi.
                $table->decimal('quantity_received', 16, 3)->nullable();
                // Hasil alokasi biaya per unit saat finalize (snapshot).
                $table->decimal('hpp_estimate', 20, 2)->nullable();
                $table->decimal('hpp_real', 20, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        Schema::dropIfExists('import_items');
    }
};
