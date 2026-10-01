<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * カレンダー画面（モックアップ）。
     *
     * 機能は未確定のため、現時点ではサーバーからデータを渡さない。
     * 表示用のダミーイベントはページ側（Pages/Calendar/Index.vue）で保持する。
     */
    public function index(): Response
    {
        return Inertia::render('Calendar/Index');
    }
}
