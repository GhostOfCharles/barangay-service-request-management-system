<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($staff)->post('/admin/users', ['name' => 'X', 'username' => 'x', 'role' => 'admin'])->assertForbidden();
        $this->actingAs($staff)->get('/dashboard')->assertDontSee('User Management');
    }

    public function test_admin_can_create_a_user_with_a_temporary_password(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/users', ['name' => 'Ana Lim', 'username' => 'ana', 'position' => 'Front Desk', 'role' => 'staff'])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('temporaryPassword');

        $user = User::where('username', 'ana')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_can_reset_a_password(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/users/{$staff->id}/reset-password")
            ->assertRedirect('/admin/users');

        $temporaryPassword = $response->getSession()->get('temporaryPassword')['password'];
        $staff->refresh();
        $this->assertTrue(Hash::check($temporaryPassword, $staff->password));
        $this->assertTrue($staff->must_change_password);
    }

    public function test_admin_cannot_deactivate_or_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put("/admin/users/{$admin->id}", ['name' => $admin->name, 'username' => $admin->username, 'role' => 'staff', 'is_active' => '1'])
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $staff->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
