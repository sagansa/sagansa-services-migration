<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kurs referensi antar valuta per tanggal (mis. 1 USD = x VND).
 *
 * Satu baris = satu kurs untuk pasangan valuta pada tanggal tertentu.
 * Sumber `public` = hasil ambil referensi publik yang di-cache server
 * (rate = 0 menandai "pernah dicek, tidak tersedia" agar tidak diambil
 * ulang); sumber `manual` = input admin (menimpa publik untuk tanggal
 * itu). Dipakai analisa trend harga kontrak impor lintas valuta —
 * tersimpan terpusat agar konsisten di semua perangkat.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (!Schema::hasTable('currency_rates')) {
            Schema::create('currency_rates', function (Blueprint $table) {
                $table->bigIncrements('id');
                // Pasangan kurs: rate = 1 base = x quote (mis. USD→VND).
                $table->string('base_currency', 3);
                $table->string('quote_currency', 3);
                $table->date('rate_date');
                $table->decimal('rate', 14, 4);
                $table->enum('source', ['public', 'manual'])->default('public');
                // Pembuat baris manual (user DB auth — tanpa FK, pola
                // import_purchases.created_by_id).
                $table->unsignedBigInteger('created_by_id')->nullable()->index();
                $table->timestamps();

                $table->unique(
                    ['base_currency', 'quote_currency', 'rate_date'],
                    'currency_rates_pair_date_unique',
                );
                $table->index(['base_currency', 'quote_currency', 'source']);
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::dropIfExists('currency_rates');
    }
};
