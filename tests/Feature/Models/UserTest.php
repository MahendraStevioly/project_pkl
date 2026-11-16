<?php

namespace Tests\Feature\Models;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\InternDetail;
use App\Models\JobOpening;
use App\Models\OtpCode;
use App\Models\Profile;
use App\Models\SiteContent;

class UserTest extends PostgresFeatureTestCase
{
    public function test_role_helpers_match_their_role(): void
    {
        $user = $this->userWithRole('karyawan');

        foreach ([
            'operator' => ['isOperator', true],
            'admin' => ['isAdmin', true],
            'magang' => ['isMagang', true],
            'karyawan' => ['isKaryawan', true],
        ] as $role => [$method, $expected]) {
            $user->role = $role;

            $this->assertSame($expected, $user->{$method}());
        }
    }

    public function test_user_relations_follow_their_foreign_keys(): void
    {
        $creator = $this->userWithRole('operator');
        $user = $this->userWithRole('admin');
        $user->forceFill(['created_by' => $creator->id])->save();

        $profile = Profile::create([
            'user_id' => $user->id,
            'full_name' => 'Test Admin',
        ]);
        $internDetail = InternDetail::create([
            'user_id' => $user->id,
            'university' => 'Test University',
            'major' => 'Management',
        ]);
        $jobOpening = JobOpening::create([
            'created_by' => $creator->id,
            'title' => 'Job',
            'type' => 'kerja',
            'description' => 'Test opening',
        ]);
        $application = Application::create([
            'applicant_id' => $user->id,
            'job_opening_id' => $jobOpening->id,
            'reviewed_by' => $creator->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $otpCode = OtpCode::create([
            'user_id' => $user->id,
            'purpose' => 'login_2fa',
            'code_hash' => 'hashed-code',
            'expires_at' => now()->addMinutes(5),
        ]);
        $activityLog = ActivityLog::create([
            'user_id' => $user->id,
            'category' => 'admin',
            'action' => 'test_action',
            'metadata' => ['source' => 'test'],
        ]);
        $siteContent = SiteContent::create([
            'key' => 'test-user-content',
            'value' => 'Test content',
            'updated_by' => $user->id,
        ]);

        $this->assertTrue($user->profile->is($profile));
        $this->assertTrue($profile->user->is($user));
        $this->assertTrue($user->internDetail->is($internDetail));
        $this->assertTrue($internDetail->user->is($user));
        $this->assertTrue($creator->createdUsers->first()->is($user));
        $this->assertTrue($user->creator->is($creator));
        $this->assertTrue($creator->createdJobOpenings->first()->is($jobOpening));
        $this->assertTrue($jobOpening->creator->is($creator));
        $this->assertTrue($user->applications->first()->is($application));
        $this->assertTrue($creator->reviewedApplications->first()->is($application));
        $this->assertTrue($user->otpCodes->first()->is($otpCode));
        $this->assertTrue($otpCode->user->is($user));
        $this->assertTrue($user->activityLogs->contains($activityLog));
        $this->assertSame(['source' => 'test'], $activityLog->metadata);
        $this->assertTrue($user->siteContents->first()->is($siteContent));
        $this->assertTrue($siteContent->updater->is($user));
    }
}
