<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran ke supplier / bank per tranche.
 *
 * Satu baris = satu debit bank yang dipakai staf sebagai angka realisasi.
 * Nominal ini sudah mencakup nilai barang + biaya bank − diskon, sehingga
 * rincian Transfer Bank/Bank/Discounts tidak perlu diisi ulang saat realisasi.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (! Schema::hasTable('import_payments')) {
            Schema::create('import_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('import_purchase_id')
                    ->constrained('import_purchases')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                // Total debit bank pada tranche ini (bisa 2x pembayaran).
                $table->decimal('amount', 20, 2);
                $table->date('paid_at');
                $table->text('note')->nullable();
                // Bukti pembayaran opsional per tranche (img service path).
                $table->string('proof_path', 500)->nullable();
                $table->string('proof_original_name', 255)->nullable();
                $table->string('proof_mime_type', 100)->nullable();
                $table->unsignedInteger('proof_size_bytes')->nullable();
                $table->timestamps();

                $table->index(['import_purchase_id', 'paid_at']);
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        Schema::dropIfExists('import_payments');
    }
};
