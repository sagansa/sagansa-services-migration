<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga jual per produk yang ditetapkan saat finalisasi impor (input manual
 * atas HPP realisasi). Snapshot per item + harga jual terbaru di master
 * produk — sumber prefill harga saat membuat sales order online.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('import_items', function (Blueprint $table) {
            if (!Schema::hasColumn('import_items', 'selling_price')) {
                $table->decimal('selling_price', 20, 2)->nullable()
                    ->after('hpp_real');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'selling_price')) {
                $table->decimal('selling_price', 20, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'selling_price_updated_at')) {
                $table->timestamp('selling_price_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('import_items', function (Blueprint $table) {
            if (Schema::hasColumn('import_items', 'selling_price')) {
                $table->dropColumn('selling_price');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'selling_price')) {
                $table->dropColumn('selling_price');
            }
            if (Schema::hasColumn('products', 'selling_price_updated_at')) {
                $table->dropColumn('selling_price_updated_at');
            }
        });
    }
};
