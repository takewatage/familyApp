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

class FamilyIconTest extends TestCase
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
            'settings' => ['banner' => ['external_id' => 'banner', 'url' => 'https://example.com/banner.webp']],
        ]);
        $this->family->members()->attach($this->owner->id, ['role' => 'owner']);
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner)->withSession(['current_family_id' => $this->family->id]);
    }

    public function test_owner_can_upload_icon_and_it_is_shared_as_current_family_icon(): void
    {
        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('upload')->once()->andReturnUsing(function (string $contents, string $filename) {
                $size = getimagesizefromstring($contents);
                $this->assertSame('image/webp', $size['mime']);
                // 横幅 256px に縮小して保存する
                $this->assertSame(256, $size[0]);
                $this->assertStringStartsWith("familyApp/{$this->family->id}/icon/", $filename);

                return ['external_id' => 'icon-1', 'direct_url' => 'https://example.com/icon-1.webp', 'share_url' => ''];
            });
        });

        $this->asOwner()
            ->post(route('family.settings.icon.update'), ['icon' => UploadedFile::fake()->image('icon.png', 1024, 1024)])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = $this->family->fresh()->settings;
        $this->assertSame('https://example.com/icon-1.webp', $settings['icon']['url']);
        // 他の家族設定（バナー）は消えない
        $this->assertSame('https://example.com/banner.webp', $settings['banner']['url']);

        // 全ページの共有プロパティ（サイドメニュー）に載る
        $this->asOwner()->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('currentFamily.iconUrl', 'https://example.com/icon-1.webp'));
        $this->asOwner()->get(route('family.settings.index'))
            ->assertInertia(fn (Assert $page) => $page->where('iconUrl', 'https://example.com/icon-1.webp'));
    }

    public function test_owner_can_delete_icon(): void
    {
        $this->family->update(['settings' => ['icon' => ['external_id' => 'old', 'url' => 'https://example.com/old.webp']]]);

        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('delete')->once()->with('old');
        });

        $this->asOwner()->delete(route('family.settings.icon.destroy'))->assertRedirect();

        $this->assertArrayNotHasKey('icon', $this->family->fresh()->settings ?? []);
        $this->asOwner()->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('currentFamily.iconUrl', null));
    }

    public function test_non_owner_cannot_change_icon(): void
    {
        $member = User::factory()->create();
        $this->family->members()->attach($member->id, ['role' => 'parent']);

        $this->mock(HStorageClient::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('upload');
        });

        $this->actingAs($member)->withSession(['current_family_id' => $this->family->id])
            ->post(route('family.settings.icon.update'), ['icon' => UploadedFile::fake()->image('icon.png', 256, 256)])
            ->assertForbidden();
        $this->actingAs($member)->withSession(['current_family_id' => $this->family->id])
            ->delete(route('family.settings.icon.destroy'))
            ->assertForbidden();
    }

    public function test_icon_must_be_an_image(): void
    {
        $this->asOwner()
            ->post(route('family.settings.icon.update'), ['icon' => UploadedFile::fake()->create('icon.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('icon');
    }
}
