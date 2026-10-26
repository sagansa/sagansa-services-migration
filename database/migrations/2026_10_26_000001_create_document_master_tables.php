<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master domain surat perusahaan (DB `sagansa_document`).
 *
 * - `letter_types`: jenis surat (ST/SK/SPR/... KUA) + definisi field khusus
 *   per jenis (mis. penerima kuasa & masa berlaku untuk KUA).
 * - `templates`: template isi surat (HTML) per jenis, satu default per jenis.
 * - `number_sequences`: counter nomor per (jenis, unit, tahun) — atomic
 *   increment untuk format nomor surat.
 * - `parties`: pihak eksternal (penerima/kuasa) yang bisa dipakai ulang
 *   antar surat; `created_from_document_id` menandai pihak yang lahir dari
 *   isian bebas saat membuat surat (dokumen belum ada → tanpa FK).
 * - `signers`: penandatangan internal, `user_id` merujuk `users` di DB
 *   `sagansa_user` — TANPA FK lintas database (pola referensi lintas DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_document');

        // 1. letter_types
        if (! $schema->hasTable('letter_types')) {
            $schema->create('letter_types', function (Blueprint $table) {
                $table->id();
                // Kode jenis surat: ST, SK, SPR, SKK, SP, UND, SE, BA, PK, REC, KUA.
                $table->string('code', 8)->unique();
                $table->string('name');
                // Definisi field khusus per jenis (mis. KUA: penerima_kuasa, masa_berlaku).
                $table->json('specific_fields')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. templates
        if (! $schema->hasTable('templates')) {
            $schema->create('templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('letter_type_id')
                    ->constrained('letter_types')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->string('name');
                $table->longText('body_html');
                // Daftar placeholder yang dikenali template (mis. nomor, tanggal, penerima).
                $table->json('placeholders')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. number_sequences (tanpa timestamps: baris counter murni)
        if (! $schema->hasTable('number_sequences')) {
            $schema->create('number_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('letter_type_code', 8);
                $table->string('unit', 16);
                $table->integer('year');
                $table->integer('last_seq')->default(0);

                $table->unique(['letter_type_code', 'unit', 'year']);
            });
        }

        // 4. parties
        if (! $schema->hasTable('parties')) {
            $schema->create('parties', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->string('identity_no')->nullable();
                $table->text('address')->nullable();
                $table->string('occupation')->nullable();
                $table->text('notes')->nullable();
                // Pihak pertama kali dibuat dari isian bebas pada surat ini
                // (tabel documents dibuat migration berikutnya → tanpa FK).
                $table->uuid('created_from_document_id')->nullable();
                $table->timestamps();
            });
        }

        // 5. signers
        if (! $schema->hasTable('signers')) {
            $schema->create('signers', function (Blueprint $table) {
                $table->id();
                // User DB `sagansa_user` — tanpa FK lintas database.
                $table->unsignedBigInteger('user_id');
                $table->enum('role', ['direktur', 'komisaris', 'pemegang_saham']);
                $table->string('display_name');
                $table->string('title')->nullable();
                // Path aset tanda tangan (img service), opsional.
                $table->string('signature_asset_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Satu user maksimal satu baris per role (sekaligus index user_id).
                $table->unique(['user_id', 'role']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql_document');

        $schema->dropIfExists('signers');
        $schema->dropIfExists('parties');
        $schema->dropIfExists('number_sequences');
        $schema->dropIfExists('templates');
        $schema->dropIfExists('letter_types');
    }
};
