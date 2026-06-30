<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultPaymentMethodsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * システムデフォルトの支払い方法を投入する。
     * is_system=true / family_id=null で全家族から参照可能・編集不可とする。
     */
    public function run(): void
    {
        // [支払い方法名, アイコン]
        $methods = [
            ['現金', 'cash'],
            ['クレジットカード', 'credit-card'],
            ['電子マネー', 'contactless-payment'],
            ['銀行振込', 'bank'],
            ['その他', 'dots-horizontal'],
        ];

        foreach ($methods as $sort => [$name, $icon]) {
            PaymentMethod::firstOrCreate(
                ['family_id' => null, 'name' => $name, 'is_system' => true],
                ['icon' => $icon, 'sort_order' => $sort, 'is_active' => true],
            );
        }
    }
}
