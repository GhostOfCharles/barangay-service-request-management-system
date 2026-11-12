<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-01: one login page for three roles; inactive accounts are refused; each role sees only its own menus.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/my')->assertRedirect('/login');
    }

    public function test_staff_land_on_the_staff_dashboard(): void
    {
        User::factory()->create(['username' => 'frontdesk']);

        $this->post('/login', ['username' => 'frontdesk', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_residents_land_on_the_resident_dashboard(): void
    {
        $resident = Resident::factory()->withAccount()->create();

        $this->post('/login', ['username' => $resident->user->username, 'password' => 'password']);
        $this->get('/')->assertRedirect(route('resident.dashboard'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['username' => 'frontdesk']);

        $this->from('/login')
            ->post('/login', ['username' => 'frontdesk', 'password' => 'wrong'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_pending_resident_sees_the_pending_verification_message(): void
    {
        $resident = Resident::factory()->pending()->withAccount(AccountStatus::Pending)->create();

        $this->post('/login', ['username' => $resident->user->username, 'password' => 'password'])
            ->assertSessionHasErrors(['username' => 'Your account is pending verification. Barangay staff will check your valid ID before you can log in.']);

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->status(AccountStatus::Inactive)->create(['username' => 'former']);

        $this->post('/login', ['username' => 'former', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_roles_only_reach_their_own_screens(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $staff = User::factory()->create();

        $this->actingAs($resident->user)->get('/dashboard')->assertForbidden();
        $this->actingAs($resident->user)->get('/residents')->assertForbidden();
        $this->actingAs($staff)->get('/my')->assertForbidden();
        $this->actingAs($staff)->get('/admin/service-types')->assertForbidden();
    }

    public function test_temporary_password_forces_a_password_change(): void
    {
        $user = User::factory()->mustChangePassword()->create(['username' => 'newstaff']);

        $this->post('/login', ['username' => 'newstaff', 'password' => 'password'])
            ->assertRedirect(route('password.edit'));
        $this->get('/dashboard')->assertRedirect(route('password.edit'));

        $this->put('/password', [
            'current_password' => 'password',
            'password' => 'new-secure-pass',
            'password_confirmation' => 'new-secure-pass',
        ]);

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_users_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
