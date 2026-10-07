<?php

namespace App\Exceptions;

use Exception;

/**
 * 招待経由の新規登録ができない場合の例外（招待なし・無効／期限切れ・定員到達）
 *
 * メッセージはそのままログイン画面に表示するため、ユーザー向けの文言で投げること。
 */
class InvalidInviteException extends Exception {}
