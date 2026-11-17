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

    public function test_rejection_message_shows_the_reason_without_a_doubled_period(): void
    {
        $resident = Resident::factory()->withAccount(AccountStatus::Rejected)
            ->create(['verification_status' => 'rejected', 'rejection_reason' => 'ID photo is blurry.']);

        $this->post('/login', ['username' => $resident->user->username, 'password' => 'password'])
            ->assertSessionHasErrors(['username' => 'Your registration was not approved: ID photo is blurry. Please visit the barangay hall or register again with a clearer valid ID.']);
    }

    public function test_login_returns_to_the_page_the_user_was_opening_when_it_is_theirs(): void
    {
        User::factory()->create(['username' => 'frontdesk']);

        $this->get('/service-requests')->assertRedirect('/login');

        $this->post('/login', ['username' => 'frontdesk', 'password' => 'password'])
            ->assertRedirect('/service-requests');
    }

    public function test_login_skips_a_saved_page_that_belongs_to_another_role(): void
    {
        User::factory()->create(['username' => 'frontdesk']);
        $resident = Resident::factory()->withAccount()->create();

        // An Administrator page was open in this browser before Staff logged in.
        $this->get('/admin/service-types')->assertRedirect('/login');
        $this->post('/login', ['username' => 'frontdesk', 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->post('/logout');

        // A Staff page was open before a resident logged in.
        $this->get('/residents')->assertRedirect('/login');
        $this->post('/login', ['username' => $resident->user->username, 'password' => 'password'])
            ->assertRedirect(route('home'));
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
            'password' => 'new-secure-pass-2026',
            'password_confirmation' => 'new-secure-pass-2026',
        ]);

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_users_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
