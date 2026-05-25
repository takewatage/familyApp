<?php

namespace App\Http\Middleware;

use App\Services\CurrentFamilyService;
use App\Services\InviteUrlService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settings = $request->user()?->settings ?? [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->setAppends(['avatar']),
            ],
            'userSettings' => [
                'theme'       => $settings['theme'] ?? 'system',
                'themeName'   => $settings['theme_name'] ?? $settings['theme_color'] ?? 'pink',
                'footerItems' => $settings['footer_items'] ?? ['home', 'dok', 'tasks'],
            ],
            'currentFamily' => function () {
                $family = app(CurrentFamilyService::class)->getCurrentFamily();

                if (!$family) {
                    return null;
                }

                return [
                    'id'   => $family->id,
                    'name' => $family->name,
                ];
            },
            'inviteUrls' => function () {
                $family = app(CurrentFamilyService::class)->getCurrentFamily();

                if (!$family) {
                    return [];
                }

                return app(InviteUrlService::class)->generateInviteUrls($family);
            },
        ];
    }
}
