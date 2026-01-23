<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 家族A: オーナー1名 + メンバー2名
        $ownerA = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => Hash::make('testtest'),
        ]);

        $familyA = Family::factory()->create([
            'name' => '山田家',
            'code' => 'FAMILYA1',
            'owner_id' => $ownerA->id,
        ]);

        // オーナーをfamily_userに追加
        DB::table('family_user')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'family_id' => $familyA->id,
            'user_id' => $ownerA->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 家族Aのメンバー2名
        $memberA1 = User::factory()->create([
            'name' => '山田花子',
            'email' => 'hanako@example.com',
            'password' => Hash::make('testtest'),
        ]);

        DB::table('family_user')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'family_id' => $familyA->id,
            'user_id' => $memberA1->id,
            'role' => 'parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberA2 = User::factory()->create([
            'name' => '山田次郎',
            'email' => 'jiro@example.com',
            'password' => Hash::make('testtest'),
        ]);

        DB::table('family_user')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'family_id' => $familyA->id,
            'user_id' => $memberA2->id,
            'role' => 'child',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 家族B: オーナー1名 + メンバー1名
        $ownerB = User::factory()->create([
            'name' => '佐藤一郎',
            'email' => 'sato@example.com',
            'password' => Hash::make('testtest'),
        ]);

        $familyB = Family::factory()->create([
            'name' => '佐藤家',
            'code' => 'FAMILYB1',
            'owner_id' => $ownerB->id,
        ]);

        // オーナーをfamily_userに追加
        DB::table('family_user')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'family_id' => $familyB->id,
            'user_id' => $ownerB->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 家族Bのメンバー1名
        $memberB1 = User::factory()->create([
            'name' => '佐藤美咲',
            'email' => 'misaki@example.com',
            'password' => Hash::make('testtest'),
        ]);

        DB::table('family_user')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'family_id' => $familyB->id,
            'user_id' => $memberB1->id,
            'role' => 'parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 単独ユーザー（どの家族にも所属しない）
        User::factory()->create([
            'name' => '田中孤独',
            'email' => 'tanaka@example.com',
            'password' => Hash::make('testtest'),
        ]);

        $this->call([
            CategorySeeder::class,
            TaskSeeder::class,
        ]);
    }
}
