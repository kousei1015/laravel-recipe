<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Relationship;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 投稿したレシピ
     */
    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * お気に入り登録したレシピ
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * 自分がフォローしているユーザーとのリレーション
     */
    public function relationships()
    {
        return $this->hasMany(Relationship::class, 'follower_id');
    }

    /**
     * 自分をフォローしてくれているユーザーとのリレーション（必要なら）
     */
    public function followers()
    {
        return $this->hasMany(Relationship::class, 'followed_id');
    }

    /**
     * 自分がフォローしているユーザー一覧（Userモデルのコレクション）
     */
    public function followings()
    {
        return $this->belongsToMany(
            User::class,
            'relationships',
            'follower_id',
            'followed_id'
        )->select('users.id', 'users.name', 'users.avatar_url'); // ← 明示的にカラムを限定
    }


    /**
     * 自分をフォローしているユーザー一覧（Userモデルのコレクション）
     */
    public function followersUsers()
    {
        return $this->belongsToMany(
            User::class,
            'relationships',
            'followed_id',
            'follower_id'
        );
    }

    // $user->followings; // 自分がフォローしているユーザー一覧
    // $user->followersUsers; // 自分をフォローしているユーザー一覧

}
