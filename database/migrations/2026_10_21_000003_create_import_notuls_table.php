<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notul (nota pelaksanaan) dari kantor pabean.
 *
 * PPh/PPN pada notul adalah angka final yang menggantikan estimasi pajak.
 * Denda administrasi dan biaya lain ditambahkan sebagai biaya real; seluruh
 * nilainya disinkronkan ke import_costs agar perhitungan HPP ikut berubah.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (! Schema::hasTable('import_notuls')) {
            Schema::create('import_notuls', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('import_purchase_id')
                    ->constrained('import_purchases')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->string('notul_number', 100)->nullable();
                $table->date('notul_date');
                $table->decimal('pph_amount', 20, 2)->default(0);
                $table->decimal('ppn_amount', 20, 2)->default(0);
                $table->decimal('penalty_amount', 20, 2)->default(0);
                $table->decimal('other_amount', 20, 2)->default(0);
                $table->string('other_description', 100)->nullable();
                $table->text('note')->nullable();
                // Dokumen notul opsional (img service path).
                $table->string('document_path', 500)->nullable();
                $table->string('document_original_name', 255)->nullable();
                $table->string('document_mime_type', 100)->nullable();
                $table->unsignedInteger('document_size_bytes')->nullable();
                $table->timestamps();

                $table->index(['import_purchase_id', 'notul_date']);
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        Schema::dropIfExists('import_notuls');
    }
};
