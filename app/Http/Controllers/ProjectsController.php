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
}
