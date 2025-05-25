<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return $user->favorites()->with('recipe')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        $user = $request->user();

        $user->favorites()->updateOrCreate([
            'recipe_id' => $request->recipe_id,
        ]);

        return response()->json(['message' => 'お気に入りに追加しました']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        $user = $request->user();

        $user->favorites()->where('recipe_id', $request->recipe_id)->delete();

        return response()->json(['message' => 'お気に入りから削除しました']);
    }
}
