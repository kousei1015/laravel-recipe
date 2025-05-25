<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    // App\Models\Favorite.php

    public function recipe()
    {
        return $this->belongsTo(\App\Models\Recipe::class);
    }
}
