<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\JobPost;

class CategoriesController extends Controller
{
    public function getAll(Request $request)
    {
        $categories = Category::all();
        return response()->json([
            "data" => $categories,
        ]);
    }

    public function getAllByJobPost($jobPostId)
{
    $jobPost = JobPost::with('categories')->find($jobPostId);

    if (!$jobPost) {
        return response()->json([
            "message" => "Job post not found",
            "data" => []
        ], 404);
    }

    return response()->json([
        "data" => $jobPost->categories,
    ]);
}
}
