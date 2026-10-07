<?php

namespace App\Services;

use App\Dtos\Family\FamilyPwaSettingsData;
use App\Models\Family;

class PwaManifestService
{
    /**
     * 家族の PWA 設定を既定値とマージして返す
     */
    public function resolve(?Family $family): FamilyPwaSettingsData
    {
        $settings = $this->settings($family);

        return new FamilyPwaSettingsData(
            name: $settings['name'],
            icon_apple: $settings['icons']['apple'],
            icon_192: $settings['icons']['192'],
            icon_512: $settings['icons']['512'],
            icon_maskable: $settings['icons']['maskable'],
            is_custom_icon: $settings['is_custom_icon'],
            manifest_hash: $this->hashOf($settings),
        );
    }

    /**
     * Web App Manifest を組み立てる
     *
     * @return array<string, mixed>
     */
    public function manifest(?Family $family): array
    {
        return $this->buildManifest($this->settings($family));
    }

    /**
     * @return array{name: string, icons: array{apple: string, 192: string, 512: string, maskable: string}, is_custom_icon: bool}
     */
    private function settings(?Family $family): array
    {
        $pwa = $family?->settings['pwa'] ?? [];
        $defaultIcons = config('pwa.default_icons');
        $customIcon = $pwa['icon'] ?? null;

        $name = is_string($pwa['name'] ?? null) && $pwa['name'] !== '' ? $pwa['name'] : config('pwa.default_name');

        $icons = [];
        foreach (['apple', '192', '512', 'maskable'] as $key) {
            $icons[$key] = is_array($customIcon) && is_string($customIcon[$key] ?? null) ? $customIcon[$key] : $defaultIcons[$key];
        }

        return [
            'name' => $name,
            'icons' => $icons,
            'is_custom_icon' => is_array($customIcon),
        ];
    }

    /**
     * @param  array{name: string, icons: array{apple: string, 192: string, 512: string, maskable: string}, is_custom_icon: bool}  $settings
     * @return array<string, mixed>
     */
    private function buildManifest(array $settings): array
    {
        return [
            // 設定変更・家族切り替えで別アプリとして扱われないよう固定
            'id' => '/',
            'name' => $settings['name'],
            'short_name' => $settings['name'],
            'start_url' => config('pwa.start_url'),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => config('pwa.background_color'),
            'theme_color' => config('pwa.theme_color'),
            'icons' => [
                ['src' => $settings['icons']['192'], 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $settings['icons']['512'], 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => $settings['icons']['maskable'], 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];
    }

    /**
     * @param  array{name: string, icons: array{apple: string, 192: string, 512: string, maskable: string}, is_custom_icon: bool}  $settings
     */
    private function hashOf(array $settings): string
    {
        $source = json_encode([$this->buildManifest($settings), $settings['icons']['apple']]);

        return substr(hash('sha256', $source), 0, 12);
    }
}
