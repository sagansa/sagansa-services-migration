<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HPP (harga pokok per satuan) hasil finalisasi proses impor — dasar
 * penentuan harga jual minimum. Di-update oleh endpoint finalize.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'hpp')) {
                $table->decimal('hpp', 20, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'hpp_updated_at')) {
                $table->timestamp('hpp_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'hpp')) {
                $table->dropColumn('hpp');
            }
            if (Schema::hasColumn('products', 'hpp_updated_at')) {
                $table->dropColumn('hpp_updated_at');
            }
        });
    }
};
