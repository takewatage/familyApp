<?php

namespace App\Http\Controllers;

use App\Dtos\MyPage\MyPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyPageController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('MyPage/index', MyPageData::from([
            'name'      => $user->name,
            'email'     => $user->email,
            'avatarUrl' => null,
            'createdAt' => $user->created_at->format('Y年m月d日'),
        ]));
    }
}
