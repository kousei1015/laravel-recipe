<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    //
    // app/Models/RecipeIngredient.php

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
