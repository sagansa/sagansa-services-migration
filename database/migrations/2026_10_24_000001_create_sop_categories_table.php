<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori SOP (Standard Operation Procedure) — mis. Buka Toko, Tutup Toko,
 * Kebersihan, Kasir. Scope v1: global lintas toko; pengelompokan hanya
 * kategori.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (! Schema::hasTable('sop_categories')) {
            Schema::create('sop_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                // Nama ikon untuk kategori (opsional, dipetakan client).
                $table->string('icon', 100)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        Schema::dropIfExists('sop_categories');
    }
};
