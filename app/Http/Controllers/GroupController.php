<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'groups' => $request->user()->groups()->with('users')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $group = Group::create(['name' => $request->name]);
        
        $group->users()->attach($request->user()->id, ['role' => 'owner']);

        return response()->json([
            'message' => 'Grup berhasil dibuat',
            'group' => $group->load('users')
        ], 201);
    }

    public function invite(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $group = $request->user()->groups()->findOrFail($id);

        $invitee = User::where('email', $request->email)->first();

        if ($group->users()->where('user_id', $invitee->id)->exists()) {
            return response()->json(['message' => 'User sudah ada di dalam grup'], 400);
        }

        $group->users()->attach($invitee->id, ['role' => 'member']);

        return response()->json(['message' => 'Anggota berhasil diundang ke dalam grup']);
    }

    public function removeMember(Request $request, $id, $user_id)
    {
        $group = $request->user()->groups()->wherePivot('role', 'owner')->findOrFail($id);

        if ($group->users()->where('user_id', $user_id)->wherePivot('role', 'owner')->exists()) {
            return response()->json(['message' => 'Tidak dapat menghapus owner dari grup'], 403);
        }

        $group->users()->detach($user_id);

        return response()->json(['message' => 'Anggota berhasil dihapus dari grup']);
    }
}
