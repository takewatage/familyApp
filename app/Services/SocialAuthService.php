<?php

namespace App\Services;

use App\Exceptions\SocialAuthException;
use App\Models\User;
use Laravel\Socialite\AbstractUser as SocialiteUser;

class SocialAuthService
{
    /**
     * Googleユーザー情報からアプリのユーザーを特定または作成する
     *
     * 判定順序:
     *  1. google_id が一致するユーザー → そのユーザー
     *  2. Google側のメール未確認 → 例外（既存アカウントへの自動紐付けを許可しない）
     *  3. 同じメールアドレスのユーザー → google_id を紐付けて連携
     *  4. いずれも該当なし → 新規作成（パスワードなし・メール確認済み）
     *
     * @return array{0: User, 1: bool} ユーザーと、新規作成したかどうか
     *
     * @throws SocialAuthException
     */
    public function findOrCreateGoogleUser(SocialiteUser $googleUser): array
    {
        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            return [$user, false];
        }

        if (!$email) {
            throw new SocialAuthException(
                'Googleアカウントからメールアドレスを取得できませんでした。'
            );
        }

        if (!$this->isEmailVerified($googleUser)) {
            throw new SocialAuthException('Googleアカウントのメールアドレスが確認済みではないため、ログインできません。');
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            $existing
                ->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' =>
                        $existing->email_verified_at ?? now(),
                ])
                ->save();

            return [$existing, false];
        }

        $user = User::create([
            'name' =>
                $googleUser->getName() ?:
                $googleUser->getNickname() ?:
                'ゲスト',
            'email' => $email,
            'google_id' => $googleId,
            'password' => null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return [$user, true];
    }

    /**
     * Google側でメールアドレスが確認済みかどうか
     *
     * OpenID Connect の email_verified クレームを参照する。
     */
    private function isEmailVerified(SocialiteUser $googleUser): bool
    {
        $raw = $googleUser->getRaw() ?: [];

        return filter_var(
            $raw['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
