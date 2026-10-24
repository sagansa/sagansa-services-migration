<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur konten pada tabel legacy `sops` (sebelumnya hanya arsip file).
 *
 * SOP menjadi konten terstruktur: kategori, deskripsi, cover, status
 * publish, urutan. Kolom lama (title, revision, file) dipertahankan —
 * revision dipakai sebagai penanda versi (naik otomatis saat admin
 * mengubah), file dibiarkan tidak terpakai (v1 tanpa PDF).
 *
 * `store_id` reserved untuk scoping per toko (fase lanjutan) — tanpa FK
 * agar aman terhadap perbedaan tipe PK tabel stores.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        if (! Schema::hasColumn('sops', 'sop_category_id')) {
            Schema::table('sops', function (Blueprint $table) {
                $table->foreignId('sop_category_id')
                    ->nullable()
                    ->after('title')
                    ->constrained('sop_categories')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('sops', 'description')) {
            Schema::table('sops', function (Blueprint $table) {
                $table->text('description')->nullable()->after('revision');
            });
        }
        if (! Schema::hasColumn('sops', 'cover_image')) {
            Schema::table('sops', function (Blueprint $table) {
                // Path di img service (img.sagansa.id), bukan URL penuh.
                $table->string('cover_image', 500)->nullable()->after('description');
            });
        }
        if (! Schema::hasColumn('sops', 'is_published')) {
            Schema::table('sops', function (Blueprint $table) {
                $table->boolean('is_published')->default(false)->after('cover_image');
                $table->timestamp('published_at')->nullable()->after('is_published');
            });
        }
        if (! Schema::hasColumn('sops', 'sort_order')) {
            Schema::table('sops', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('published_at');
            });
        }
        if (! Schema::hasColumn('sops', 'store_id')) {
            Schema::table('sops', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('sort_order');
            });
        }

        Schema::table('sops', function (Blueprint $table) {
            $table->index(['sop_category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('sops', function (Blueprint $table) {
            $table->dropIndex(['sop_category_id', 'sort_order']);
        });

        foreach (['sop_category_id', 'description', 'cover_image', 'is_published',
                  'published_at', 'sort_order', 'store_id'] as $column) {
            if (Schema::hasColumn('sops', $column)) {
                Schema::table('sops', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
