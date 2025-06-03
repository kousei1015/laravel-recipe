<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RelationshipController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\OAuthController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/all', [RecipeController::class, 'all']); // ← これを先に書かないとエラーでる

Route::get('/recipes/search', [RecipeController::class, 'searchByTags']);
Route::get('/recipes/{id}', [RecipeController::class, 'show']);

// ユーザーの詳細情報
Route::get('/users/{id}', [UserController::class, 'userInfo']);

Route::get('/users/{id}/followings', [RelationshipController::class, 'userFollowings']);
Route::get('/users/{id}/followers', [RelationshipController::class, 'userFollowers']);

// ミドルウェア
Route::middleware(['auth:sanctum', 'verified'])->get('/user', function (Request $request) {
    return $request->user();
});

// googleアカウント連携
Route::get('/auth/redirect/{provider}', [OAuthController::class, 'redirect']);
Route::get('/auth/callback/{provider}', [OAuthController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    // レシピ作成
    Route::post('/recipes', [RecipeController::class, 'store']);

    // レシピ更新
    Route::put('/recipes/{id}', [RecipeController::class, 'update']);

    // レシピ削除
    Route::delete('/recipes/{id}', [RecipeController::class, 'destroy']);

    // ユーザーごとのレシピを表示
    Route::get('/users/{id}/recipes', [RecipeController::class, 'userRecipes']);

    // お気に入り機能
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);

    // フォロー関係
    Route::get('/myfollowings', [RelationshipController::class, 'myFollowings']);
    Route::get('/myfollowers', [RelationshipController::class, 'myFollowers']);
    Route::post('/relationships', [RelationshipController::class, 'store']);
    Route::delete('/relationships/{id}', [RelationshipController::class, 'destroy']);
});
Route::middleware('auth:sanctum')->put('/profile', [UserController::class, 'update']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/current_user_info', function (Request $request) {
    $user = Auth::guard('sanctum')->user(); // ここでSanctumトークンを明示的に使って取得

    if (!$user) {
        return response()->json([
            'is_login' => false
        ]);
    }

    return response()->json([
        'is_login' => true,
        'user_id' => (string) $user->id,
        'user_name' => $user->name,
        'avatar_url' => $user->avatar_url,
    ]);
});


Route::middleware(['auth:sanctum', 'verified'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {

    // メール認証リンククリック時にフロントが呼ぶAPI
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request, $id, $hash) {
        // リクエストユーザーとIDのユーザーが一致するかチェック
        if ($request->user()->id != $id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // すでに認証済みならそのまま返す
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified.']);
        }

        // メールアドレス認証完了
        $request->fulfill();

        return response()->json(['message' => 'Email verified successfully.']);
    })->name('verification.verify');

    // 確認メール再送API（オプション）
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent!']);
    })->name('verification.send')->middleware('throttle:6,1');
});
