<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplier luar negeri: negara (ISO 3166-1 alpha-2, null/ID = domestik),
 * kota bebas (wilayah Indonesia tidak berlaku), valuta transaksi default
 * untuk prefill proses impor, serta bank luar negeri (nama bebas + SWIFT)
 * karena bank_id hanya memuat bank Indonesia.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('no_telp');
            $table->string('city_name', 100)->nullable()->after('country_code');
            $table->string('currency', 3)->nullable()->after('city_name');
            $table->string('bank_name', 100)->nullable()->after('bank_account_no');
            $table->string('bank_swift_code', 11)->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'country_code',
                'city_name',
                'currency',
                'bank_name',
                'bank_swift_code',
            ]);
        });
    }
};
