<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('family.{familyId}', function ($user, $familyId) {
    // ユーザーがこのfamilyに所属しているかチェック
    return $user->families()->where('families.id', $familyId)->exists();
});
