<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // TEKS PLACEHOLDER: ganti dengan data resmi perusahaan lewat panel admin / langsung di DB.
        $contents = [
            'visi' => '[ISI DENGAN VISI RESMI PERUSAHAAN]',
            'misi' => "[ISI MISI 1]\n[ISI MISI 2]\n[ISI MISI 3]",
            'sejarah' => '[ISI DENGAN SEJARAH SINGKAT PERUSAHAAN]',
            'alur_pendaftaran' => implode("\n", [
                '1. Buat akun dan login',
                '2. Lengkapi data pribadi',
                '3. Pilih lowongan, lalu unggah berkas yang diminta',
                '4. Pendaftaran ditinjau oleh admin',
                '5. Pantau status pendaftaran di akun kamu',
            ]),
            'kontak' => "[ALAMAT KANTOR]\n[NOMOR TELEPON / WHATSAPP]\n[EMAIL]",
        ];

        foreach ($contents as $key => $value) {
            DB::table('site_contents')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // Placeholder gambar: ganti image_path dengan aset yang sudah kamu punya (storage/public).
        foreach (range(1, 3) as $i) {
            DB::table('carousel_slides')->insert([
                'title' => 'Slide '.$i,
                'subtitle' => '[Teks pendek slide '.$i.']',
                'image_path' => 'images/carousel/slide-'.$i.'.jpg',
                'sort_order' => $i,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (range(1, 6) as $i) {
            DB::table('gallery_items')->insert([
                'caption' => '[Keterangan kegiatan '.$i.']',
                'image_path' => 'images/gallery/foto-'.$i.'.jpg',
                'taken_at' => now()->subDays($i * 20)->toDateString(),
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
