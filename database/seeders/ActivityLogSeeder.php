<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menambah log aktivitas umum (login, keamanan, aksi admin) selama 60 hari terakhir
 * supaya grafik "penggunaan" di dashboard operator tidak kosong.
 */
class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2028);

        $roles = DB::table('users')->pluck('role', 'id')->all();
        $allIds = array_keys($roles);
        $adminIds = array_keys(array_filter($roles, fn ($r) => in_array($r, ['admin', 'operator'], true)));
        $internIds = array_keys(array_filter($roles, fn ($r) => $r === 'magang'));

        $rows = [];
        for ($n = 0; $n < 250; $n++) {
            $at = now()->subDays(mt_rand(0, 60))->subMinutes(mt_rand(0, 1439));
            $roll = mt_rand(1, 100);

            if ($roll <= 55) {
                [$user, $cat, $action, $entity, $meta] = [$allIds[array_rand($allIds)], 'auth', 'login_success', null, null];
            } elseif ($roll <= 70) {
                [$user, $cat, $action, $entity, $meta] = [null, 'auth', 'login_failed', null, ['reason' => 'invalid_credentials']];
            } elseif ($roll <= 80) {
                [$user, $cat, $action, $entity, $meta] = [$allIds[array_rand($allIds)], 'security', ['otp_requested', 'otp_failed', 'password_reset'][mt_rand(0, 2)], null, null];
            } elseif ($roll <= 92) {
                [$user, $cat, $action, $entity, $meta] = [$adminIds[array_rand($adminIds)], 'admin', ['job_created', 'job_updated', 'applicant_viewed'][mt_rand(0, 2)], 'job_openings', null];
            } else {
                [$user, $cat, $action, $entity, $meta] = [$internIds[array_rand($internIds)], 'document', 'document_downloaded', 'issued_documents', null];
            }

            $rows[] = [
                'user_id' => $user,
                'category' => $cat,
                'action' => $action,
                'entity_type' => $entity,
                'entity_id' => null,
                'ip_address' => '10.0.0.'.mt_rand(2, 200),
                'metadata' => $meta ? json_encode($meta) : null,
                'created_at' => $at,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('activity_logs')->insert($chunk);
        }
    }
}
