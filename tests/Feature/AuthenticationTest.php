<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/residents')->assertRedirect('/login');
    }

    public function test_staff_can_log_in_with_username_and_password(): void
    {
        $user = User::factory()->create(['username' => 'frontdesk']);

        $this->post('/login', ['username' => 'frontdesk', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['username' => 'frontdesk']);

        $this->from('/login')
            ->post('/login', ['username' => 'frontdesk', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['username' => 'former']);

        $this->post('/login', ['username' => 'former', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
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
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->get('/dashboard')->assertOk();
    }

    public function test_users_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
