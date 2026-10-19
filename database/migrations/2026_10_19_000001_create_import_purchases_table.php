<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Header proses impor barang (mis. mete dari luar negeri).
 *
 * Satu baris = satu proses impor yang berjalan melalui tahapan fixed:
 * draft → payment → shipment → customs → arrival → receiving → finalized
 * (+ cancelled). Biaya & dokumen menempel di tabel import_costs /
 * import_documents; barang di import_items.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (!Schema::hasTable('import_purchases')) {
            Schema::create('import_purchases', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('supplier_id')->index('import_purchases_supplier_id_foreign');
                // Toko/gudang tujuan (opsional — impor bisa bersifat pusat).
                $table->unsignedBigInteger('store_id')->nullable()->index('import_purchases_store_id_foreign');
                // Mata uang pembelian barang ke supplier (mis. USD).
                $table->string('currency', 3)->default('USD');
                // Kurs valuta → Rupiah saat proses impor (mis. 17545).
                $table->decimal('exchange_rate', 12, 2);
                $table->enum('stage', [
                    'draft',
                    'payment',
                    'shipment',
                    'customs',
                    'arrival',
                    'receiving',
                    'finalized',
                    'cancelled',
                ])->default('draft');
                $table->text('notes')->nullable();
                // Snapshot hasil perhitungan ImportHppService.
                $table->decimal('total_estimate', 20, 2)->default(0);
                $table->decimal('total_real', 20, 2)->default(0);
                // Pembuat (user di DB auth — tanpa FK, pola last_order_follow_ups).
                $table->unsignedBigInteger('created_by_id')->index();
                $table->timestamps();

                $table->foreign('supplier_id')->references('id')->on('suppliers')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('store_id')->references('id')->on('stores')->onUpdate('cascade')->onDelete('cascade');
                $table->index(['stage', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('import_purchases', function (Blueprint $table) {
            $table->dropForeign('import_purchases_supplier_id_foreign');
            $table->dropForeign('import_purchases_store_id_foreign');
        });
        Schema::dropIfExists('import_purchases');
    }
};
