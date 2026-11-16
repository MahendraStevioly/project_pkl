<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Prototipe ETL: Extract (applications + job_openings) -> Transform (agregasi harian)
 * -> Load (dashboard_daily_stats), lalu catat di etl_runs.
 * Logika yang sama nanti dipindah ke `php artisan etl:run`.
 */
class DashboardStatsSeeder extends Seeder
{
    public function run(): void
    {
        $startedAt = now();

        $rows = DB::affectingStatement(<<<'SQL'
INSERT INTO dashboard_daily_stats
    (stat_date, job_opening_id, type, submitted, accepted, rejected, avg_review_days, created_at, updated_at)
SELECT a.submitted_at::date,
       a.job_opening_id,
       j.type,
       COUNT(*),
       COUNT(*) FILTER (WHERE a.status = 'accepted'),
       COUNT(*) FILTER (WHERE a.status = 'rejected'),
       ROUND((AVG(EXTRACT(EPOCH FROM (a.reviewed_at - a.submitted_at)) / 86400)
              FILTER (WHERE a.reviewed_at IS NOT NULL))::numeric, 2),
       now(), now()
FROM applications a
JOIN job_openings j ON j.id = a.job_opening_id
WHERE a.submitted_at IS NOT NULL
GROUP BY a.submitted_at::date, a.job_opening_id, j.type
ON CONFLICT (stat_date, job_opening_id) DO UPDATE SET
    submitted = EXCLUDED.submitted,
    accepted = EXCLUDED.accepted,
    rejected = EXCLUDED.rejected,
    avg_review_days = EXCLUDED.avg_review_days,
    updated_at = now()
SQL);

        DB::table('etl_runs')->insert([
            'started_at' => $startedAt,
            'finished_at' => now(),
            'rows_loaded' => $rows,
            'status' => 'success',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::unprepared('REFRESH MATERIALIZED VIEW mv_monthly_applications');
    }
}
