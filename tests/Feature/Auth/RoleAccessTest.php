<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Feature\Models\PostgresFeatureTestCase;

class RoleAccessTest extends PostgresFeatureTestCase
{
    public function test_role_matrix_restricts_each_dashboard_and_operator_admin_routes(): void
    {
        $routes = [
            'operator' => ['operator.dashboard' => true, 'admin.dashboard' => true, 'magang.dashboard' => false, 'karyawan.dashboard' => false, 'operator.admins.index' => true],
            'admin' => ['operator.dashboard' => false, 'admin.dashboard' => true, 'magang.dashboard' => false, 'karyawan.dashboard' => false, 'operator.admins.index' => false],
            'magang' => ['operator.dashboard' => false, 'admin.dashboard' => false, 'magang.dashboard' => true, 'karyawan.dashboard' => false, 'operator.admins.index' => false],
            'karyawan' => ['operator.dashboard' => false, 'admin.dashboard' => false, 'magang.dashboard' => false, 'karyawan.dashboard' => true, 'operator.admins.index' => false],
        ];

        foreach ($routes as $role => $expectedAccess) {
            $user = User::factory()->create();
            $user->forceFill(['role' => $role, 'status' => 'active'])->save();
            $this->actingAs($user);

            foreach ($expectedAccess as $route => $allowed) {
                $response = $this->get(route($route));

                if ($allowed) {
                    $response->assertOk();
                } else {
                    $response->assertForbidden();
                }
            }
        }
    }

    public function test_dashboard_pages_include_logout_and_security_headers(): void
    {
        $operator = User::factory()->create();
        $operator->forceFill(['role' => 'operator', 'status' => 'active'])->save();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertOk()
            ->assertSee('Operator dashboard')
            ->assertSee('Logout')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_operator_can_create_update_and_delete_admins(): void
    {
        $operator = User::factory()->create();
        $operator->forceFill(['role' => 'operator', 'status' => 'active'])->save();
        $this->actingAs($operator);

        $this->post(route('operator.admins.store'), [
            'name' => 'Created Admin',
            'email' => 'created-admin@example.test',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'role' => 'operator',
        ])->assertRedirect(route('operator.admins.index'));

        $admin = User::query()->where('email', 'created-admin@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertSame((int) $operator->id, (int) $admin->created_by);

        $this->put(route('operator.admins.update', $admin), [
            'name' => 'Updated Admin',
            'email' => $admin->email,
        ])->assertRedirect(route('operator.admins.index'));
        $this->assertSame('Updated Admin', $admin->fresh()->name);
        $this->assertSame('admin', $admin->fresh()->role);

        $this->delete(route('operator.admins.destroy', $admin))
            ->assertRedirect(route('operator.admins.index'));
        $this->assertModelMissing($admin);
    }
}
