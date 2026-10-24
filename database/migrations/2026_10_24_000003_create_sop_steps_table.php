<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Langkah SOP terstruktur (pengganti dokumen PDF): tiap langkah bernomor
 * dengan judul, deskripsi, dan gambar panduan (path img service).
 *
 * `requires_photo` reserved untuk fase eksekusi checklist (fase C): langkah
 * yang nanti mewajibkan bukti foto saat staf menjalankan SOP.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (! Schema::hasTable('sop_steps')) {
            Schema::create('sop_steps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('sop_id')
                    ->constrained('sops')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                // Path gambar panduan di img service (opsional per langkah).
                $table->string('image', 500)->nullable();
                $table->boolean('requires_photo')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['sop_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        Schema::dropIfExists('sop_steps');
    }
};
