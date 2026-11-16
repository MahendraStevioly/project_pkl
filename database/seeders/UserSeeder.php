<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private string $passwordHash;

    public function run(): void
    {
        mt_srand(2026);
        $this->passwordHash = Hash::make('password');
        $now = now();

        // 1 operator (super admin)
        $operatorId = $this->createUser('Operator EWF', 'operator@ewf.test', 'operator', null);
        $this->createProfile($operatorId, 'Operator EWF');

        // 2 admin (dibuat oleh operator)
        foreach ([['Admin Satu', 'admin1@ewf.test'], ['Admin Dua', 'admin2@ewf.test']] as [$name, $email]) {
            $id = $this->createUser($name, $email, 'admin', $operatorId);
            $this->createProfile($id, $name);
        }

        // 10 pelamar magang
        $magang = [
            'Aditya Pratama', 'Bunga Lestari', 'Cahya Nugroho', 'Dewi Anggraini', 'Eko Prasetyo',
            'Fitri Handayani', 'Gilang Ramadhan', 'Hana Safitri', 'Indra Wijaya', 'Julia Kusuma',
        ];
        $universities = ['Universitas Airlangga', 'Institut Teknologi Sepuluh Nopember', 'Universitas Negeri Surabaya', 'Universitas Surabaya', 'UPN Veteran Jawa Timur'];
        $majors = ['Manajemen', 'Akuntansi', 'Sistem Informasi', 'Ilmu Komunikasi', 'Teknik Informatika'];

        foreach ($magang as $i => $name) {
            $email = 'magang'.($i + 1).'@ewf.test';
            $id = $this->createUser($name, $email, 'magang', null);
            $this->createProfile($id, $name, 2002 + ($i % 3), 'S1 (sedang menempuh)');
            DB::table('intern_details')->insert([
                'user_id' => $id,
                'university' => $universities[$i % count($universities)],
                'major' => $majors[$i % count($majors)],
                'student_id_number' => sprintf('%02d%08d', 20 + ($i % 3), mt_rand(0, 99999999)),
                'semester' => 5 + ($i % 4),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 8 pelamar karyawan
        $karyawan = [
            'Kurniawan Hadi', 'Lestari Putri', 'Mahendra Saputra', 'Nadia Permata',
            'Oktavian Reza', 'Putri Maharani', 'Rizky Firmansyah', 'Sari Wulandari',
        ];
        foreach ($karyawan as $i => $name) {
            $id = $this->createUser($name, 'karyawan'.($i + 1).'@ewf.test', 'karyawan', null);
            $this->createProfile($id, $name, 1993 + ($i % 7), 'S1');
        }
    }

    private function createUser(string $name, string $email, string $role, ?int $createdBy): int
    {
        $now = now();

        return DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => $now,
            'password' => $this->passwordHash,
            'role' => $role,
            'status' => 'active',
            'created_by' => $createdBy,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createProfile(int $userId, string $name, ?int $birthYear = null, ?string $education = null): void
    {
        $now = now();
        $row = [
            'user_id' => $userId,
            'full_name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($birthYear) {
            // NIK & no. HP DUMMY. Dienkripsi (dua arah); nik_hash = blind index untuk cek duplikat.
            $nik = '3578'.sprintf('%06d%06d', mt_rand(0, 999999), mt_rand(0, 999999));
            $phone = '08'.sprintf('%010d', mt_rand(0, 999999999));

            $row += [
                'nik_encrypted' => Crypt::encryptString($nik),
                'nik_hash' => hash_hmac('sha256', $nik, config('app.key')),
                'phone_encrypted' => Crypt::encryptString($phone),
                'birth_place' => 'Surabaya',
                'birth_date' => Carbon::create($birthYear, mt_rand(1, 12), mt_rand(1, 28))->toDateString(),
                'address' => 'Jl. Contoh No. '.mt_rand(1, 99).', Surabaya',
                'last_education' => $education,
            ];
        }

        DB::table('profiles')->insert($row);
    }
}
