<?php

namespace App\Services;

use App\Models\Family;
use Illuminate\Support\Facades\URL;

class InviteUrlService
{
    private const ROLES = ['parent', 'child', 'guest'];

    /**
     * 家族の招待URL一覧を生成する（ロール別）
     *
     * @return array<string, string>
     */
    public function generateInviteUrls(Family $family): array
    {
        $urls = [];
        foreach (self::ROLES as $role) {
            $urls[$role] = URL::signedRoute('invite.show', [
                'code' => $family->code,
                'role' => $role,
            ]);
        }

        return $urls;
    }
}
