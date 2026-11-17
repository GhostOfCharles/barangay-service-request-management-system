<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `php artisan demo:setup` prepares a deployed site for the professor's evaluation.
 */
class DemoSetupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_demo_data_beside_existing_records_with_random_passwords(): void
    {
        Storage::fake();
        $this->seed(DatabaseSeeder::class);
        $existing = Resident::factory()->withAccount()->create(['full_name' => 'Real Test Account']);

        $this->artisan('demo:setup')
            ->expectsOutputToContain('Copy these passwords now')
            ->assertSuccessful();

        foreach (DemoDataSeeder::DEMO_USERNAMES as $username) {
            $user = User::where('username', $username)->firstOrFail();
            $this->assertFalse(Hash::check('password', $user->password), "{$username} still uses the default password");
        }

        $this->assertSame(AccountStatus::Pending, User::where('username', 'ramon')->first()->status);
        $this->assertSame(7, ServiceRequest::count());
        $this->assertSame(3, Ticket::count());
        $this->assertTrue($existing->fresh()->is($existing), 'The existing resident record is untouched');
    }

    public function test_running_it_again_changes_nothing(): void
    {
        Storage::fake();
        $this->seed(DatabaseSeeder::class);
        $this->artisan('demo:setup')->assertSuccessful();
        $passwordHash = User::where('username', 'staff')->first()->password;

        $this->artisan('demo:setup')
            ->expectsOutputToContain('already set up')
            ->assertSuccessful();

        $this->assertSame(7, ServiceRequest::count());
        $this->assertSame($passwordHash, User::where('username', 'staff')->first()->password);
    }
}
