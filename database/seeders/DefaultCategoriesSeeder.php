<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * システムデフォルトのカテゴリー（親子階層）を投入する。
     * is_system=true / family_id=null で全家族から参照可能・編集不可とする。
     */
    public function run(): void
    {
        // [親カテゴリー名, アイコン, 色, [子カテゴリー名, ...]]
        $tree = [
            ['固定費', 'home', '#EF4444', ['家賃', '光熱費', '通信費', '保険', 'サブスク']],
            ['生活費', 'cart', '#10B981', ['食費', '日用品', '交通費', '医療費', '教育費', '被服費', '美容・理容']],
            ['その他', 'dots-horizontal', '#6B7280', ['娯楽費', '交際費', '雑費', '特別支出']],
        ];

        $parentSort = 0;

        foreach ($tree as [$parentName, $icon, $color, $children]) {
            $parent = Category::firstOrCreate(
                ['family_id' => null, 'parent_id' => null, 'name' => $parentName, 'is_system' => true],
                ['icon' => $icon, 'color' => $color, 'sort_order' => $parentSort, 'is_active' => true],
            );
            $parentSort++;

            $childSort = 0;

            foreach ($children as $childName) {
                Category::firstOrCreate(
                    ['family_id' => null, 'parent_id' => $parent->id, 'name' => $childName, 'is_system' => true],
                    ['color' => $color, 'sort_order' => $childSort, 'is_active' => true],
                );
                $childSort++;
            }
        }
    }
}
