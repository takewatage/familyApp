<?php

namespace Tests\Feature\Pwa;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_gets_default_manifest(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('id', '/')
            ->assertJsonPath('name', 'familyApp')
            ->assertJsonPath('short_name', 'familyApp')
            ->assertJsonPath('start_url', '/home')
            ->assertJsonPath('scope', '/')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('theme_color', '#FF45CE')
            ->assertJsonPath('icons.0.src', '/icons/icon-192x192.png')
            ->assertJsonPath('icons.2.purpose', 'maskable');
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    public function test_manifest_reflects_current_family_settings(): void
    {
        [$user, $family] = $this->userWithFamily([
            'pwa' => [
                'name' => '山田家アプリ',
                'icon' => [
                    'external_ids' => ['a', 'b', 'c', 'd'],
                    'apple' => 'https://example.com/apple-180.png',
                    '192' => 'https://example.com/icon-192.png',
                    '512' => 'https://example.com/icon-512.png',
                    'maskable' => 'https://example.com/icon-maskable-512.png',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('id', '/')
            ->assertJsonPath('name', '山田家アプリ')
            ->assertJsonPath('short_name', '山田家アプリ')
            ->assertJsonPath('theme_color', '#FF45CE')
            ->assertJsonPath('icons.0.src', 'https://example.com/icon-192.png')
            ->assertJsonPath('icons.1.src', 'https://example.com/icon-512.png')
            ->assertJsonPath('icons.2.src', 'https://example.com/icon-maskable-512.png');
    }

    public function test_family_without_pwa_settings_uses_defaults(): void
    {
        [$user, $family] = $this->userWithFamily(null);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('name', 'familyApp')
            ->assertJsonPath('icons.0.src', '/icons/icon-192x192.png');
    }

    public function test_blade_head_and_shared_props_use_family_settings(): void
    {
        [$user, $family] = $this->userWithFamily(['pwa' => ['name' => '山田家アプリ']]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('home'));

        $response->assertOk()
            ->assertSee('<meta name="apple-mobile-web-app-title" content="山田家アプリ">', false)
            ->assertSee('crossorigin="use-credentials"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('pwa.name', '山田家アプリ')
                ->where('pwa.iconApple', '/icons/apple-touch-icon.png')
                ->where('pwa.isCustomIcon', false)
                ->has('pwa.manifestHash'));
    }

    public function test_manifest_hash_changes_when_settings_change(): void
    {
        [$user, $family] = $this->userWithFamily(['pwa' => ['name' => 'A']]);

        $first = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('home'))
            ->viewData('page')['props']['pwa']['manifestHash'];

        $family->update(['settings' => ['pwa' => ['name' => 'B']]]);

        $second = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('home'))
            ->viewData('page')['props']['pwa']['manifestHash'];

        $this->assertNotSame($first, $second);
    }

    /**
     * @return array{0: User, 1: Family}
     */
    private function userWithFamily(?array $settings): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $user->id, 'settings' => $settings]);
        $family->members()->attach($user->id, ['role' => 'owner']);

        return [$user, $family];
    }
}
