<?php

namespace App\Http\Controllers;

use App\Models\Relationship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RelationshipController extends Controller
{
    /**
     * フォロー作成
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $followedId = $request->input('user_id');

        if ($currentUser->id == $followedId) {
            return response()->json(['error' => '自分自身をフォローすることはできません。'], 422);
        }

        $existing = $currentUser->relationships()->where('followed_id', $followedId)->first();
        if ($existing) {
            return response()->json(['error' => 'すでにフォローしています。'], 409);
        }

        $follow = $currentUser->relationships()->create([
            'followed_id' => $followedId
        ]);

        return response()->json($follow, 201);
    }

    /**
     * フォロー解除
     */
    public function destroy($userId)
    {
        $currentUser = Auth::user();

        // フォロー関係があるか確認
        $follow = $currentUser->relationships()->where('followed_id', $userId)->first();

        if (!$follow) {
            return response()->json(['error' => 'フォロー関係が見つかりません。'], 404);
        }

        $follow->delete();

        return response()->json(null, 204);
    }



    // 自分がフォローしているユーザー一覧 (GET /myfollowings)
    public function myFollowings(Request $request)
    {
        $user = $request->user();

        // ログインユーザーがフォローしているユーザーIDのリスト
        $followingIds = Relationship::where('follower_id', $user->id)
            ->pluck('followed_id')
            ->toArray();

        $relationships = Relationship::where('follower_id', $user->id)
            ->with('followed:id,name,avatar_url')
            ->get();

        $result = $relationships->map(function ($rel) use ($followingIds) {
            return [
                'id' => (string) $rel->id,
                'follower_id' => (string) $rel->follower_id,
                'followed_id' => (string) $rel->followed_id,
                'user_name' => $rel->followed->name,
                'avatar_url' => $rel->followed->avatar_url,
                'already_following' => in_array($rel->followed_id, $followingIds),
            ];
        });

        return response()->json($result);
    }



    // 自分をフォローしているユーザー一覧 (GET /myfollowers)
    public function myFollowers(Request $request)
    {
        $user = $request->user();

        // ログインユーザーがフォローしているユーザーIDのリストを取得（一度だけ）
        $followingIds = Relationship::where('follower_id', $user->id)
            ->pluck('followed_id')
            ->toArray();

        $relationships = Relationship::where('followed_id', $user->id)
            ->with('follower:id,name,avatar_url')
            ->get();

        $result = $relationships->map(function ($rel) use ($followingIds) {
            return [
                'id' => (string) $rel->id,
                'follower_id' => (string) $rel->follower_id,
                'followed_id' => (string) $rel->followed_id,
                'user_name' => $rel->follower->name,
                'avatar_url' => $rel->follower->avatar_url,
                // ログインユーザーがこのユーザーをフォローしているかどうか
                'already_following' => in_array($rel->follower_id, $followingIds),
            ];
        });

        return response()->json($result);
    }

    // 指定ユーザーのフォローしているユーザー一覧 (GET /users/{id}/followings)
    public function userFollowings(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $loginUser = $request->user();

        // ログインユーザーがフォローしているユーザーID一覧
        $myFollowingIds = $loginUser
            ? Relationship::where('follower_id', $loginUser->id)->pluck('followed_id')->toArray()
            : [];

        // 指定ユーザーがフォローしているユーザーとの中間テーブル情報を取得
        $relationships = Relationship::where('follower_id', $targetUser->id)
            ->with('followed:id,name,avatar_url')
            ->get();

        $result = $relationships->map(function ($rel) use ($myFollowingIds) {
            return [
                'id' => (string) $rel->id,
                'follower_id' => (string) $rel->follower_id,
                'followed_id' => (string) $rel->followed_id,
                'user_name' => $rel->followed->name,
                'avatar_url' => $rel->followed->avatar_url,
                'already_following' => in_array($rel->followed_id, $myFollowingIds),
            ];
        });

        return response()->json($result);
    }



    // 指定ユーザーのフォロワー一覧 (GET /users/{id}/followers)
    public function userFollowers(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $loginUser = $request->user();

        // ログインユーザーがフォローしているユーザーIDの一覧を取得
        $myFollowingIds = $loginUser
            ? Relationship::where('follower_id', $loginUser->id)->pluck('followed_id')->toArray()
            : [];

        // 指定ユーザーをフォローしているユーザーとの関係を取得
        $relationships = Relationship::where('followed_id', $targetUser->id)
            ->with('follower:id,name,avatar_url')
            ->get();

        $result = $relationships->map(function ($rel) use ($myFollowingIds) {
            return [
                'id' => (string) $rel->id,
                'follower_id' => (string) $rel->follower_id,
                'followed_id' => (string) $rel->followed_id,
                'user_name' => $rel->follower->name,
                'avatar_url' => $rel->follower->avatar_url,
                'already_following' => in_array($rel->follower_id, $myFollowingIds),
            ];
        });

        return response()->json($result);
    }
}
