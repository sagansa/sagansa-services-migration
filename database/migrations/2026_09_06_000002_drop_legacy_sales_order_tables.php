<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus tabel penjualan lama yang seluruh isinya sudah digabungkan
 * ke sales_orders + detail_sales_orders oleh migrasi 2026_09_06_000001
 * (terverifikasi: 12.302 baris sumber = 12.302 baris mapping).
 *
 * Tabel legacy_sales_order_migration sengaja dipertahankan sebagai jejak
 * audit asal-usul setiap baris hasil merge (source_table + source_id).
 *
 * Urutan drop child -> parent agar foreign key tidak menghalangi.
 * down() sengaja tidak mengembalikan apa pun: data asli tidak bisa
 * dipulihkan dari migrasi — gunakan backup
 * database/backups/legacy_sales_order_tables_2026-09-06.sql.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    private const TABLES = [
        'so_ddetails',
        'product_sales_order_online',
        'product_sales_order_employee',
        'sales_order_direct_products',
        'sales_order_onlines',
        'sales_order_employees',
        'sales_order_directs',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Restorasi data hanya lewat file backup mysqldump, bukan dari migrasi.
    }
};
