<?php

use App\Models\CalendarEvent;
use App\Models\Family;
use App\Models\User;
use App\Services\InviteUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| E2E テスト用ルート
|--------------------------------------------------------------------------
|
| APP_ENV=e2e のときだけ登録される（bootstrap/app.php）。Playwright からテストデータを作るために使う。
| web ミドルウェア外のため CSRF・セッションは不要。本番・開発環境では存在しない。
|
*/

// 家族とオーナー（＋任意でメンバー）を作成し、ログイン情報と招待URLを返す
Route::post('/families', function (Request $request, InviteUrlService $inviteUrlService) {
    $data = $request->validate([
        'owner_email' => ['nullable', 'email'],
        'max_members' => ['nullable', 'integer', 'min:1'],
        'members' => ['nullable', 'integer', 'min:0'],
        'expired' => ['nullable', 'boolean'],
        'owner_birthday_today' => ['nullable', 'boolean'],
        // バナー画像の URL（画像ストレージにアップロードせず、家族設定に直接入れる）
        'banner_url' => ['nullable', 'string'],
        // 家族のアイコンの URL（同上）
        'icon_url' => ['nullable', 'string'],
    ]);

    // owner_email を指定した場合は既存ユーザーに 2 つ目以降の家族を作る
    // 誕生日はカレンダーに表示されるため、指定がなければ空にする（ランダムな誕生日でテストが揺れないように）
    $owner = isset($data['owner_email'])
        ? User::where('email', $data['owner_email'])->firstOrFail()
        : User::factory()->create([
            'birthday' => ($data['owner_birthday_today'] ?? false) ? now('Asia/Tokyo')->subYears(30)->toDateString() : null,
        ]);

    $family = Family::factory()->create([
        'owner_id' => $owner->id,
        'max_members' => $data['max_members'] ?? 10,
        'code_expires_at' => ($data['expired'] ?? false) ? now()->subDay() : null,
        'settings' => array_filter([
            'banner' => isset($data['banner_url']) ? ['external_id' => 'e2e-banner', 'url' => $data['banner_url']] : null,
            'icon' => isset($data['icon_url']) ? ['external_id' => 'e2e-icon', 'url' => $data['icon_url']] : null,
        ]) ?: null,
    ]);
    $family->members()->attach($owner->id, ['role' => 'owner']);

    foreach (User::factory()->count($data['members'] ?? 0)->create(['birthday' => null]) as $member) {
        $family->members()->attach($member->id, ['role' => 'parent']);
    }

    return response()->json([
        // UserFactory の既定パスワード
        'owner' => ['email' => $owner->email, 'password' => 'testtest', 'name' => $owner->name],
        'family' => ['id' => $family->id, 'name' => $family->name, 'code' => $family->code],
        'inviteUrls' => $inviteUrlService->generateInviteUrls($family),
    ]);
});

// 家族の予定を作成する（start_offset は今日から何日後か。participants=true で家族メンバー全員を参加者にする）
Route::post('/calendar-events', function (Request $request) {
    $data = $request->validate([
        'family_id' => ['required', 'string'],
        'title' => ['required', 'string'],
        'start_offset' => ['nullable', 'integer'],
        'days' => ['nullable', 'integer', 'min:1'],
        'start_time' => ['nullable', 'date_format:H:i'],
        'rrule' => ['nullable', 'string'],
        'participants' => ['nullable', 'boolean'],
    ]);

    $family = Family::findOrFail($data['family_id']);
    $start = now('Asia/Tokyo')->startOfDay()->addDays($data['start_offset'] ?? 0);

    $event = CalendarEvent::create([
        'family_id' => $family->id,
        'title' => $data['title'],
        'all_day' => !isset($data['start_time']),
        'start_date' => $start->toDateString(),
        'end_date' => $start->copy()->addDays(($data['days'] ?? 1) - 1)->toDateString(),
        'start_time' => $data['start_time'] ?? null,
        'rrule' => $data['rrule'] ?? null,
    ]);

    if ($data['participants'] ?? false) {
        foreach ($family->members as $member) {
            $event->participants()->create(['participant_type' => User::class, 'participant_id' => $member->id]);
        }
    }

    return response()->json(['id' => $event->id]);
});
