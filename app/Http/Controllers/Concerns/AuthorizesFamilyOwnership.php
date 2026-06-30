<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * モデルが現在の家族に属することを保証する越境アクセスガード。
 * 利用側コントローラーは `private readonly CurrentFamilyService $currentFamilyService` を保持している前提。
 * システム既定（family_id=null）も family 不一致として 404 になり、編集・削除から保護される。
 */
trait AuthorizesFamilyOwnership
{
    protected function authorizeFamilyOwnership(Model $model): void
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        abort_if(! $familyId || $model->family_id !== $familyId, 404);
    }
}
