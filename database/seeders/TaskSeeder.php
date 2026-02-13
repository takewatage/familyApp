<?php

namespace Database\Seeders;

use App\Models\TaskCategory;
use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taskTemplates = [
            '家事' => ['掃除する', '洗濯する', '食器を洗う'],
            '買い物' => ['食材を買う', '日用品を買う', 'プレゼントを買う'],
            '勉強' => ['宿題をする', '本を読む', '英語を勉強する'],
        ];

        $categories = TaskCategory::all();

        foreach ($categories as $category) {
            $templates = $taskTemplates[$category->name] ?? ['タスクを実行する'];

            // ランダムで1〜3個のタスクを作成
            $taskCount = rand(1, 3);

            for ($i = 0; $i < $taskCount; $i++) {
                if (isset($templates[$i])) {
                    Task::create([
                        'family_id' => $category->family_id,
                        'category_id' => $category->id,
                        'content' => $templates[$i],
                        'is_completed' => (bool) rand(0, 1),
                        'completed_at' => null,
                    ]);
                }
            }
        }
    }
}
