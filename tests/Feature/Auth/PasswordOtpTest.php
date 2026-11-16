<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetOtpMail;
use App\Models\ActivityLog;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Models\PostgresFeatureTestCase;

class PasswordOtpTest extends PostgresFeatureTestCase
{
    public function test_valid_otp_resets_password_and_is_consumed_once(): void
    {
        $this->freezeTime();
        Mail::fake();
        $user = User::factory()->create(['email' => 'otp-valid@example.test']);

        $this->post(route('password.otp.send'), ['email' => $user->email])
            ->assertRedirect(route('password.otp.form'));
        $otp = OtpCode::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNotSame('123456', $otp->code_hash);
        $this->assertSame(now()->addMinutes(10)->timestamp, $otp->expires_at->timestamp);

        $code = null;
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use ($user, &$code): bool {
            $code = $mail->code;

            return $mail->hasTo($user->email) && preg_match('/^\d{6}$/', $code) === 1;
        });
        $this->assertTrue(Hash::check($code, $otp->code_hash));

        $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('password.reset'));

        $otp->refresh();
        $this->assertNotNull($otp->used_at);

        $this->post(route('password.update'), [
            'password' => 'a-new-strong-password',
            'password_confirmation' => 'a-new-strong-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('a-new-strong-password', $user->fresh()->password));
        $this->assertSame(1, ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'password_reset')
            ->count());
        $this->assertGuest();

        $this->withSession(['otp_email' => $user->email]);
        $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertSessionHasErrors('code');
    }

    public function test_expired_otp_is_rejected(): void
    {
        Mail::fake();
        $this->travelTo('2026-11-16 12:00:00');
        $user = User::factory()->create(['email' => 'otp-expired@example.test']);
        $this->post(route('password.otp.send'), ['email' => $user->email]);
        $mail = Mail::sent(PasswordResetOtpMail::class)->first();
        $this->assertNotNull($mail);
        $code = $mail->code;

        $this->travel(11)->minutes();
        $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertSessionHasErrors('code');

        $this->assertSame(0, (int) OtpCode::query()->where('user_id', $user->id)->value('attempts'));
        $this->assertSame(1, ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'otp_expired')
            ->count());
    }

    public function test_wrong_otp_is_limited_to_five_attempts(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'otp-wrong@example.test']);
        $this->post(route('password.otp.send'), ['email' => $user->email]);
        $mail = Mail::sent(PasswordResetOtpMail::class)->first();
        $this->assertNotNull($mail);
        $code = $mail->code;
        $wrongCode = str_pad((string) (((int) $code + 1) % 1000000), 6, '0', STR_PAD_LEFT);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.otp.verify'), [
                'email' => $user->email,
                'code' => $wrongCode,
            ])->assertSessionHasErrors('code');
        }

        $otp = OtpCode::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(5, (int) $otp->attempts);
        $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => $wrongCode,
        ])->assertSessionHasErrors('code');
        $this->assertSame(5, (int) $otp->fresh()->attempts);
    }

    public function test_password_reset_otp_requests_are_rate_limited(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'otp-throttle@example.test']);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('password.otp.send'), ['email' => $user->email])
                ->assertRedirect(route('password.otp.form'));
        }

        $this->post(route('password.otp.send'), ['email' => $user->email])
            ->assertTooManyRequests();

        Mail::assertSentTimes(PasswordResetOtpMail::class, 3);
    }

    public function test_unknown_email_gets_a_generic_password_reset_response_without_mail(): void
    {
        Mail::fake();

        $this->post(route('password.otp.send'), ['email' => 'unknown@example.test'])
            ->assertRedirect(route('password.otp.form'))
            ->assertSessionHas('status');

        Mail::assertNothingSent();
    }
}
