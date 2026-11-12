<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\Resident;
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
        $this->actingAs($staff)->post('/admin/users', ['name' => 'X', 'username' => 'x', 'role' => 'administrator'])->assertForbidden();
        $this->actingAs($staff)->get('/dashboard')->assertDontSee('User Management');
    }

    public function test_administrator_creates_staff_with_a_temporary_password(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->post('/admin/users', ['name' => 'Ana Lim', 'username' => 'ana', 'position' => 'Front Desk', 'role' => 'staff'])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('temporaryPassword');

        $user = User::where('username', 'ana')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isAdministrator());
    }

    public function test_resident_accounts_cannot_be_created_or_edited_here(): void
    {
        $administrator = User::factory()->administrator()->create();
        $resident = Resident::factory()->withAccount()->create();

        $this->actingAs($administrator)
            ->post('/admin/users', ['name' => 'X', 'username' => 'xres', 'role' => 'resident'])
            ->assertSessionHasErrors('role');
        $this->actingAs($administrator)->get("/admin/users/{$resident->user_id}/edit")->assertNotFound();
    }

    public function test_administrator_can_reset_any_password(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs(User::factory()->administrator()->create())
            ->post("/admin/users/{$staff->id}/reset-password")
            ->assertRedirect('/admin/users');

        $temporaryPassword = $response->getSession()->get('temporaryPassword')['password'];
        $staff->refresh();
        $this->assertTrue(Hash::check($temporaryPassword, $staff->password));
        $this->assertTrue($staff->must_change_password);
    }

    public function test_administrator_cannot_deactivate_or_demote_themselves(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->put("/admin/users/{$administrator->id}", ['name' => $administrator->name, 'username' => $administrator->username, 'role' => 'staff', 'is_active' => '1'])
            ->assertSessionHasErrors('role');

        $this->assertTrue($administrator->fresh()->isAdministrator());
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $staff->update(['status' => AccountStatus::Inactive]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
