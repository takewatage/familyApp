<?php

namespace Tests\Feature\Calendar;

use App\Models\CalendarLabel;
use App\Models\Family;
use App\Models\User;
use App\Services\CalendarLabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarLabelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->family = Family::factory()->create(['owner_id' => $this->user->id]);
        $this->family->members()->attach($this->user->id, ['role' => 'guest']);
    }

    public function test_default_labels_are_created_on_first_visit_only_once(): void
    {
        $page = fn () => $this->actingAs($this->user)->withSession(['current_family_id' => $this->family->id])->get(route('calendar'));

        $page()->assertInertia(fn (Assert $p) => $p
            ->has('labels', count(CalendarLabelService::DEFAULT_LABELS))
            ->where('labels.0.name', 'エメラルド・グリーン')
            ->where('labels.0.color', '#2BB673'));

        $page();

        $this->assertSame(count(CalendarLabelService::DEFAULT_LABELS), CalendarLabel::where('family_id', $this->family->id)->count());
    }

    public function test_labels_can_be_renamed_recolored_and_reordered_by_any_member(): void
    {
        app(CalendarLabelService::class)->ensureDefaults($this->family);
        $labels = CalendarLabel::where('family_id', $this->family->id)->orderBy('sort')->get();

        $payload = $labels->reverse()->values()->map(fn ($l, $i) => [
            'id' => $l->id,
            'name' => $i === 0 ? '家族' : $l->name,
            'color' => $i === 0 ? '#1e88e5' : $l->color,
        ])->all();

        // ゲストのメンバーでも編集できる
        $this->actingAs($this->user)->withSession(['current_family_id' => $this->family->id])
            ->putJson(route('calendar.labels.update'), ['labels' => $payload])
            ->assertOk()
            ->assertJsonPath('labels.0.name', '家族')
            ->assertJsonPath('labels.0.color', '#1e88e5')
            ->assertJsonPath('labels.0.id', $labels->last()->id);
    }

    public function test_labels_update_requires_all_own_labels_and_valid_values(): void
    {
        app(CalendarLabelService::class)->ensureDefaults($this->family);
        $labels = CalendarLabel::where('family_id', $this->family->id)->get();
        $other = CalendarLabel::factory()->create();
        $request = fn (array $labels) => $this->actingAs($this->user)->withSession(['current_family_id' => $this->family->id])
            ->putJson(route('calendar.labels.update'), ['labels' => $labels]);

        $valid = $labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])->all();

        // 一部だけ・他の家族のラベルを含む
        $request(array_slice($valid, 1))->assertJsonValidationErrors('labels');
        $request([...array_slice($valid, 1), ['id' => $other->id, 'name' => 'x', 'color' => '#000000']])->assertJsonValidationErrors('labels');
        // 名前が空・カラーの形式が不正
        $request([['id' => $labels[0]->id, 'name' => '', 'color' => 'red'], ...array_slice($valid, 1)])
            ->assertJsonValidationErrors(['labels.0.name', 'labels.0.color']);

        $this->assertSame($other->name, $other->fresh()->name);
    }
}
