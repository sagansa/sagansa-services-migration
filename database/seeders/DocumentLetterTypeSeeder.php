<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed master jenis surat domain document (DB `sagansa_document`).
 *
 * Idempoten: baris dicocokkan berdasarkan `code`, lalu di-update bila sudah
 * ada (created_at dipertahankan) atau di-insert bila belum.
 */
class DocumentLetterTypeSeeder extends Seeder
{
    /**
     * 11 jenis surat standar perusahaan. Hanya KUA yang punya definisi
     * `specific_fields` (penerima kuasa + masa berlaku kuasa).
     *
     * @var array<int, array{code: string, name: string, specific_fields: array|null}>
     */
    private const LETTER_TYPES = [
        ['code' => 'ST', 'name' => 'Surat Tugas', 'specific_fields' => null],
        ['code' => 'SK', 'name' => 'Surat Keputusan', 'specific_fields' => null],
        ['code' => 'SPR', 'name' => 'Surat Pernyataan', 'specific_fields' => null],
        ['code' => 'SKK', 'name' => 'Surat Keterangan', 'specific_fields' => null],
        ['code' => 'SP', 'name' => 'Surat Pengantar', 'specific_fields' => null],
        ['code' => 'UND', 'name' => 'Surat Undangan', 'specific_fields' => null],
        ['code' => 'SE', 'name' => 'Surat Edaran', 'specific_fields' => null],
        ['code' => 'BA', 'name' => 'Berita Acara', 'specific_fields' => null],
        ['code' => 'PK', 'name' => 'Surat Perjanjian', 'specific_fields' => null],
        ['code' => 'REC', 'name' => 'Surat Rekomendasi', 'specific_fields' => null],
        [
            'code' => 'KUA',
            'name' => 'Surat Kuasa',
            'specific_fields' => [
                'penerima_kuasa' => [
                    'nama' => '',
                    'nik' => '',
                    'alamat' => '',
                ],
                'masa_berlaku' => [
                    'mode' => 'hingga_tanggal|selesai_urusan|tanpa_batas',
                    'tanggal' => null,
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $connection = DB::connection('mysql_document');
        $now = now();

        foreach (self::LETTER_TYPES as $type) {
            $values = [
                'name' => $type['name'],
                'specific_fields' => $type['specific_fields'] === null
                    ? null
                    : json_encode($type['specific_fields'], JSON_UNESCAPED_SLASHES),
                'is_active' => true,
                'updated_at' => $now,
            ];

            // Builder dibuat segar per query — builder yang dipakai ulang akan
            // menumpuk klausa where antar iterasi dan merusak pencocokan.
            if ($connection->table('letter_types')->where('code', $type['code'])->exists()) {
                $connection->table('letter_types')->where('code', $type['code'])->update($values);
            } else {
                $connection->table('letter_types')->insert($values + [
                    'code' => $type['code'],
                    'created_at' => $now,
                ]);
            }
        }
    }
}
