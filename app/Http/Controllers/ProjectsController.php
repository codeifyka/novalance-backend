<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function getSelfAll(Request $request)
    {
        return response()->json([
            "data" => Project::with(['images'])->where('user_id','=',auth('api')->user()->id)->get(),
        ]);
    }
    
    public function getById(Request $request)
    {
        $projects = Project::with(['images'])->where('id','=', $request->id)->get();
        if(count($projects) == 0){
            return response()->json([
                "data" => null,
            ]);
        }

        return response()->json([
            "data" => $projects[0],
        ]);
    }

    public function getAll(Request $request)
    {
        $username = request('username');
        $users = User::where('username','=', $username)->get(); 

        if(count($users) == 0){
            return response()->json([
                "error" => "No user",
                "username" => $username,
            ]);
        }

        $projects = Project::with(['images'])->where('user_id','=', $users[0]->id)->get();
        return response()->json([
            "data" => $projects,
        ]);
    }

    public function create(Request $request)
    {
        $project = Project::create([
            "title" => $request['title'],
            "description" => $request['description'],
            "user_id" => auth('api')->user()['id'],
        ]);

        foreach($request["images"] as $img){
            ProjectImage::create([
                "path" => $img,
                "project_id" => $project->id
            ]);
        }

        return response()->json([
            "data" => $project
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $project = Project::with(['images'])->find($id);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        if ($project->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to edit this project'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'images' => 'nullable|array',
        ]);

        $project->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        if ($request->has('images')) {
            $incomingImages = $request->input('images', []);

            ProjectImage::where('project_id', $project->id)
                ->whereNotIn('path', $incomingImages)
                ->delete();

            $existingPaths = ProjectImage::where('project_id', $project->id)
                ->pluck('path')
                ->toArray();

            foreach ($incomingImages as $imgPath) {
                if (!in_array($imgPath, $existingPaths)) {
                    ProjectImage::create([
                        'path' => $imgPath,
                        'project_id' => $project->id,
                    ]);
                }
            }
        }

        $project->load('images');

        return response()->json([
            'data' => $project,
            'message' => 'Project updated successfully'
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $project = Project::find($id);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        if ($project->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to delete this project'], 403);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($project) {
            ProjectImage::where('project_id', $project->id)->delete();
            $project->delete();
        });

        return response()->json([
            'message' => 'Project deleted successfully'
        ], 200);
    }
}
