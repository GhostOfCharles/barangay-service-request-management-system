<?php

namespace Tests\Feature;

use App\Http\Middleware\AddSecurityHeaders;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * Browser security headers (MDN HTTP Observatory): CSP, frame, sniffing, referrer, and HSTS on the live site.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Behave as the built site, even if `npm run dev` happens to be running on this machine.
        Vite::useHotFile(storage_path('framework/testing/no-vite-hot-file'));
    }

    public function test_pages_send_the_security_headers(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('Content-Security-Policy', AddSecurityHeaders::CONTENT_SECURITY_POLICY)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_the_policy_blocks_inline_scripts_and_outside_framing(): void
    {
        $policy = AddSecurityHeaders::CONTENT_SECURITY_POLICY;

        $this->assertStringContainsString("script-src 'self';", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
    }

    public function test_hsts_is_sent_only_in_production(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';

        $this->get('/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_private_files_also_get_the_headers(): void
    {
        Storage::fake();
        Storage::put('valid-ids/id.png', 'png');
        $resident = Resident::factory()->create(['valid_id_path' => 'valid-ids/id.png']);

        $this->actingAs(User::factory()->create())
            ->get("/files/valid-ids/{$resident->id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_pages_no_longer_use_inline_event_handlers(): void
    {
        $views = collect(glob(resource_path('views/**/*.blade.php')) ?: [])
            ->merge(glob(resource_path('views/*/*/*.blade.php')) ?: [])
            ->merge(glob(resource_path('views/*.blade.php')) ?: []);

        foreach ($views as $view) {
            $this->assertDoesNotMatchRegularExpression('/\son(click|submit|change|load|input)=/', file_get_contents($view), $view);
        }
    }
}
