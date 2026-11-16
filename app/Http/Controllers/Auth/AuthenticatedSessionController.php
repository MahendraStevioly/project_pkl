<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AuthActivityLogger $activityLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower($credentials['email']);
        $user = User::query()->where('email', $email)->first();

        $authenticated = DB::transaction(function () use ($user, $credentials, $request, $activityLogger): bool {
            $user = $user === null
                ? null
                : User::query()->whereKey($user->getKey())->lockForUpdate()->first();

            if ($user === null) {
                $activityLogger->record(null, 'auth', 'login_failed', $request->ip());

                return false;
            }

            if ($user->status !== 'active') {
                $activityLogger->record($user, 'security', 'login_failed', $request->ip(), [
                    'reason' => 'suspended',
                ]);

                return false;
            }

            if ($user->locked_until !== null && $user->locked_until->isFuture()) {
                $activityLogger->record($user, 'security', 'lockout', $request->ip());

                return false;
            }

            if (! Hash::check($credentials['password'], $user->password)) {
                $failedLoginCount = $user->failed_login_count + 1;
                $lockedUntil = $failedLoginCount >= 5 ? now()->addMinutes(15) : null;

                $user->forceFill([
                    'failed_login_count' => $failedLoginCount,
                    'locked_until' => $lockedUntil,
                ])->save();

                $activityLogger->record($user, 'auth', 'login_failed', $request->ip());

                if ($lockedUntil !== null) {
                    $activityLogger->record($user, 'security', 'lockout', $request->ip());
                }

                return false;
            }

            $user->forceFill([
                'failed_login_count' => 0,
                'locked_until' => null,
            ])->save();

            $activityLogger->record($user, 'auth', 'login_success', $request->ip());

            return true;
        });

        if (! $authenticated || $user === null) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'The provided credentials are invalid or the account is unavailable.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route($this->dashboardRoute($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function dashboardRoute(User $user): string
    {
        return match ($user->role) {
            'operator' => 'operator.dashboard',
            'admin' => 'admin.dashboard',
            'magang' => 'magang.dashboard',
            default => 'karyawan.dashboard',
        };
    }
}
