<?php

namespace App\Http\Controllers;

use App\Models\Theme;

class ThemeController extends Controller
{
    public function index()
    {
        return response()->json([
            'themes' => Theme::all()
        ]);
    }
}
