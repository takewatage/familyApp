<?php

namespace Tests\Feature\Pwa;

use App\Models\Family;
use App\Models\User;
use App\Services\HStorageClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;
use Tests\TestCase;

class FamilyPwaSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_app_name(): void
    {
        [$owner, $family] = $this->ownerWithFamily(['other_key' => 'keep']);

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('family.settings.pwa.update'), ['name' => '山田家アプリ'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = $family->fresh()->settings;
        $this->assertSame('山田家アプリ', $settings['pwa']['name']);
        $this->assertArrayHasKey('updated_at', $settings['pwa']);
        $this->assertSame('keep', $settings['other_key']);
    }

    public function test_non_owner_cannot_update(): void
    {
        [, $family] = $this->ownerWithFamily(null);
        $member = User::factory()->create();
        $family->members()->attach($member->id, ['role' => 'parent']);

        $this->actingAs($member)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('family.settings.pwa.update'), ['name' => '乗っ取り'])
            ->assertForbidden();

        $this->actingAs($member)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('family.settings.pwa.icon.destroy'))
            ->assertForbidden();

        $this->assertNull($family->fresh()->settings);
    }

    public function test_name_is_required_and_max_30(): void
    {
        [$owner, $family] = $this->ownerWithFamily(null);

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('family.settings.pwa.update'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('family.settings.pwa.update'), ['name' => str_repeat('あ', 31)])
            ->assertSessionHasErrors('name');

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('family.settings.pwa.update'), ['name' => str_repeat('あ', 30)])
            ->assertSessionHasNoErrors();
    }

    public function test_icon_upload_saves_four_png_sizes_and_deletes_old_icon(): void
    {
        [$owner, $family] = $this->ownerWithFamily([
            'pwa' => [
                'name' => '旧',
                'icon' => ['external_ids' => ['old-1', 'old-2'], 'apple' => 'a', '192' => 'b', '512' => 'c', 'maskable' => 'd'],
            ],
        ]);

        $uploaded = [];
        $this->mock(HStorageClient::class, function (MockInterface $mock) use (&$uploaded) {
            $mock->shouldReceive('upload')
                ->times(4)
                ->andReturnUsing(function (string $fileContents, string $filename, string $contentType) use (&$uploaded) {
                    $size = getimagesizefromstring($fileContents);
                    $uploaded[] = ['filename' => $filename, 'type' => $contentType, 'mime' => $size['mime'], 'width' => $size[0], 'height' => $size[1]];
                    $n = count($uploaded);

                    return ['external_id' => "new-{$n}", 'direct_url' => "https://example.com/{$n}.png", 'share_url' => ''];
                });
            $mock->shouldReceive('delete')->once()->with('old-1');
            $mock->shouldReceive('delete')->once()->with('old-2');
        });

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('family.settings.pwa.update'), [
                'name' => '山田家アプリ',
                'icon' => UploadedFile::fake()->image('icon.jpg', 800, 600),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([180, 192, 512, 512], array_column($uploaded, 'width'));
        $this->assertSame([180, 192, 512, 512], array_column($uploaded, 'height'));
        $this->assertSame(['image/png'], array_values(array_unique(array_column($uploaded, 'mime'))));
        $this->assertSame(['image/png'], array_values(array_unique(array_column($uploaded, 'type'))));
        foreach ($uploaded as $file) {
            $this->assertStringStartsWith("familyApp/{$family->id}/pwa-icon/", $file['filename']);
        }

        $icon = $family->fresh()->settings['pwa']['icon'];
        $this->assertSame(['new-1', 'new-2', 'new-3', 'new-4'], $icon['external_ids']);
        $this->assertSame('https://example.com/1.png', $icon['apple']);
        $this->assertSame('https://example.com/2.png', $icon['192']);
        $this->assertSame('https://example.com/3.png', $icon['512']);
        $this->assertSame('https://example.com/4.png', $icon['maskable']);
    }

    public function test_icon_must_be_image(): void
    {
        [$owner, $family] = $this->ownerWithFamily(null);

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('family.settings.pwa.update'), [
                'name' => 'A',
                'icon' => UploadedFile::fake()->create('icon.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('icon');
    }

    public function test_destroy_icon_restores_default_and_deletes_images(): void
    {
        [$owner, $family] = $this->ownerWithFamily([
            'pwa' => [
                'name' => '山田家アプリ',
                'icon' => ['external_ids' => ['old-1'], 'apple' => 'a', '192' => 'b', '512' => 'c', 'maskable' => 'd'],
            ],
        ]);

        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('delete')->once()->with('old-1');
        });

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('family.settings.pwa.icon.destroy'))
            ->assertRedirect();

        $pwa = $family->fresh()->settings['pwa'];
        $this->assertArrayNotHasKey('icon', $pwa);
        $this->assertSame('山田家アプリ', $pwa['name']);

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->get('/manifest.webmanifest')
            ->assertJsonPath('icons.0.src', '/icons/icon-192x192.png');
    }

    /**
     * @return array{0: User, 1: Family}
     */
    private function ownerWithFamily(?array $settings): array
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id, 'settings' => $settings]);
        $family->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $family];
    }
}
