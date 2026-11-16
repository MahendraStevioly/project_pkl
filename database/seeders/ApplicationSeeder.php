<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplicationSeeder extends Seeder
{
    private const ROMAN = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public function run(): void
    {
        mt_srand(2027);

        $admins = DB::table('users')->where('role', 'admin')->pluck('id')->all();
        $jobsByType = DB::table('job_openings')->orderBy('id')->get()->groupBy('type');
        $applicants = DB::table('users')->whereIn('role', ['magang', 'karyawan'])->orderBy('id')->get();
        $templates = DB::table('document_templates')->pluck('id', 'type');

        $statusCycle = ['submitted', 'in_review', 'interview', 'accepted', 'rejected', 'accepted', 'rejected', 'submitted', 'draft', 'accepted'];
        $divisions = ['Back Office', 'Customer Service', 'Marketing', 'IT Support'];

        $histories = [];
        $logs = [];
        $i = 0;
        $docCounter = 0;
        $requestedDemoDone = false;

        // Trigger dimatikan selama seeding supaya riwayat/log bisa diberi tanggal mundur.
        DB::unprepared('ALTER TABLE applications DISABLE TRIGGER trg_application_status_history');

        try {
            foreach ($applicants as $user) {
                $type = $user->role === 'magang' ? 'magang' : 'kerja';
                $jobList = $jobsByType[$type]->values();

                $picked = [mt_rand(0, $jobList->count() - 1)];
                if ($i % 3 === 0 && $jobList->count() > 1) {
                    $picked[] = ($picked[0] + 1) % $jobList->count();   // sebagian user melamar 2 lowongan
                }

                foreach ($picked as $idx) {
                    $job = $jobList[$idx];
                    $status = $statusCycle[$i % count($statusCycle)];
                    $i++;
                    $adminId = $admins[mt_rand(0, count($admins) - 1)];

                    $submittedAt = $status === 'draft' ? null : now()->subDays(mt_rand(3, 150))->subMinutes(mt_rand(0, 1000));
                    $decided = in_array($status, ['accepted', 'rejected'], true);
                    $reviewedAt = $decided ? $submittedAt->copy()->addDays(mt_rand(1, 10)) : null;
                    if ($reviewedAt && $reviewedAt->isFuture()) {
                        $reviewedAt = now()->subHour();
                    }
                    $reviewed = in_array($status, ['in_review', 'interview', 'accepted', 'rejected'], true);
                    $note = match ($status) {
                        'accepted' => 'Memenuhi kriteria.',
                        'rejected' => 'Belum sesuai kebutuhan saat ini.',
                        default => null,
                    };
                    $createdAt = $submittedAt ? $submittedAt->copy()->subHours(2) : now()->subDays(mt_rand(1, 20));

                    $applicationId = DB::table('applications')->insertGetId([
                        'applicant_id' => $user->id,
                        'job_opening_id' => $job->id,
                        'status' => $status,
                        'reviewed_by' => $reviewed ? $adminId : null,
                        'admin_note' => $note,
                        'submitted_at' => $submittedAt,
                        'reviewed_at' => $reviewedAt,
                        'created_at' => $createdAt,
                        'updated_at' => $reviewedAt ?? $submittedAt ?? $createdAt,
                    ]);

                    // ---- riwayat status + log aktivitas (meniru yang dibuat trigger) ----
                    $steps = [];
                    if ($status === 'draft') {
                        $steps[] = [$user->id, null, 'draft', null, $createdAt];
                    } else {
                        $steps[] = [$user->id, null, 'submitted', null, $submittedAt];
                        if (in_array($status, ['in_review', 'interview'], true)) {
                            $steps[] = [$adminId, 'submitted', 'in_review', null, $submittedAt->copy()->addDay()];
                        }
                        if ($status === 'interview') {
                            $steps[] = [$adminId, 'in_review', 'interview', null, $submittedAt->copy()->addDays(3)];
                        }
                        if ($decided) {
                            $steps[] = [$adminId, 'submitted', $status, $note, $reviewedAt];
                        }
                    }
                    foreach ($steps as [$actor, $from, $to, $stepNote, $at]) {
                        $histories[] = [
                            'application_id' => $applicationId, 'changed_by' => $actor,
                            'from_status' => $from, 'to_status' => $to, 'note' => $stepNote,
                            'created_at' => $at, 'updated_at' => $at,
                        ];
                        $logs[] = [
                            'user_id' => $actor, 'category' => 'application',
                            'action' => $from === null ? 'application_created' : 'application_status_changed',
                            'entity_type' => 'applications', 'entity_id' => $applicationId,
                            'ip_address' => '10.0.0.'.mt_rand(2, 200),
                            'metadata' => json_encode(['from' => $from, 'to' => $to]),
                            'created_at' => $at,
                        ];
                    }

                    // ---- berkas pendaftaran (path DUMMY, file asli belum ada) ----
                    if ($submittedAt) {
                        $docTypes = $type === 'magang' ? ['cv', 'ktp', 'transkrip'] : ['cv', 'ktp'];
                        foreach ($docTypes as $docType) {
                            DB::table('application_documents')->insert([
                                'application_id' => $applicationId,
                                'doc_type' => $docType,
                                'file_path' => "applications/{$applicationId}/{$docType}.pdf",
                                'original_name' => "{$docType}.pdf",
                                'created_at' => $submittedAt,
                                'updated_at' => $submittedAt,
                            ]);
                        }
                    }

                    // ---- magang diterima: penempatan + dokumen LoA/sertifikat ----
                    if ($status === 'accepted' && $type === 'magang') {
                        $start = $reviewedAt->copy()->addDays(7);
                        $end = $start->copy()->addDays(90);
                        $placementId = DB::table('intern_placements')->insertGetId([
                            'application_id' => $applicationId,
                            'division' => $divisions[mt_rand(0, count($divisions) - 1)],
                            'mentor_name' => '[Nama mentor]',
                            'start_date' => $start->toDateString(),
                            'end_date' => $end->toDateString(),
                            'status' => $end->isPast() ? 'completed' : 'active',
                            'created_at' => $reviewedAt,
                            'updated_at' => $reviewedAt,
                        ]);

                        $docs = [['loa', 'issued', $reviewedAt->copy()->addDay()]];
                        if ($end->isPast()) {
                            $docs[] = ['sertifikat', 'issued', $end->copy()->addDay()->min(now())];
                        } elseif (! $requestedDemoDone) {
                            $docs[] = ['sertifikat', 'requested', null];   // contoh pengajuan yang menunggu admin
                            $requestedDemoDone = true;
                        }

                        foreach ($docs as [$docType, $docStatus, $issuedAt]) {
                            $issued = $docStatus === 'issued';
                            $label = $docType === 'loa' ? 'LoA' : 'SERT';
                            DB::table('issued_documents')->insert([
                                'placement_id' => $placementId,
                                'template_id' => $templates[$docType] ?? null,
                                'issued_by' => $issued ? $adminId : null,
                                'type' => $docType,
                                'doc_number' => $issued
                                    ? sprintf('%03d/%s/EWF-SBY/%s/%d', ++$docCounter, $label, self::ROMAN[$issuedAt->month], $issuedAt->year)
                                    : null,
                                'status' => $docStatus,
                                'file_path' => $issued ? "documents/{$placementId}-{$docType}.pdf" : null,
                                'requested_at' => $issuedAt?->copy()->subDay() ?? now()->subDay(),
                                'issued_at' => $issuedAt,
                                'created_at' => $issuedAt ?? now()->subDay(),
                                'updated_at' => $issuedAt ?? now()->subDay(),
                            ]);
                            if ($issued) {
                                $logs[] = [
                                    'user_id' => $adminId, 'category' => 'document', 'action' => 'document_issued',
                                    'entity_type' => 'issued_documents', 'entity_id' => $placementId,
                                    'ip_address' => '10.0.0.'.mt_rand(2, 200),
                                    'metadata' => json_encode(['type' => $docType]),
                                    'created_at' => $issuedAt,
                                ];
                            }
                        }
                    }
                }
            }

            foreach (array_chunk($histories, 100) as $chunk) {
                DB::table('application_status_history')->insert($chunk);
            }
            foreach (array_chunk($logs, 100) as $chunk) {
                DB::table('activity_logs')->insert($chunk);
            }
        } finally {
            DB::unprepared('ALTER TABLE applications ENABLE TRIGGER trg_application_status_history');
        }
    }
}
