<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user(); // または auth()->user();
        $request->validate([
            'name' => 'required|string|max:255',
            'avatar_url' => 'nullable|image|max:2048',
            // 必要に応じて他のバリデーションも追加
        ]);

        $user->name = $request->name;

        if ($request->hasFile('avatar_url')) {
            // ストレージに保存
            $path = $request->file('avatar_url')->store('avatars', 'public');
            $user->avatar_url = Storage::url($path);
        }

        $user->save();

        return response()->json($user);
    }

    public function userInfo($id)
    {
        $user = User::findOrFail($id);

        $followingsCount = $user->followings()->count(); // 自分がフォローしている人数
        $followersCount = $user->followers()->count();   // 自分をフォローしている人数

        return response()->json([
            'id' => (string) $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'followings_count' => $followingsCount,
            'followers_count' => $followersCount,
        ]);
    }
}
