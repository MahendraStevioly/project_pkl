<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Tests\Feature\Models\PostgresFeatureTestCase;

class LoginSecurityTest extends PostgresFeatureTestCase
{
    public function test_five_consecutive_failures_lock_the_account_for_fifteen_minutes_and_are_logged(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        $user = User::factory()->create(['email' => 'lockout@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $user->refresh();
        $this->assertSame(5, (int) $user->failed_login_count);
        $this->assertSame(now()->addMinutes(15)->timestamp, $user->locked_until->timestamp);
        $this->assertSame(5, ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'login_failed')
            ->count());
        $this->assertSame(1, ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'lockout')
            ->count());
        $this->assertNotEmpty(ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'lockout')
            ->value('ip_address'));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_successful_login_resets_failures_and_redirects_by_role(): void
    {
        $user = User::factory()->create(['email' => 'login-success@example.test']);
        $user->forceFill([
            'role' => 'magang',
            'failed_login_count' => 3,
            'locked_until' => now()->subMinute(),
        ])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('magang.dashboard'));

        $user->refresh();
        $this->assertSame(0, (int) $user->failed_login_count);
        $this->assertNull($user->locked_until);
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'login_success')
            ->count());
        $this->assertNotEmpty(ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'login_success')
            ->value('ip_address'));
    }

    public function test_login_redirects_each_role_to_its_dashboard(): void
    {
        foreach ([
            'operator' => 'operator.dashboard',
            'admin' => 'admin.dashboard',
            'magang' => 'magang.dashboard',
            'karyawan' => 'karyawan.dashboard',
        ] as $index => $route) {
            $user = User::factory()->create(['email' => 'redirect-'.$index.'@example.test']);
            $user->forceFill(['role' => $index, 'status' => 'active'])->save();

            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route($route));

            $this->post(route('logout'));
        }
    }

    public function test_suspended_users_cannot_login_even_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'suspended@example.test']);
        $user->forceFill(['status' => 'suspended'])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, (int) $user->fresh()->failed_login_count);
        $this->assertSame(1, ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('category', 'security')
            ->where('action', 'login_failed')
            ->count());
    }

    public function test_login_failed_for_unknown_email_is_logged_without_account_data(): void
    {
        $this->post(route('login'), [
            'email' => 'missing@example.test',
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $log = ActivityLog::query()->where('action', 'login_failed')->firstOrFail();
        $this->assertNull($log->user_id);
        $this->assertNotEmpty($log->ip_address);
    }
}
