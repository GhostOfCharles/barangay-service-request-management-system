<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FR-09: resident self-registration with a valid ID and privacy consent, then verification by Staff.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function registration(array $overrides = []): array
    {
        return [
            'full_name' => 'Flores, Cynthia M.',
            'contact_number' => '0917 111 2233',
            'address' => '12 Rizal St.',
            'purok' => 'Purok 1',
            'username' => 'cynthia',
            'password' => 'secret-pass',
            'valid_id' => UploadedFile::fake()->image('id_front.jpg'),
            'privacy_consent' => '1',
            ...$overrides,
        ];
    }

    public function test_resident_registers_and_waits_for_verification(): void
    {
        Storage::fake('local');

        $this->post('/register', $this->registration())->assertRedirect('/login');

        $user = User::where('username', 'cynthia')->firstOrFail();
        $this->assertSame(UserRole::Resident, $user->role);
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertSame(VerificationStatus::Pending, $user->resident->verification_status);
        $this->assertNotNull($user->resident->privacy_consent_at);
        Storage::disk('local')->assertExists($user->resident->valid_id_path);
    }

    public function test_registration_requires_consent_and_a_valid_id_file(): void
    {
        Storage::fake('local');

        $this->post('/register', $this->registration([
            'privacy_consent' => null,
            'valid_id' => UploadedFile::fake()->create('id.docx', 100),
        ]))->assertSessionHasErrors(['privacy_consent', 'valid_id']);

        $this->post('/register', $this->registration([
            'valid_id' => UploadedFile::fake()->create('id.pdf', 6000, 'application/pdf'),
        ]))->assertSessionHasErrors(['valid_id' => 'The valid ID must not be larger than 5 MB.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_staff_approve_activates_the_account(): void
    {
        $resident = Resident::factory()->pending()->withAccount(AccountStatus::Pending)->create();
        $staff = User::factory()->create();

        $this->actingAs($staff)->post("/residents/{$resident->id}/approve")->assertRedirect();

        $resident->refresh();
        $this->assertSame(VerificationStatus::Verified, $resident->verification_status);
        $this->assertSame($staff->id, $resident->verified_by);
        $this->assertSame(AccountStatus::Active, $resident->user->status);
    }

    public function test_reject_needs_a_reason_and_keeps_the_account_inactive(): void
    {
        $resident = Resident::factory()->pending()->withAccount(AccountStatus::Pending)->create();
        $staff = User::factory()->create();

        $this->actingAs($staff)->post("/residents/{$resident->id}/reject", ['rejection_reason' => ''])
            ->assertSessionHasErrorsIn('rejectResident', 'rejection_reason');

        $this->actingAs($staff)->post("/residents/{$resident->id}/reject", ['rejection_reason' => 'ID photo is unreadable']);

        $resident->refresh();
        $this->assertSame(VerificationStatus::Rejected, $resident->verification_status);
        $this->assertSame(AccountStatus::Rejected, $resident->user->status);
    }

    public function test_staff_can_review_the_uploaded_id_but_other_residents_cannot(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('valid-ids/sample-id.png', 'image');
        $pending = Resident::factory()->pending()->withAccount(AccountStatus::Pending)->create();
        $otherResident = Resident::factory()->withAccount()->create();

        $this->actingAs(User::factory()->create())->get("/files/valid-ids/{$pending->id}")->assertOk();
        $this->actingAs($otherResident->user)->get("/files/valid-ids/{$pending->id}")->assertForbidden();
    }
}
