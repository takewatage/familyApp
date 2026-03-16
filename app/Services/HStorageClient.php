<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Random\RandomException;
use RuntimeException;

class HStorageClient
{
    private string $apiKey;
    private string $secretKey;
    private string $email;
    private string $baseUrl = 'https://api.hstorage.io';

    public function __construct()
    {
        $this->apiKey = config('services.HStorage.api_key');
        $this->secretKey = config('services.HStorage.secret_key');
        $this->email = config('services.HStorage.email');
    }

    // -------------------------------------------------------------------------
    //  Public API
    // -------------------------------------------------------------------------

    /**
     *  presigned URLを取得 → ファイルをPUTアップロード
     *
     * @param string $fileContents ファイルのバイナリデータ
     * @param string $filename ファイル名（例: img_01HX...webp）
     * @param string $contentType MIMEタイプ（例: image/webp）
     * @return array{external_id: string, direct_url: string, share_url: string}
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function upload(string $fileContents, string $filename, string $contentType = 'image/webp'): array
    {
        // 1. presigned URL を取得
        $presigned = $this->getPresignedUrl($filename);

        // 2. presigned URL に PUT でアップロード（S3互換）
        $uploadResponse = Http::withHeaders([
            'Content-Type' => $contentType,
            'Content-Length' => strlen($fileContents),
        ])
            ->withBody($fileContents, $contentType)
            ->put($presigned['presigned_url']);

        if (!$uploadResponse->successful()) {
            throw new RuntimeException(
                'HStorage upload failed: ' . $uploadResponse->body()
            );
        }

        return [
            'external_id' => $presigned['external_id'],
            'direct_url' => $presigned['direct_url'],
            'share_url' => $presigned['share_url'],
        ];
    }

    /**
     * ファイルを削除
     * @param string $externalId HStorageのexternal_id
     * @return bool
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function delete(string $externalId): bool
    {
        $response = Http::withHeaders($this->buildAuthHeaders())
            ->delete("{$this->baseUrl}/file/my", [
                'external_id' => $externalId,
            ]);

        return $response->successful();
    }

    // -------------------------------------------------------------------------
    //  Private
    // -------------------------------------------------------------------------

    /**
     * presigned URL を取得
     */
    private function getPresignedUrl(string $filename): array
    {
        $response = Http::withHeaders($this->buildAuthHeaders())
            ->post("{$this->baseUrl}/upload/v1/presigned", [
                'file_name' => $filename,
                'is_encrypt' => false,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Failed to get presigned URL: ' . $response->body()
            );
        }

        $data = $response->json();

        // レスポンス検証
        if (empty($data['presigned_url']) || empty($data['external_id'])) {
            throw new RuntimeException(
                'Invalid presigned URL response: ' . json_encode($data)
            );
        }

        return $data;
    }

    /**
     * AES-GCM で認証ヘッダーを生成
     *
     * HStorage の認証方式:
     *   x-eu-api-key: APIキーを AES-GCM で暗号化し、Hexエンコード
     *   x-eu-nonce:   暗号化に使用した nonce を Hexエンコード
     *   x-eu-email:   アカウントのメールアドレス
     *
     * ※ nonce はリクエスト毎に生成する（同じnonceを再利用するとエラー）
     * @throws RandomException
     */
    private function buildAuthHeaders(): array
    {
        // nonce を毎回生成（12バイト = GCMの標準nonceサイズ）
        $nonce = random_bytes(12);

        // AES-GCM で APIキーを暗号化
        $tag = '';
        $ciphertext = openssl_encrypt(
            $this->apiKey,       // 平文: APIキー
            'aes-256-gcm',       // アルゴリズム
            $this->secretKey,    // 暗号鍵
            OPENSSL_RAW_DATA,    // バイナリ出力
            $nonce,              // nonce
            $tag,                // 認証タグ（出力）
            '',                  // AAD（なし）
            16                   // タグ長
        );

        if ($ciphertext === false) {
            throw new RuntimeException('AES-GCM encryption failed');
        }

        // ciphertext + tag を結合（Go の gcm.Seal 相当）
        $sealed = $ciphertext . $tag;

        return [
            'x-eu-api-key' => bin2hex($sealed),
            'x-eu-nonce' => bin2hex($nonce),
            'x-eu-email' => $this->email,
        ];
    }
}
