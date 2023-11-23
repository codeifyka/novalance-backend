<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectsController extends Controller
{
    public function getSelfAll(Request $request)
    {
        $user = auth('api')->user(); 
        $projects = DB::table('projects')->select('id','title','description')->where('user_id','=',$user->id)->get();

        foreach($projects as $project){
            $images = DB::table('projects_images')->select('*')->where('project_id','=',$project->id)->get();
            $arr = [];
            foreach($images as $img){
                array_push($arr, [
                    "id" => $img->id,
                    "url" => asset('storage/'.$img->path),
                ]);
            }
            $project->images = $arr;
        }

        return response()->json([
            "data" => $projects,
        ]);
    }
    
    public function getById(Request $request)
    {
        $project_id = $request->id;

        $projects = DB::table('projects')->select('id','title','description')->where('id','=',$project_id)->get();
        
        foreach($projects as $project){
            $images = DB::table('projects_images')->select('*')->where('project_id','=',$project->id)->get();
            $arr = [];
            foreach($images as $img){
                array_push($arr, [
                    "id" => $img->id,
                    "url" => asset('storage/'.$img->path),
                ]);
            }
            $project->images = $arr;
        }

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
        $users = DB::table('users')->select('id','username')->where('username','=', $username)->get(); 

        if(count($users) == 0){
            return response()->json([
                "error" => "No user",
                "username" => $username,
            ]);
        }

        $projects = DB::table('projects')->select('id','title','description')->where('user_id','=',$users[0]->id)->get();
        
        foreach($projects as $project){
            $images = DB::table('projects_images')->select('*')->where('project_id','=',$project->id)->get();
            $arr = [];
            foreach($images as $img){
                array_push($arr, [
                    "id" => $img->id,
                    "url" => asset('storage/'.$img->path),
                ]);
            }
            $project->images = $arr;
        }

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
            DB::table('projects_images')->insert([
                "path" => $img,
                "project_id" => $project->id
            ]);
        }

        return response()->json([
            "data" => $project
        ]);
    }
}
