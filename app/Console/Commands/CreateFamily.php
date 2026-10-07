<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * 家族とオーナーを作成する（管理者用）
 *
 * 新規登録は招待経由のみのため、最初の家族（＋オーナー）や追加の家族はこのコマンドで作成する。
 * 作成後はオーナーがアプリから招待リンクを発行してメンバーを招待する。
 */
class CreateFamily extends Command
{
    protected $signature = 'family:create
                            {name : 家族名}
                            {--email= : オーナーのメールアドレス（未登録なら新規ユーザーを作成）}';

    protected $description = '家族とオーナーを作成する（新規登録は招待経由のみのため、最初の家族はこのコマンドで作る）';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('オーナーのメールアドレス');

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error('メールアドレスの形式が正しくありません。');

            return self::FAILURE;
        }

        $email = strtolower($email);
        $owner = User::where('email', $email)->first();
        $userName = null;
        $password = null;

        if ($owner) {
            $this->info("既存ユーザー「{$owner->name}」をオーナーにします。");
        } else {
            $this->info('未登録のメールアドレスのため、オーナーのユーザーを新規作成します。');

            $userName = $this->ask('オーナーの名前');
            $password = $this->secret('パスワード');

            $validator = Validator::make(
                ['name' => $userName, 'password' => $password, 'password_confirmation' => $this->secret('パスワード（確認）')],
                ['name' => ['required', 'string', 'max:255'], 'password' => ['required', 'confirmed', Password::defaults()]],
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->error($message);
                }

                return self::FAILURE;
            }
        }

        $family = DB::transaction(function () use ($owner, $email, $userName, $password) {
            if (!$owner) {
                $owner = User::create([
                    'name' => $userName,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                $owner->forceFill(['email_verified_at' => now()])->save();
            }

            // 家族コード（code）は Family::creating フックで自動採番される
            $family = Family::create([
                'name' => $this->argument('name'),
                'owner_id' => $owner->id,
            ]);

            $family->members()->attach($owner->id, ['role' => 'owner']);

            // 次回ログイン時にこの家族が選ばれるようにする
            $owner->forceFill(['last_family_id' => $family->id])->save();

            return $family;
        });

        $this->info("家族「{$family->name}」を作成しました（オーナー: {$email}）。");
        $this->line('ログイン後、家族設定から招待リンクを発行してメンバーを招待してください。');

        return self::SUCCESS;
    }
}
