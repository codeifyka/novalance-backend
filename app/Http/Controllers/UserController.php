<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JobPost;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getInfo(Request $request)
    {
        return response()->json([
            "data" => [
                "user" => [
                    "id" => auth('api')->user()->id,
                    "username" => auth('api')->user()->username,
                    "first_name" => auth('api')->user()->first_name,
                    "last_name" => auth('api')->user()->last_name,
                    "email" => auth('api')->user()->email,
                    "account_type" => auth('api')->user()->account_type,
                    "country" => auth('api')->user()->country,
                    "gender" => auth('api')->user()->gender,
                    "telephone" => auth('api')->user()->telephone,
                ],
                "services" => auth('api')->user()->services->count(),
                "projects" => auth('api')->user()->projects->count(),
            ],
        ]);
    }

    public function updateInfo(Request $request)
    {        
        $users = User::query()->where('username','=',$request['username'])->get();
        if(count($users) > 0 && $request['username'] != auth('api')->user()->username){
            return response()->json([
                "error" => "Username already in use"
            ]);
        }

        if(count($users) > 0 && $request['first_name'] != auth('api')->user()->first_name){
            return response()->json([
                "error" => "First Name already in use"
            ]);
        }
        
        if(count($users) > 0 && $request['last_name'] != auth('api')->user()->last_name){
            return response()->json([
                "error" => "Last Name already in use"
            ]);
        }

        if(count($users) > 0 && $request['country'] != auth('api')->user()->country){
            return response()->json([
                "error" => "Country Name already in use"
            ]);
        }

        if(count($users) > 0 && $request['telephone'] != auth('api')->user()->telephone){
            return response()->json([
                "error" => "Telephone Name already in use"
            ]);
        }

        if(count($users) > 0 && $request['gender'] != auth('api')->user()->gender){
            return response()->json([
                "error" => "Gender Name already in use"
            ]);
        }

        if(count($users) > 0 && $request['email'] != auth('api')->user()->email){
            return response()->json(data: [
                "error" => "Email Name already in use"
            ]);
        }
        
        User::query()->where('id','=', auth('api')->user()->id)->update([
            'username' => $request['username'],
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'email' => $request['email'],
            'gender' => $request['gender'],
            'telephone' => $request['telephone'],
            'country' => $request['country'],
        ]);
        
        return response()->json([
            "data" => auth('api')->user(),
        ]);
    }

    public function getAllJobs()
    {
        $jobs = JobPost::with('categories')->get();
    
        return response()->json(['data' => $jobs]);
    }

    public function getUserById($user_id)
    {
        $user = User::find($user_id);

        if ($user) {
            return response()->json([
                "data" => $user
            ]);
        } else {
            return response()->json([
                "message" => "User not found"
            ], 404);
        }
    }
}
