<?php

namespace App\Exceptions;

use Exception;

/**
 * 外部プロバイダ認証（Google等）でユーザーを特定・作成できなかった場合の例外
 *
 * メッセージはそのままログイン画面に表示するため、ユーザー向けの文言で投げること。
 */
class SocialAuthException extends Exception {}
