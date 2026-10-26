<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seed template default (satu per jenis surat) domain document.
 *
 * `body_html` berupa HTML sederhana (h1/h2, p, ol) siap render dompdf, dengan
 * kalimat pembuka/penutup resmi bahasa Indonesia. Placeholder standar:
 * {{nomor}} {{tanggal}} {{penerima}} {{isi}} {{penandatangan}}; KUA memuat
 * blok penerima kuasa & masa berlaku, BA memuat blok hadir_rapat.
 *
 * Idempoten: template dicocokkan berdasarkan (letter_type_id, name), lalu
 * di-update bila sudah ada (created_at dipertahankan) atau di-insert bila
 * belum.
 */
class DocumentTemplateSeeder extends Seeder
{
    /**
     * Template default per kode jenis surat.
     *
     * @var array<string, array{name: string, placeholders: array<int, string>, body_html: string}>
     */
    private const TEMPLATES = [
        'ST' => [
            'name' => 'Default Surat Tugas',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT TUGAS</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Yang bertanda tangan di bawah ini, bertindak untuk dan atas nama PT Sagansa, dengan ini memberikan tugas kepada:</p>
<p>{{penerima}}</p>
<p>untuk melaksanakan tugas dengan rincian sebagai berikut:</p>
<p>{{isi}}</p>
<p>Pelaksana tugas diwajibkan menunaikan tugas tersebut dengan penuh tanggung jawab dan segera melaporkan hasil pelaksanaannya kepada pimpinan setelah tugas selesai.</p>
<p>Demikian Surat Tugas ini dibuat untuk dilaksanakan sebagaimana mestinya.</p>
<p>{{tanggal}}</p>
<p>Hormat kami,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'SK' => [
            'name' => 'Default Surat Keputusan',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT KEPUTUSAN</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Yang bertanda tangan di bawah ini, bertindak untuk dan atas nama PT Sagansa, berdasarkan pertimbangan sebagaimana tercantum dalam berkas resmi perusahaan,</p>
<p>dengan ini <strong>MEMUTUSKAN</strong> dan <strong>MENETAPKAN</strong> keputusan berikut, yang berlaku bagi:</p>
<p>{{penerima}}</p>
<ol>
<li>{{isi}}</li>
</ol>
<p>Keputusan ini berlaku terhitung sejak tanggal ditetapkan. Apabila di kemudian hari terdapat kekeliruan dalam penetapan ini, akan dilakukan perbaikan sebagaimana mestinya.</p>
<p>Ditetapkan pada: {{tanggal}}</p>
<p>PT Sagansa</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'SPR' => [
            'name' => 'Default Surat Pernyataan',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT PERNYATAAN</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Saya yang bertanda tangan di bawah ini:</p>
<p>{{penerima}}</p>
<p>dengan ini menyatakan dengan sesungguhnya bahwa:</p>
<p>{{isi}}</p>
<p>Demikian pernyataan ini saya buat dengan sebenar-benarnya, dalam keadaan sadar, tanpa paksaan dari pihak mana pun. Apabila di kemudian hari pernyataan ini terbukti tidak benar, saya bersedia mempertanggungjawabkannya sesuai dengan ketentuan yang berlaku.</p>
<p>{{tanggal}}</p>
<p>Yang Membuat Pernyataan,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'SKK' => [
            'name' => 'Default Surat Keterangan',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT KETERANGAN</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Yang bertanda tangan di bawah ini, bertindak untuk dan atas nama PT Sagansa, dengan ini menerangkan dengan sebenarnya bahwa:</p>
<p>{{penerima}}</p>
<p>{{isi}}</p>
<p>Surat Keterangan ini dibuat berdasarkan keadaan yang sebenarnya dan dipergunakan sebagaimana mestinya oleh pihak-pihak yang berkepentingan.</p>
<p>Dikeluarkan pada: {{tanggal}}</p>
<p>Hormat kami,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'SP' => [
            'name' => 'Default Surat Pengantar',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT PENGANTAR</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Kepada Yth.</p>
<p>{{penerima}}</p>
<p>di tempat</p>
<p>Dengan hormat,</p>
<p>bersama surat ini kami sampaikan dengan hormat hal-hal sebagai berikut:</p>
<p>{{isi}}</p>
<p>Demikian Surat Pengantar ini kami buat untuk dipergunakan sebagaimana mestinya. Atas perhatian dan kerja sama Bapak/Ibu, kami mengucapkan terima kasih.</p>
<p>{{tanggal}}</p>
<p>Hormat kami,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'UND' => [
            'name' => 'Default Surat Undangan',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT UNDANGAN</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Kepada Yth.</p>
<p>{{penerima}}</p>
<p>di tempat</p>
<p>Dengan hormat,</p>
<p>dengan ini kami mengundang Bapak/Ibu untuk hadir pada acara dengan ketentuan sebagai berikut:</p>
<p>{{isi}}</p>
<p>Mengingat pentingnya acara tersebut, kami mengharapkan kehadiran Bapak/Ibu tepat pada waktunya. Atas perhatian dan kesediaan hadir Bapak/Ibu, kami mengucapkan terima kasih.</p>
<p>{{tanggal}}</p>
<p>Hormat kami,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'SE' => [
            'name' => 'Default Surat Edaran',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT EDARAN</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Kepada seluruh:</p>
<p>{{penerima}}</p>
<p>di tempat masing-masing</p>
<p>Dengan hormat,</p>
<p>dengan ini kami sampaikan hal-hal sebagai berikut:</p>
<p>{{isi}}</p>
<p>Demikian Surat Edaran ini kami sampaikan untuk menjadi perhatian dan dilaksanakan sebagaimana mestinya.</p>
<p>Dikeluarkan pada: {{tanggal}}</p>
<p>Hormat kami,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'BA' => [
            'name' => 'Default Berita Acara',
            'placeholders' => ['nomor', 'tanggal', 'hadir_rapat', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>BERITA ACARA</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Pada hari ini, {{tanggal}}, telah berlangsung rapat yang diselenggarakan oleh PT Sagansa dan dihadiri oleh:</p>
<p>{{hadir_rapat}}</p>
<p>Rapat tersebut membahas dan menyepakati hal-hal sebagai berikut:</p>
<p>{{isi}}</p>
<p>Demikian Berita Acara ini dibuat dengan sebenarnya, untuk selanjutnya disetujui dan ditandatangani oleh seluruh pihak yang hadir, serta dipergunakan sebagaimana mestinya.</p>
<p>Hormat kami,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'PK' => [
            'name' => 'Default Surat Perjanjian',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT PERJANJIAN</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Pada hari ini, {{tanggal}}, telah dibuat dan disepakati Surat Perjanjian oleh dan antara:</p>
<p><strong>Pihak Pertama</strong></p>
<p>PT Sagansa</p>
<p><strong>Pihak Kedua</strong></p>
<p>{{penerima}}</p>
<p>Kedua belah pihak sepakat untuk mengikatkan diri dalam perjanjian dengan ketentuan sebagai berikut:</p>
<p>{{isi}}</p>
<p>Perjanjian ini dibuat dalam keadaan sadar, tanpa paksaan dari pihak mana pun, dalam rangkap dua (2) yang masing-masing mempunyai kekuatan hukum yang sama, dan berlaku sejak tanggal ditandatangani oleh kedua belah pihak.</p>
<p>Pihak Pertama,</p>
<p><strong>{{penandatangan}}</strong></p>
<p>Pihak Kedua,</p>
<p><strong>{{penerima}}</strong></p>
HTML,
        ],
        'REC' => [
            'name' => 'Default Surat Rekomendasi',
            'placeholders' => ['nomor', 'tanggal', 'penerima', 'isi', 'penandatangan'],
            'body_html' => <<<'HTML'
<h1>SURAT REKOMENDASI</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Yang bertanda tangan di bawah ini, bertindak untuk dan atas nama PT Sagansa, dengan ini memberikan rekomendasi kepada:</p>
<p>{{penerima}}</p>
<p>berdasarkan pertimbangan sebagai berikut:</p>
<p>{{isi}}</p>
<p>Surat Rekomendasi ini diberikan untuk dipergunakan sebagaimana mestinya dan tidak mengurangi tanggung jawab pihak penerima rekomendasi dalam melaksanakan kewajibannya.</p>
<p>Diberikan pada: {{tanggal}}</p>
<p>Hormat kami,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
        'KUA' => [
            'name' => 'Default Surat Kuasa',
            'placeholders' => [
                'nomor',
                'tanggal',
                'penerima_kuasa_nama',
                'penerima_kuasa_nik',
                'penerima_kuasa_alamat',
                'isi',
                'masa_berlaku',
                'penandatangan',
            ],
            'body_html' => <<<'HTML'
<h1>SURAT KUASA</h1>
<h2>Nomor: {{nomor}}</h2>
<p>Pada hari ini, {{tanggal}}, yang bertanda tangan di bawah ini:</p>
<p>{{penandatangan}}</p>
<p>selaku <strong>Pemberi Kuasa</strong>, dengan ini memberikan kuasa kepada:</p>
<h2>Penerima Kuasa</h2>
<p>Nama: {{penerima_kuasa_nama}}</p>
<p>NIK: {{penerima_kuasa_nik}}</p>
<p>Alamat: {{penerima_kuasa_alamat}}</p>
<p>selaku <strong>Penerima Kuasa</strong>, untuk dan atas nama Pemberi Kuasa melaksanakan hal-hal sebagai berikut:</p>
<p>{{isi}}</p>
<h2>Masa Berlaku Kuasa</h2>
<p>{{masa_berlaku}}</p>
<p>Segala akibat hukum yang timbul dari penggunaan kuasa ini menjadi tanggung jawab Pemberi Kuasa sepanjang dilaksanakan sesuai dengan isi kuasa ini.</p>
<p>Demikian Surat Kuasa ini dibuat dalam keadaan sadar, tanpa paksaan dari pihak mana pun, untuk dipergunakan sebagaimana mestinya.</p>
<p>Pemberi Kuasa,</p>
<p><strong>{{penandatangan}}</strong></p>
HTML,
        ],
    ];

    public function run(): void
    {
        $connection = DB::connection('mysql_document');

        $typeIds = $connection->table('letter_types')
            ->whereIn('code', array_keys(self::TEMPLATES))
            ->pluck('id', 'code');

        $now = now();

        foreach (self::TEMPLATES as $code => $template) {
            $typeId = $typeIds[$code] ?? null;

            if ($typeId === null) {
                throw new RuntimeException(
                    "Jenis surat '{$code}' tidak ditemukan — jalankan DocumentLetterTypeSeeder terlebih dahulu."
                );
            }

            $values = [
                'body_html' => $template['body_html'],
                'placeholders' => json_encode($template['placeholders']),
                'is_default' => true,
                'is_active' => true,
                'updated_at' => $now,
            ];

            // Builder dibuat segar per query — builder yang dipakai ulang akan
            // menumpuk klausa where antar iterasi dan merusak pencocokan.
            $existing = $connection->table('templates')
                ->where('letter_type_id', $typeId)
                ->where('name', $template['name']);

            if ($existing->exists()) {
                $existing->update($values);
            } else {
                $connection->table('templates')->insert($values + [
                    'letter_type_id' => $typeId,
                    'name' => $template['name'],
                    'created_at' => $now,
                ]);
            }
        }
    }
}
