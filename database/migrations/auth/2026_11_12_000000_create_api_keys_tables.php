<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel API key untuk integrasi antar sistem. Dibuat di koneksi mysql_auth
     * (sagansa_user) karena key adalah kredensial, sekelas dengan
     * personal_access_tokens & device_tokens. Plaintext key TIDAK disimpan —
     * hanya hash SHA-256 + prefix untuk identifikasi di UI. Tabel
     * api_key_audit_logs diisi oleh project konsumen (bukan apps/admin);
     * referensi created_by longgar (tanpa FK) mengikuti pola device_tokens.
     */
    public function up(): void
    {
        if (! Schema::connection('mysql_auth')->hasTable('api_keys')) {
            Schema::connection('mysql_auth')->create('api_keys', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 100);
                $table->string('key_hash', 64)->unique();
                $table->string('key_prefix', 20);
                $table->json('scopes')->nullable();
                $table->json('allowed_ips')->nullable();
                $table->unsignedInteger('rate_limit')->default(60);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::connection('mysql_auth')->hasTable('api_key_audit_logs')) {
            Schema::connection('mysql_auth')->create('api_key_audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('api_key_id')->constrained('api_keys')->cascadeOnDelete();
                $table->string('method', 10);
                $table->string('path', 255);
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->string('ip', 45);
                $table->timestamp('created_at')->nullable();

                $table->index(['api_key_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mysql_auth')->dropIfExists('api_key_audit_logs');
        Schema::connection('mysql_auth')->dropIfExists('api_keys');
    }
};
