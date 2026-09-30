<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class CalendarDemoController extends Controller
{
    /**
     * カレンダーコンポーネント（SwipeCalendar）の動作確認ページ。
     *
     * メニューには載せず URL 直打ちで開く。サーバーからデータは渡さず、
     * 確認用のダミーイベントはページ側（Pages/Calendar/Demo.vue）で保持する。
     */
    public function index(): Response
    {
        return Inertia::render('Calendar/Demo');
    }
}
