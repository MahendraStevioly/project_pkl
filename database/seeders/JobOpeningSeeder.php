<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobOpeningSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('email', 'admin1@ewf.test')->value('id');
        $now = now();

        // Lowongan CONTOH untuk pengembangan. Ganti lewat panel admin.
        $jobs = [
            ['Magang Staff Administrasi', 'magang', 45, true],
            ['Magang Digital Marketing', 'magang', 30, true],
            ['Magang Customer Service', 'magang', 60, true],
            ['Magang Data Entry (sudah ditutup)', 'magang', -10, false],
            ['Staff Customer Service', 'kerja', 40, true],
            ['Staff Back Office', 'kerja', 25, true],
            ['Marketing Executive', 'kerja', 50, true],
        ];

        foreach ($jobs as [$title, $type, $deadlineDays, $active]) {
            DB::table('job_openings')->insert([
                'created_by' => $adminId,
                'title' => $title,
                'type' => $type,
                'description' => '[Deskripsi pekerjaan '.$title.']',
                'requirements' => "[Persyaratan 1]\n[Persyaratan 2]\n[Persyaratan 3]",
                'deadline' => now()->addDays($deadlineDays)->toDateString(),
                'is_active' => $active,
                'created_at' => $now->copy()->subDays(mt_rand(5, 40)),
                'updated_at' => $now,
            ]);
        }
    }
}
