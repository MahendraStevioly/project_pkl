<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Models\PostgresFeatureTestCase;

class RegistrationTest extends PostgresFeatureTestCase
{
    public function test_registration_creates_a_magang_user_and_empty_profile(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Intern',
            'email' => 'new-intern@example.test',
            'role' => 'magang',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $user = User::query()->where('email', 'new-intern@example.test')->firstOrFail();

        $response->assertRedirect(route('magang.dashboard'));
        $this->assertSame('magang', $user->role);
        $this->assertSame('New Intern', $user->profile->full_name);
        $this->assertNull($user->profile->nik_encrypted);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_creates_a_karyawan_user(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Employee',
            'email' => 'new-employee@example.test',
            'role' => 'karyawan',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirect(route('karyawan.dashboard'));
        $this->assertSame('karyawan', User::query()->where('email', 'new-employee@example.test')->firstOrFail()->role);
    }

    public function test_registration_rejects_privileged_or_unknown_roles(): void
    {
        foreach (['admin', 'operator', 'unknown'] as $index => $role) {
            $email = 'invalid-role-'.$index.'@example.test';

            $this->post(route('register'), [
                'name' => 'Invalid Role',
                'email' => $email,
                'role' => $role,
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ])->assertSessionHasErrors('role');

            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_registration_password_is_hashed(): void
    {
        $this->post(route('register'), [
            'name' => 'Password Test',
            'email' => 'password-test@example.test',
            'role' => 'karyawan',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $user = User::query()->where('email', 'password-test@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('strong-password', $user->password));
    }
}
