<?php

namespace Tests\Feature\Models;

use App\Models\DashboardDailyStat;
use App\Models\JobOpening;

class JobOpeningTest extends PostgresFeatureTestCase
{
    public function test_open_scope_includes_active_openings_without_expired_deadlines(): void
    {
        $this->travelTo('2026-11-16 12:00:00');

        $withoutDeadline = JobOpening::create([
            'title' => 'No deadline',
            'type' => 'magang',
            'description' => 'Test opening',
            'is_active' => true,
        ]);
        $deadlineToday = JobOpening::create([
            'title' => 'Deadline today',
            'type' => 'kerja',
            'description' => 'Test opening',
            'deadline' => '2026-11-16',
        ]);
        $deadlinePassed = JobOpening::create([
            'title' => 'Expired',
            'type' => 'magang',
            'description' => 'Test opening',
            'deadline' => '2026-11-15',
        ]);
        $inactive = JobOpening::create([
            'title' => 'Inactive',
            'type' => 'kerja',
            'description' => 'Test opening',
            'is_active' => false,
        ]);
        $deadlineFuture = JobOpening::create([
            'title' => 'Future',
            'type' => 'kerja',
            'description' => 'Test opening',
            'deadline' => '2026-11-17',
        ]);
        $dailyStat = DashboardDailyStat::create([
            'stat_date' => '2026-11-16',
            'job_opening_id' => $deadlineToday->id,
            'type' => 'kerja',
        ]);

        $testOpeningIds = [
            $withoutDeadline->id,
            $deadlineToday->id,
            $deadlinePassed->id,
            $inactive->id,
            $deadlineFuture->id,
        ];
        $openIds = JobOpening::open()
            ->whereIn('id', $testOpeningIds)
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $expectedIds = collect([$withoutDeadline, $deadlineToday, $deadlineFuture])
            ->sortBy('id')
            ->map(fn (JobOpening $jobOpening): int => (int) $jobOpening->id)
            ->values()
            ->all();

        $this->assertSame($expectedIds, array_map('intval', $openIds));
        $this->assertTrue($deadlineToday->dailyStats->first()->is($dailyStat));
    }
}
