<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kelengkapan dokumen proses impor (mirror pib_supporting_docs di ceisa).
 * File diupload single-hop ke img service (gambar → WebP, PDF → asli) lalu
 * relative path-nya disimpan di file_path; gerbang pindah tahap memeriksa
 * keberadaan tipe dokumen wajib.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (!Schema::hasTable('import_documents')) {
            Schema::create('import_documents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('import_purchase_id')->constrained('import_purchases')->cascadeOnUpdate()->cascadeOnDelete();
                $table->enum('type', [
                    'po',
                    'invoice',
                    'payment_proof',
                    'packing_list',
                    'bl',
                    'coo',
                    'insurance',
                    'pib',
                    'tax_proof',
                    'notul',
                    'delivery_order',
                    'unloading_receipt',
                    'port_receipt',
                    'other',
                ]);
                $table->string('file_path', 500);
                $table->string('original_name', 255)->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->unsignedInteger('size_bytes')->nullable();
                // Pengunggah (user di DB auth — tanpa FK).
                $table->unsignedBigInteger('uploaded_by')->index();
                $table->timestamps();

                $table->index(['import_purchase_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        Schema::dropIfExists('import_documents');
    }
};
