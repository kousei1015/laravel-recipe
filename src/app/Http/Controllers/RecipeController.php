<?php

namespace App\Http\Controllers;

use App\Models\Recipe;

class RecipeController extends Controller
{
    public function index()
    {
        return Recipe::with(['tags', 'ingredients'])->get();
    }
}

