<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DokController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dok/index', []);
    }
}
