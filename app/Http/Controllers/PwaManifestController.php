<?php

namespace App\Http\Controllers;

use App\Services\CurrentFamilyService;
use App\Services\PwaManifestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PwaManifestController extends Controller
{
    public function __construct(
        private PwaManifestService $pwaManifestService,
        private CurrentFamilyService $currentFamilyService,
    ) {}

    /**
     * 現在の家族の設定から Web App Manifest を返す（未ログインは既定値）
     */
    public function show(): JsonResponse
    {
        $family = Auth::check() ? $this->currentFamilyService->getCurrentFamily() : null;

        return response()->json(
            $this->pwaManifestService->manifest($family),
            200,
            [
                'Content-Type' => 'application/manifest+json',
                'Cache-Control' => 'no-cache',
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
