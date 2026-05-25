<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadService
{
    private const DEFAULT_QUALITY = 80;

    /**
     * 用途別サイズ定義（scale: 縦横比維持）
     */
    private array $presets = [
        'product' => [
            'large' => ['width' => 1200],
            'medium' => ['width' => 600],
            'thumbnail' => ['width' => 300],
        ],
        'avatar' => [
            'default' => ['width' => 400],
            'thumbnail' => ['width' => 80],
        ],
        'blog' => [
            'hero' => ['width' => 1200],
            'card' => ['width' => 600],
            'thumbnail' => ['width' => 300],
        ],
    ];

    public function __construct(
        private HStorageClient $client,
    )
    {
    }

    // -------------------------------------------------------------------------
    //  Public API
    // -------------------------------------------------------------------------

    /**
     * 単体アップロード（1サイズのみ）
     * @param UploadedFile $file
     * @param int $width
     * @param int $quality
     * @return array{external_id: string, direct_url: string, share_url: string}
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function upload(
        UploadedFile $file,
        int          $width = 800,
        int          $quality = self::DEFAULT_QUALITY,
        string       $storagePath = 'familyApp',
    ): array
    {
        $encoded = $this->resizeToWebp($file, $width, $quality);
        $filename = $storagePath . '/' . Str::ulid() . '.webp';

        return $this->client->upload(
            fileContents: (string)$encoded,
            filename: $filename,
        );
    }

    /**
     * プリセットを使って複数サイズを一括生成
     * @param UploadedFile $file
     * @param string $preset
     * @param string $storagePath 保存先パス（例: familyApp/{familyId}/product）
     * @param int $quality
     * @return array<string, array{external_id: string, direct_url: string, share_url: string}>
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function uploadWithPreset(
        UploadedFile $file,
        string       $preset,
        string       $storagePath = 'familyApp',
        int          $quality = self::DEFAULT_QUALITY,
    ): array
    {
        if (!isset($this->presets[$preset])) {
            throw new \InvalidArgumentException("Unknown preset: {$preset}");
        }

        $results = [];

        foreach ($this->presets[$preset] as $variant => $config) {
            $encoded = $this->resizeToWebp($file, $config['width'], $quality);
            $filename = $storagePath . '/' . $variant . '_' . Str::ulid() . '.webp';

            $results[$variant] = $this->client->upload(
                fileContents: (string)$encoded,
                filename: $filename,
            );
        }

        return $results;
    }

    /**
     * 画像を削除（単一 external_id または配列）
     */
    public function delete(string|array $externalIds): void
    {
        $externalIds = is_array($externalIds) ? $externalIds : [$externalIds];

        foreach ($externalIds as $externalId) {
            if ($externalId) {
                $this->client->delete($externalId);
            }
        }
    }

    // -------------------------------------------------------------------------
    //  Private
    // -------------------------------------------------------------------------

    /**
     * リサイズしてWebPにエンコード
     */
    private function resizeToWebp(UploadedFile $file, int $width, int $quality): string
    {
        $image = Image::read($file);
        $image->scale(width: $width);

        return (string)$image->toWebp($quality);
    }

}
