<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('stock_cards') && !Schema::hasColumn('stock_cards', 'updated_by')) {
            Schema::table('stock_cards', function (Blueprint $table) {
                // Editor terakhir laporan stock (koreksi storage-staff/admin).
                // Tanpa FK — konsisten dengan kolom user_id di tabel ini.
                $table->unsignedBigInteger('updated_by')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stock_cards') && Schema::hasColumn('stock_cards', 'updated_by')) {
            Schema::table('stock_cards', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
    }
};
