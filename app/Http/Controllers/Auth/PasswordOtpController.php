<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use App\Services\AuthActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PasswordOtpController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request, AuthActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = mb_strtolower($validated['email']);
        $user = User::query()->where('email', $email)->where('status', 'active')->first();

        if ($user !== null) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::transaction(function () use ($user, $code): void {
                $user->otpCodes()
                    ->where('purpose', 'forgot_password')
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);

                $user->otpCodes()->create([
                    'purpose' => 'forgot_password',
                    'code_hash' => Hash::make($code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(10),
                ]);
            });

            Mail::to($user->email)->send(new PasswordResetOtpMail($code));
        }

        $activityLogger->record($user, 'auth', 'otp_requested', $request->ip());
        $request->session()->put('otp_email', $email);

        return redirect()->route('password.otp.form')
            ->with('status', 'If an active account matches that email, a verification code has been sent.');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-password-otp', [
            'email' => $request->session()->get('otp_email'),
        ]);
    }

    public function verify(Request $request, AuthActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = mb_strtolower($validated['email']);

        /** @var array{status: string, user_id: int|null, user: User|null} $result */
        $result = DB::transaction(function () use ($email, $validated, $request, $activityLogger): array {
            $user = User::query()->where('email', $email)->where('status', 'active')->first();
            $otp = $user?->otpCodes()
                ->where('purpose', 'forgot_password')
                ->whereNull('used_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($user === null || $otp === null) {
                $activityLogger->record($user, 'security', 'otp_invalid', $request->ip());

                return ['status' => 'invalid', 'user_id' => null, 'user' => null];
            }

            if ($otp->expires_at->lessThanOrEqualTo(now())) {
                $activityLogger->record($user, 'security', 'otp_expired', $request->ip());

                return ['status' => 'expired', 'user_id' => null, 'user' => $user];
            }

            if ($otp->attempts >= 5) {
                return ['status' => 'invalid', 'user_id' => null, 'user' => $user];
            }

            if (! Hash::check($validated['code'], $otp->code_hash)) {
                $otp->increment('attempts');
                $activityLogger->record($user, 'security', 'otp_invalid', $request->ip());

                return ['status' => 'invalid', 'user_id' => null, 'user' => $user];
            }

            $otp->forceFill(['used_at' => now()])->save();
            $activityLogger->record($user, 'auth', 'otp_verified', $request->ip());

            return ['status' => 'valid', 'user_id' => (int) $user->getKey(), 'user' => $user];
        });

        if ($result['status'] !== 'valid') {
            return back()->withErrors([
                'code' => $result['status'] === 'expired'
                    ? 'This verification code has expired.'
                    : 'This verification code is invalid or has already been used.',
            ]);
        }

        $request->session()->put('otp_password_reset', [
            'user_id' => $result['user_id'],
            'verified_at' => now()->timestamp,
        ]);
        $request->session()->put('otp_email', $email);

        return redirect()->route('password.reset');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $this->hasValidResetChallenge($request)) {
            $request->session()->forget('otp_password_reset');

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Verify a current code before resetting your password.']);
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request, AuthActivityLogger $activityLogger): RedirectResponse
    {
        if (! $this->hasValidResetChallenge($request)) {
            $request->session()->forget('otp_password_reset');

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Verify a current code before resetting your password.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $challenge = $request->session()->get('otp_password_reset');
        $user = User::query()->findOrFail($challenge['user_id']);

        DB::transaction(function () use ($user, $validated, $activityLogger, $request): void {
            $user->forceFill(['password' => $validated['password']])->save();
            $user->otpCodes()
                ->where('purpose', 'forgot_password')
                ->whereNull('used_at')
                ->update(['used_at' => now()]);
            $activityLogger->record($user, 'security', 'password_reset', $request->ip());
        });

        $request->session()->forget(['otp_password_reset', 'otp_email']);

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in.');
    }

    private function hasValidResetChallenge(Request $request): bool
    {
        $challenge = $request->session()->get('otp_password_reset');

        return is_array($challenge)
            && isset($challenge['user_id'], $challenge['verified_at'])
            && now()->timestamp >= (int) $challenge['verified_at']
            && now()->timestamp - (int) $challenge['verified_at'] <= 600
            && User::query()->whereKey($challenge['user_id'])->where('status', 'active')->exists();
    }
}
