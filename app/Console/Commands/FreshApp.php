<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FreshApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'アプリケーションをフレッシュする';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        print '--- start: ' . get_class($this) . "\n";

        // キャッシュクリア
        self::sendArtisan('cache:clear');
        self::sendArtisan('config:clear');
        self::sendArtisan('route:clear');
        self::sendArtisan('view:clear');

        print '--- finish: ' . get_class($this) . "\n\n";
    }

    private function chmod($directory)
    {
        $path = exec('pwd') . "/{$directory}";

        exec("sudo chmod -R 777 {$directory}");

        printf("\e[32m%s: \e[m %s\n", 'finish chmod 777', $path);
    }

    private function sendArtisan($cmd)
    {
        Artisan::call($cmd);

        printf("\e[32m%s: \e[m %s\n", 'finish', $cmd);
    }
}
