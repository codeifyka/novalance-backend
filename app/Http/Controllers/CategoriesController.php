<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function getAll(Request $request)
    {
        $categories = Category::all();
        return response()->json([
            "data" => $categories,
        ]);
    }
}
