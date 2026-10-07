<?php

namespace App\Services;

use App\Exceptions\SocialAuthException;
use App\Models\User;
use Laravel\Socialite\AbstractUser as SocialiteUser;

class SocialAuthService
{
    /**
     * Googleユーザー情報から既存のアプリユーザーを特定する（必要なら google_id を紐付ける）
     *
     * 判定順序:
     *  1. google_id が一致するユーザー → そのユーザー
     *  2. Google側のメール未確認 → 例外（既存アカウントへの自動紐付け・新規作成を許可しない）
     *  3. 同じメールアドレスのユーザー → google_id を紐付けて連携
     *  4. いずれも該当なし → null（新規登録が必要。招待の確認後に createGoogleUser() を呼ぶ）
     *
     * @throws SocialAuthException
     */
    public function findOrLinkGoogleUser(SocialiteUser $googleUser): ?User
    {
        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            return $user;
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
        }

        return $existing;
    }

    /**
     * Googleユーザー情報から新規ユーザーを作成する（パスワードなし・メール確認済み）
     *
     * findOrLinkGoogleUser() が null を返した（メール確認済み・未登録）ことを前提とする。
     */
    public function createGoogleUser(SocialiteUser $googleUser): User
    {
        $user = User::create([
            'name' =>
                $googleUser->getName() ?:
                $googleUser->getNickname() ?:
                'ゲスト',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
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
