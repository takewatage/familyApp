<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use App\Services\HStorageClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class FamilyBannerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->family = Family::factory()->create([
            'owner_id' => $this->owner->id,
            'settings' => ['pwa' => ['name' => 'うちのアプリ']],
        ]);
        $this->family->members()->attach($this->owner->id, ['role' => 'owner']);
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner)->withSession(['current_family_id' => $this->family->id]);
    }

    public function test_owner_can_upload_banner_and_it_is_shown_on_home(): void
    {
        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('upload')->once()->andReturnUsing(function (string $contents, string $filename) {
                $size = getimagesizefromstring($contents);
                $this->assertSame('image/webp', $size['mime']);
                // 横幅 1200px に縮小して保存する
                $this->assertSame(1200, $size[0]);
                $this->assertStringStartsWith("familyApp/{$this->family->id}/banner/", $filename);

                return ['external_id' => 'banner-1', 'direct_url' => 'https://example.com/banner-1.webp', 'share_url' => ''];
            });
        });

        $this->asOwner()
            ->post(route('family.settings.banner.update'), ['banner' => UploadedFile::fake()->image('banner.jpg', 2400, 800)])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = $this->family->fresh()->settings;
        $this->assertSame('https://example.com/banner-1.webp', $settings['banner']['url']);
        // 他の家族設定（PWA）は消えない
        $this->assertSame('うちのアプリ', $settings['pwa']['name']);

        $this->asOwner()->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('bannerUrl', 'https://example.com/banner-1.webp'));
        $this->asOwner()->get(route('family.settings.index'))
            ->assertInertia(fn (Assert $page) => $page->where('bannerUrl', 'https://example.com/banner-1.webp'));
    }

    public function test_replacing_banner_deletes_the_old_image_after_saving(): void
    {
        $this->family->update(['settings' => ['banner' => ['external_id' => 'old', 'url' => 'https://example.com/old.webp']]]);

        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('upload')->once()->andReturn(['external_id' => 'new', 'direct_url' => 'https://example.com/new.webp', 'share_url' => '']);
            $mock->shouldReceive('delete')->once()->with('old');
        });

        $this->asOwner()
            ->post(route('family.settings.banner.update'), ['banner' => UploadedFile::fake()->image('banner.png', 1200, 400)])
            ->assertSessionHasNoErrors();

        $this->assertSame('https://example.com/new.webp', $this->family->fresh()->settings['banner']['url']);
    }

    public function test_owner_can_delete_banner(): void
    {
        $this->family->update(['settings' => ['pwa' => ['name' => 'うちのアプリ'], 'banner' => ['external_id' => 'old', 'url' => 'https://example.com/old.webp']]]);

        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('delete')->once()->with('old');
        });

        $this->asOwner()->delete(route('family.settings.banner.destroy'))->assertRedirect();

        $settings = $this->family->fresh()->settings;
        $this->assertArrayNotHasKey('banner', $settings);
        $this->assertSame('うちのアプリ', $settings['pwa']['name']);

        $this->asOwner()->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('bannerUrl', null));
    }

    public function test_non_owner_cannot_change_banner(): void
    {
        $member = User::factory()->create();
        $this->family->members()->attach($member->id, ['role' => 'parent']);

        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('upload');
        });

        $this->actingAs($member)->withSession(['current_family_id' => $this->family->id])
            ->post(route('family.settings.banner.update'), ['banner' => UploadedFile::fake()->image('banner.png', 1200, 400)])
            ->assertForbidden();

        $this->actingAs($member)->withSession(['current_family_id' => $this->family->id])
            ->delete(route('family.settings.banner.destroy'))
            ->assertForbidden();
    }

    public function test_banner_must_be_an_image(): void
    {
        $this->asOwner()
            ->post(route('family.settings.banner.update'), ['banner' => UploadedFile::fake()->create('banner.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('banner');
    }
}
