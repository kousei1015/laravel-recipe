<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Relationship;
use Illuminate\Support\Facades\Storage;


class RecipeController extends Controller
{
    // GET /api/recipes
    public function index(Request $request)
    {
        $perPage = 12;

        $query = Recipe::with('user')
            ->select('id', 'name', 'image_url', 'user_id', 'cooking_time');

        // sort_by が cooking_time の場合だけ、昇順でソート
        $sortBy = $request->query('sort_by');
        if ($sortBy === 'cooking_time') {
            $query->orderBy('cooking_time', 'asc');
        } else {
            // それ以外はデフォルトで作成日時の降順
            $query->orderBy('created_at', 'desc');
        }

        $recipes = $query->paginate($perPage);

        return response()->json([
            'data' => $recipes->map(function ($recipe) {
                return [
                    'id' => (string) $recipe->id,
                    'recipe_name' => $recipe->name,
                    'image_url' => $recipe->image_url,
                    'user_id' => (string) $recipe->user_id,
                    'user_name' => optional($recipe->user)->name,
                    'cooking_time' => (int) $recipe->cooking_time,
                ];
            }),
            'pagination' => [
                'total_count' => $recipes->total(),
                'total_pages' => $recipes->lastPage(),
                'current_page' => $recipes->currentPage(),
            ]
        ]);
    }

    // GET /api/recipes/search?tags[]=和食&tags[]=時短
    public function searchByTags(Request $request)
    {
        $tagNames = $request->query('tags', []);

        if (empty($tagNames)) {
            return response()->json(['error' => 'タグを指定してください。'], 400);
        }

        // 該当するタグIDを取得
        $tagIds = Tag::whereIn('name', $tagNames)->pluck('id');

        $perPage = 10;

        // タグが1つでも一致するレシピを取得
        $recipes = Recipe::with('user', 'tags')
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            })
            ->select('id', 'name', 'image_url', 'user_id', 'cooking_time')
            ->paginate($perPage);

        return response()->json([
            'data' => $recipes->map(function ($recipe) {
                return [
                    'id' => (string) $recipe->id,
                    'recipe_name' => $recipe->name,
                    'image_url' => $recipe->image_url,
                    'user_id' => (string) $recipe->user_id,
                    'user_name' => optional($recipe->user)->name,
                    'cooking_time' => (int) $recipe->cooking_time,
                    'tags' => $recipe->tags->pluck('name'), // タグ名を返す
                ];
            }),
            'pagination' => [
                'total_count' => $recipes->total(),
                'total_pages' => $recipes->lastPage(),
                'current_page' => $recipes->currentPage(),
            ]
        ]);
    }



    // POST /api/recipes
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cooking_time' => 'required|in:1,2,3,4,5',
            'image_url' => 'nullable|image|max:2048',  // 画像ファイルとしてバリデーション
            'ingredients' => 'required|array',
            'ingredients.*.name' => 'required|string',
            'ingredients.*.quantity' => 'required|string',
            'instructions' => 'required|array',
            'instructions.*.description' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        $user = $request->user();

        // 画像ファイルのアップロード処理
        $imagePath = null;
        if ($request->hasFile('image_url')) {
            $imagePath = $request->file('image_url')->store('recipes', 'public');
        }

        $recipe = $user->recipes()->create([
            'name' => $request->name,
            'cooking_time' => $request->cooking_time,
            'image_url' => $imagePath ? Storage::url($imagePath) : null,
        ]);

        // 食材の登録
        foreach ($request->ingredients as $ingredientData) {
            $ingredient = Ingredient::firstOrCreate([
                'name' => $ingredientData['name'],
            ]);
            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $ingredientData['quantity'],
            ]);
        }

        // 手順の登録
        foreach ($request->instructions as $instructionData) {
            $recipe->instructions()->create([
                'description' => $instructionData['description'],
            ]);
        }

        // タグの登録
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $recipe->tags()->sync($tagIds);
        }

        return response()->json($recipe->load(['tags', 'ingredients', 'instructions']), 201);
    }

    // PUT /api/recipes/{id}
    public function update(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);

        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'cooking_time' => 'sometimes|required|in:1,2,3,4,5',
            'image_url' => 'nullable|image|max:2048',  // 画像ファイルとしてバリデーション
            'ingredients' => 'required|array',
            'ingredients.*.name' => 'required|string',
            'ingredients.*.quantity' => 'required|string',
            'instructions' => 'required|array',
            'instructions.*.description' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        // 画像ファイルのアップロード処理（もし新しい画像が送信されていれば）
        if ($request->hasFile('image_url')) {
            // 既存画像があれば削除（任意で実装）
            if ($recipe->image_url) {
                $oldPath = str_replace('/storage/', '', $recipe->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $imagePath = $request->file('image_url')->store('recipes', 'public');
            $recipe->image_url = Storage::url($imagePath);
        }

        $recipe->fill($request->only('name', 'cooking_time'));
        $recipe->save();

        // 材料の更新
        $recipe->recipeIngredients()->delete();
        foreach ($request->ingredients as $ingredientData) {
            $ingredient = Ingredient::firstOrCreate([
                'name' => $ingredientData['name'],
            ]);
            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $ingredientData['quantity'],
            ]);
        }

        // 手順の更新
        $recipe->instructions()->delete();
        foreach ($request->instructions as $instructionData) {
            $recipe->instructions()->create([
                'description' => $instructionData['description'],
            ]);
        }

        // タグの更新
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $recipe->tags()->sync($tagIds);
        }

        return response()->json($recipe->load(['tags', 'ingredients', 'instructions']));
    }


    // DELETE /api/recipes/{id}
    public function destroy(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);

        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $recipe->delete();

        return response()->json(['message' => '削除しました'], 200);
    }


    public function show(Request $request, $id)
    {
        $recipe = Recipe::with([
            'user',
            'instructions',
            'recipeIngredients.ingredient',
            'favorites',
            'tags'
        ])->findOrFail($id);

        // ここを修正
        $user = Auth::guard('sanctum')->user();
        $response = [
            'id' => (string) $recipe->id,
            'recipe_name' => $recipe->name,
            'image_url' => $recipe->image_url,
            'cooking_time' => (string) $recipe->cooking_time,
            'user_id' => (string) $recipe->user->id,
            'user_name' => $recipe->user->name,
            'avatar_url' => $recipe->user->avatar_url,
            'instructions' => $recipe->instructions->map(fn($instruction) => [
                'description' => $instruction->description,
            ]),
            'ingredients' => $recipe->recipeIngredients->map(fn($ri) => [
                'name' => $ri->ingredient->name,
                'quantity' => $ri->quantity,
            ]),
            'tags' => $recipe->tags->map(
                fn($tag) =>
                $tag->name,
            ),
        ];

        if ($user) {
            $favorite = $recipe->favorites->firstWhere('user_id', $user->id);
            if ($favorite) {
                $response['favorite_id'] = (string) $favorite->id;
            }

            $follow = $user->relationships()->where('followed_id', $recipe->user_id)->first();
            if ($follow) {
                $response['follow_id'] = (string) $follow->id;
            }
        }

        return response()->json($response);
    }

    // GET /api/recipes/all
    public function all()
    {
        $recipes = Recipe::with('user:id,name') // 必要ならユーザー情報も
            ->select('id', 'name as recipe_name', 'image_url', 'user_id', 'cooking_time')
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => (string) $recipe->id,
                    'recipe_name' => $recipe->recipe_name,
                    'image_url' => $recipe->image_url,
                    'user_id' => (string) $recipe->user_id,
                    'user_name' => $recipe->user->name ?? null,
                    'cooking_time' => $recipe->cooking_time,
                ];
            });

        return response()->json([
            'data' => $recipes,
            'pagination' => null,
        ]);
    }

    public function userRecipes($id)
    {
        $user = User::findOrFail($id);

        $recipes = $user->recipes()->with('user:id,name')->get();

        $data = $recipes->map(function ($recipe) {
            return [
                'id' => (string) $recipe->id,
                'recipe_name' => $recipe->name,
                'cooking_time' => (int) $recipe->cooking_time,
                'image_url' => $recipe->image_url,
                'user_id' => (string) $recipe->user_id,
                'user_name' => $recipe->user->name,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
