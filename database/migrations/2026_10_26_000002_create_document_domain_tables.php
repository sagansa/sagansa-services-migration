<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel inti domain surat perusahaan (DB `sagansa_document`).
 *
 * - `documents`: surat itu sendiri; PK uuid karena nomor surat dipakai
 *   publik (link verifikasi) dan id tidak boleh mudah ditebak.
 * - `document_attachments`: lampiran per surat (path + sha256 di storage).
 * - `issuances`: hasil penerbitan (PDF final + hash); `replaced_by_id`
 *   menunjuk penerbitan pengganti (versi lama dinonaktifkan).
 * - `signing_requests`: permintaan persetujuan penandatangan per surat.
 * - `audit_logs`: jejak aktivitas surat, append-only (tanpa updated_at).
 * - `verification_hits`: hit counter verifikasi publik per surat.
 *
 * Catatan pola referensi lintas database: `created_by`, `finalized_by`,
 * `actor_user_id` merujuk `users` di DB `sagansa_user` — kolom
 * unsignedBigInteger TANPA FK. `documents.company_id` juga tanpa FK
 * (entitas perusahaan belum ada di DB ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_document');

        // 1. documents
        if (! $schema->hasTable('documents')) {
            $schema->create('documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('letter_type_id')
                    ->constrained('letter_types')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                // Identitas perusahaan penerbit (tanpa FK — belum ada tabel
                // perusahaan di DB ini).
                $table->uuid('company_id')->nullable()->index();
                // Nomor surat final, mis. "001/ST/DIR/X/2026".
                $table->string('number')->unique();
                $table->integer('seq');
                $table->string('unit', 16);
                $table->integer('year');
                $table->string('subject');
                $table->date('date');
                $table->longText('body_html')->nullable();
                // Metadata/field khusus per jenis surat (mengikuti
                // letter_types.specific_fields).
                $table->json('metadata')->nullable();
                $table->enum('classification', ['umum', 'rahasia'])->default('umum');
                $table->enum('status', ['draft', 'menunggu_ttd', 'terbit', 'dicabut', 'dibatalkan'])->default('draft');
                $table->timestamp('revoked_at')->nullable();
                $table->string('revocation_reason')->nullable();
                // User DB `sagansa_user` — tanpa FK lintas database.
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->index(['status', 'year']);
            });
        }

        // 2. document_attachments
        if (! $schema->hasTable('document_attachments')) {
            $schema->create('document_attachments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('document_id')
                    ->constrained('documents')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->string('label');
                $table->integer('sort')->default(0);
                // Path di storage service + hash untuk integritas.
                $table->string('path');
                $table->char('sha256', 64);
                $table->string('mime');
                $table->integer('size');
                $table->timestamps();

                $table->index('document_id');
            });
        }

        // 3. issuances
        if (! $schema->hasTable('issuances')) {
            $schema->create('issuances', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('document_id')
                    ->constrained('documents')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                // Metode distribusi (a/b/c/d) + label penyajiannya.
                $table->enum('method', ['a', 'b', 'c', 'd']);
                $table->enum('method_label', ['elektronik', 'ttd_elektronik_resmi', 'manual']);
                // Berkas final yang diterbitkan + hash-nya; hash gabungan
                // lampiran disimpan agar terverifikasi utuh.
                $table->string('file_path');
                $table->char('file_sha256', 64);
                $table->char('attachments_sha256', 64)->nullable();
                $table->integer('file_size');
                $table->integer('pages')->nullable();
                $table->string('note')->nullable();
                // Penerbitan pengganti (versi lama dinonaktifkan, menunjuk
                // ke penerbitan barunya); nullOnDelete agar riwayat aman.
                $table->uuid('replaced_by_id')->nullable();
                $table->foreign('replaced_by_id')
                    ->references('id')
                    ->on('issuances')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
                $table->boolean('is_active')->default(true);
                // User DB `sagansa_user` — tanpa FK lintas database.
                $table->unsignedBigInteger('finalized_by');
                $table->timestamp('finalized_at');
                $table->timestamps();

                $table->index(['document_id', 'is_active']);
            });
        }

        // 4. signing_requests (tanpa timestamps: requested_at/acted_at cukup)
        if (! $schema->hasTable('signing_requests')) {
            $schema->create('signing_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('document_id')
                    ->constrained('documents')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreignId('signer_id')
                    ->constrained('signers')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->enum('status', ['diminta', 'disetujui', 'ditolak'])->default('diminta');
                $table->timestamp('requested_at');
                $table->timestamp('acted_at')->nullable();
                $table->string('note')->nullable();

                $table->index(['signer_id', 'status']);
            });
        }

        // 5. audit_logs (append-only: hanya created_at, TANPA updated_at)
        if (! $schema->hasTable('audit_logs')) {
            $schema->create('audit_logs', function (Blueprint $table) {
                $table->id();
                // Tanpa FK: jejak audit dipertahankan meski baris surat
                // dihapus (document_id hanya dicatat + di-index).
                $table->uuid('document_id')->index();
                // User DB `sagansa_user` — tanpa FK lintas database.
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('event', 32);
                $table->json('payload')->nullable();
                $table->ipAddress('ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        // 6. verification_hits (append-only: hanya created_at)
        if (! $schema->hasTable('verification_hits')) {
            $schema->create('verification_hits', function (Blueprint $table) {
                $table->id();
                $table->uuid('document_id')->index();
                $table->ipAddress('ip')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql_document');

        $schema->dropIfExists('verification_hits');
        $schema->dropIfExists('audit_logs');
        $schema->dropIfExists('signing_requests');
        $schema->dropIfExists('issuances');
        $schema->dropIfExists('document_attachments');
        $schema->dropIfExists('documents');
    }
};
