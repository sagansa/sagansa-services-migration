<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel surat masuk, disposisi & runutan surat (DB `sagansa_document`,
 * fase 1b — murni aditif, tabel fase 1 tidak tersentuh).
 *
 * - `incoming_letters`: registrasi surat masuk dari pihak eksternal;
 *   PK uuid (pola `documents`), nomor agenda internal unik, scan ber-hash
 *   sebagai bukti tunggal (immutable). `sender_party_id` merujuk
 *   `parties` dengan RESTRICT (penghapusan pihak yang masih dikutip
 *   surat masuk ditolak — konsisten migration FK-restrict fase 1).
 * - `disposition_records`: catatan pengarahan (disposisi) per surat
 *   masuk, append-only: hanya `created_at` TANPA `updated_at`.
 * - `letter_links`: edge list runutan surat, polymorphic dua sisi
 *   (`document` | `incoming_letter`). TANPA FK ke node (guard duplikat/
 *   self/cycle di service layer, bukan di DB); `created_at` saja.
 *
 * Catatan pola referensi lintas database: `created_by` dan
 * `to_user_id` merujuk `users` di DB `sagansa_user` — kolom
 * unsignedBigInteger TANPA FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_document');

        // 1. incoming_letters
        if (! $schema->hasTable('incoming_letters')) {
            $schema->create('incoming_letters', function (Blueprint $table) {
                $table->uuid('id')->primary();
                // Identitas perusahaan penerima (tanpa FK — belum ada tabel
                // perusahaan di DB ini, pola `documents.company_id`).
                $table->uuid('company_id')->nullable();
                // Nomor agenda internal otomatis, mis. "SM-001/X/2026"
                // (counter reuse `number_sequences`, code SM, unit IN).
                $table->string('agenda_number')->unique();
                $table->integer('agenda_seq');
                $table->integer('year');
                // Nomor surat versi pengirim (data, bukan kunci).
                $table->string('number_external')->nullable();
                $table->date('letter_date')->nullable();
                $table->date('received_at');
                // Pengirim dari tabel parties; RESTRICT — pihak yang masih
                // dikutip surat masuk tidak boleh dihapus.
                $table->foreignId('sender_party_id')
                    ->constrained('parties')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
                $table->string('subject');
                // Sifat penyampaian; rahasia => classification rahasia.
                $table->enum('sifat', ['biasa', 'segera', 'rahasia'])->default('biasa');
                $table->enum('classification', ['umum', 'rahasia'])->default('umum');
                // terdaftar -> diproses -> selesai (manual); saat selesai
                // semua field terkunci (409 INCOMING_LOCKED).
                $table->enum('status', ['terdaftar', 'diproses', 'selesai'])->default('terdaftar');
                // Scan bukti tunggal: wajib saat create, immutable.
                $table->string('scan_path');
                $table->char('scan_sha256', 64);
                $table->integer('scan_size');
                $table->integer('scan_pages')->nullable();
                // Uraian lampiran fisik yang menyertai surat (opsional).
                $table->text('attachments_note')->nullable();
                // User DB `sagansa_user` — tanpa FK lintas database.
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->index(['status', 'year']);
            });
        }

        // 2. disposition_records (append-only: hanya created_at, TANPA updated_at)
        if (! $schema->hasTable('disposition_records')) {
            $schema->create('disposition_records', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('incoming_letter_id')
                    ->constrained('incoming_letters')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                // Penerima disposisi: user DB `sagansa_user` (signer aktif) —
                // tanpa FK lintas database.
                $table->unsignedBigInteger('to_user_id');
                $table->text('instruction');
                // Hanya status + acted_at yang boleh di-update.
                $table->enum('status', ['open', 'selesai'])->default('open');
                $table->timestamp('acted_at')->nullable();
                // User DB `sagansa_user` — tanpa FK lintas database.
                $table->unsignedBigInteger('created_by');
                $table->timestamp('created_at')->nullable();

                $table->index('incoming_letter_id');
            });
        }

        // 3. letter_links (edge runutan; created_at saja, TANPA FK polymorphic)
        if (! $schema->hasTable('letter_links')) {
            $schema->create('letter_links', function (Blueprint $table) {
                $table->uuid('id')->primary();
                // Node dua sisi lintas jenis surat; keabsahan target
                // (ada/tidak, duplikat, self, cycle) dijaga di service —
                // DB hanya menjaga keunikan edge.
                $table->enum('parent_type', ['document', 'incoming_letter']);
                $table->uuid('parent_id');
                $table->enum('child_type', ['document', 'incoming_letter']);
                $table->uuid('child_id');
                $table->enum('relation', ['balasan', 'tindak_lanjut', 'referensi']);
                // User DB `sagansa_user` — tanpa FK lintas database.
                $table->unsignedBigInteger('created_by');
                $table->timestamp('created_at')->nullable();

                // Nama eksplisit: nama otomatis Laravel (71 karakter)
                // melebihi batas identifier MySQL (64 karakter).
                $table->unique(
                    ['parent_type', 'parent_id', 'child_type', 'child_id', 'relation'],
                    'letter_links_edge_unique'
                );
                $table->index(['child_type', 'child_id']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql_document');

        $schema->dropIfExists('letter_links');
        $schema->dropIfExists('disposition_records');
        $schema->dropIfExists('incoming_letters');
    }
};
