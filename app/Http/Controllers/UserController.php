<?php

namespace App\Http\Controllers;

use App\Models\User;
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
                    "email" => auth('api')->user()->email
                ],
                "services" => auth('api')->user()->services->count(),
                "projects" => auth('api')->user()->projects->count(),
            ],
        ]);
    }

    public function updateInfo(Request $request)
    {        
        $users = User::query()->where('username','=',$request['username'])->get();
        if(count($users) > 0 && $users[0]->username != auth('api')->user()->username){
            return response()->json([
                "error" => "Username already in use"
            ]);
        }
        
        User::query()->where('id','=', auth('api')->user()->id)->update([
            'username' => $request['username'],
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name']
        ]);
        
        return response()->json([
            "data" => auth('api')->user(),
        ]);
    }
}
