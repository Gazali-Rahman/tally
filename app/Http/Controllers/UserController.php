<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('theme')
        ]);
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:themes,id'
        ]);

        $user = $request->user();
        $user->theme_id = $request->theme_id;
        $user->save();

        return response()->json([
            'message' => 'Tema berhasil diperbarui',
            'user' => $user->load('theme')
        ]);
    }
}
