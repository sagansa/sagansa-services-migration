<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->seedDocumentDomain();
    }

    /**
     * Seed master domain surat (jenis surat + template default) ke DB
     * `sagansa_document`. Hanya dijalankan bila skemanya sudah tersedia —
     * environment yang belum migrate domain document tidak akan meledak.
     */
    private function seedDocumentDomain(): void
    {
        $schema = DB::connection('mysql_document')->getSchemaBuilder();

        if ($schema->hasTable('letter_types') && $schema->hasTable('templates')) {
            $this->call([
                DocumentLetterTypeSeeder::class,
                DocumentTemplateSeeder::class,
            ]);
        }
    }
}
