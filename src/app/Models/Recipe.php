<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'name',
        'cooking_time',
        'image_url',
        'user_id',
    ];

    // リレーションも忘れずに
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')->withPivot('quantity');
    }

    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function instructions()
    {
        return $this->hasMany(Instruction::class);
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_tags');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'recipe_tags');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}
