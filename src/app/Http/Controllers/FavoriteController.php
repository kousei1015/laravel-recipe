<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    // GET /api/favorites
    // public function index(Request $request)
    // {
    //     $user = $request->user();

    //     $favorites = $user->favorites()->with('recipe')->get();

    //     return response()->json($favorites);
    // }
    public function index(Request $request)
    {
        $user = $request->user();

        // favoritesのクエリを取得し、リレーションもロードして実際のコレクションを取得
        $favorites = $user->favorites()
            ->with('recipe.user') // eager load
            ->get(); // ←ここを追加

        return response()->json(
            $favorites->map(function ($favorite) {
                $recipe = $favorite->recipe;
                return [
                    'id' => (string) $recipe->id,
                    'recipe_name' => $recipe->name,
                    'image_url' => $recipe->image_url,
                    'user_id' => (string) $recipe->user_id,
                    'user_name' => optional($recipe->user)->name,
                    'cooking_time' => (int) $recipe->cooking_time,
                ];
            })
        );
    }



    // POST /api/favorites
    public function store(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        $user = $request->user();

        $favorite = $user->favorites()->create([
            'recipe_id' => $request->recipe_id,
        ]);

        return response()->json($favorite, 201);
    }

    // DELETE /api/favorites/{id}
    public function destroy($id)
    {
        $favorite = Favorite::find($id);

        if (!$favorite) {
            return response()->json(['error' => 'Favorite not found.'], 404);
        }

        // ※ 所有者か確認してから削除
        if ($favorite->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $favorite->delete();

        return response()->json(null, 204);
    }
}
