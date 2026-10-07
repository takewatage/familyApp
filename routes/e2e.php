<?php

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
    ]);

    // owner_email を指定した場合は既存ユーザーに 2 つ目以降の家族を作る
    $owner = isset($data['owner_email'])
        ? User::where('email', $data['owner_email'])->firstOrFail()
        : User::factory()->create();

    $family = Family::factory()->create([
        'owner_id' => $owner->id,
        'max_members' => $data['max_members'] ?? 10,
        'code_expires_at' => ($data['expired'] ?? false) ? now()->subDay() : null,
    ]);
    $family->members()->attach($owner->id, ['role' => 'owner']);

    foreach (User::factory()->count($data['members'] ?? 0)->create() as $member) {
        $family->members()->attach($member->id, ['role' => 'parent']);
    }

    return response()->json([
        // UserFactory の既定パスワード
        'owner' => ['email' => $owner->email, 'password' => 'testtest', 'name' => $owner->name],
        'family' => ['id' => $family->id, 'name' => $family->name, 'code' => $family->code],
        'inviteUrls' => $inviteUrlService->generateInviteUrls($family),
    ]);
});
