<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor invoice supplier & tanggal transaksi impor.
 *
 * Sebelumnya hanya `notes` bebas yang tersedia — nomor invoice adalah
 * identitas dokumen yang dipakai staf saat mencocokkan pembayaran, jadi
 * disimpan sebagai kolom sendiri. `purchase_date` memisahkan tanggal
 * transaksi dari `created_at` (kapan record dibuat).
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::table('import_purchases', function (Blueprint $table) {
            $table->string('invoice_number', 100)->nullable()->after('currency');
            $table->date('purchase_date')->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('import_purchases', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'purchase_date']);
        });
    }
};
