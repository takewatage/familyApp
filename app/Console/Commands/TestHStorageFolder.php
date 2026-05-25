<?php

namespace App\Console\Commands;

use App\Services\HStorageClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestHStorageFolder extends Command
{
    protected $signature = 'app:test-hstorage-folder
                            {familyId? : 家族UUID（省略時は test-family-id を使用）}';

    protected $description = 'HStorageでファイル名にパスを指定してフォルダ分けができるかテストする';

    public function __construct(private HStorageClient $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $familyId = $this->argument('familyId') ?? 'test-family-id';
        $storagePath = "familyApp/{$familyId}/avatar";
        $filename = $storagePath . '/' . Str::ulid() . '.webp';

        $this->info("アップロード開始");
        $this->line("  保存パス: {$storagePath}");
        $this->line("  ファイル名: {$filename}");

        // 1x1 の最小 WebP バイナリを生成（GD が使えない環境でも動く最小バイナリ）
        $webpBinary = $this->makeMinimalWebp();

        try {
            $result = $this->client->upload(
                fileContents: $webpBinary,
                filename: $filename,
                contentType: 'image/webp',
            );
        } catch (\Throwable $e) {
            $this->error('アップロード失敗: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('アップロード成功');
        $this->table(
            ['キー', '値'],
            [
                ['external_id', $result['external_id']],
                ['direct_url',  $result['direct_url']],
                ['share_url',   $result['share_url'] ?? '（なし）'],
            ],
        );

        $this->newLine();
        $this->line('上記 URL にアクセスしてファイルが取得できれば、フォルダ分けが機能しています。');
        $this->line("external_id にパス区切りが含まれているかも確認してください: <comment>{$result['external_id']}</comment>");

        return self::SUCCESS;
    }

    /**
     * GD・Intervention Image 不要の最小 WebP バイナリ（1x1 透明画像）
     */
    private function makeMinimalWebp(): string
    {
        // RFC 6386 準拠の 1x1 透明 WebP（VP8L ロスレス）
        return base64_decode(
            'UklGRlYAAABXRUJQVlA4IEoAAADQAQCdASoBAAEAAUAmJYgCdAEO/gHOAAD' .
            'u9vGFf8r/wZ+1f+P/wP/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wA='
        );
    }
}
