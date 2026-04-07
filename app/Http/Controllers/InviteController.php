<?php

namespace App\Http\Controllers;

use App\Dtos\Auth\InviteConfirmResult;
use App\Models\Family;
use App\Services\CurrentFamilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InviteController extends Controller
{
    public function __construct(private readonly CurrentFamilyService $currentFamilyService)
    {
    }

    public function show(Request $request, string $code): Response
    {
        // 署名付きURLの場合、署名が不正なら無効扱い
        if ($request->has('signature') && !$request->hasValidSignature()) {
            return Inertia::render('Auth/JoinFamily', InviteConfirmResult::from([
                'family_name' => null,
                'member_count' => 0,
                'code' => $code,
                'already_joined' => false,
                'is_full' => false,
                'error' => 'invalid',
            ]));
        }

        $family = Family::where('code', $code)->first();

        if (!$family || ($family->code_expires_at && $family->code_expires_at->isPast())) {
            return Inertia::render('Auth/JoinFamily', InviteConfirmResult::from([
                'family_name' => null,
                'member_count' => 0,
                'code' => $code,
                'already_joined' => false,
                'is_full' => false,
                'error' => 'invalid',
            ]));
        }

        $memberCount = $family->members()->count();

        $alreadyJoined = $request->user()
            && $request->user()->families()->where('family_id', $family->id)->exists();

        // 署名が有効な場合のみロールを信頼する（署名なしはすべて guest）
        $role = ($request->has('signature') && $request->hasValidSignature())
            && in_array($request->query('role'), ['parent', 'child', 'guest'])
            ? $request->query('role')
            : 'guest';

        // 未ログインユーザー向け: ログイン後に招待ページへ戻るよう intended を設定
        if (!$request->user()) {
            session()->put('url.intended', route('invite.show', $code) . '?role=' . $role);
        }

        session(['invite_family_code' => $code, 'invite_role' => $role]);

        return Inertia::render('Auth/JoinFamily', InviteConfirmResult::from([
            'family_name' => $family->name,
            'member_count' => $memberCount,
            'code' => $code,
            'already_joined' => $alreadyJoined,
            'is_full' => $memberCount >= $family->max_members,
            'error' => null,
            'invite_role' => $role,
        ]));
    }

    public function join(Request $request, string $code): RedirectResponse
    {
        $family = Family::where('code', $code)->first();

        if (!$family || ($family->code_expires_at && $family->code_expires_at->isPast())) {
            return back()->withErrors(['code' => '招待コードが無効または期限切れです']);
        }

        $user = $request->user();

        if ($user->families()->where('family_id', $family->id)->exists()) {
            return redirect()->route('home');
        }

        if ($family->members()->count() >= $family->max_members) {
            return back()->withErrors(['code' => '家族の定員に達しています']);
        }

        $role = in_array(session('invite_role'), ['parent', 'child', 'guest'])
            ? session('invite_role')
            : 'guest';

        $family->members()->attach($user->id, ['role' => $role]);

        $this->currentFamilyService->setCurrentFamily($family->id);

        session()->forget(['invite_family_code', 'invite_role']);

        return redirect()->route('home');
    }
}
