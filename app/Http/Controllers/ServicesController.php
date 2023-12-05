<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Price;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Models\User;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    public function getSelfAll(Request $request)
    {
        return response()->json([
            "data" => Service::with(['price','category','images'])->where('user_id','=',auth('api')->user()->id)->get(),
        ]);
    }

    public function getById(Request $request)
    {
        $services = Service::with(['price','category','images'])->where('id','=', $request->id)->get();
        if(count($services) == 0){
            return response()->json([
                "data" => null,
            ]);
        }

        return response()->json([
            "data" => $services[0],
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

        $services = Service::with(['price','category','images'])->where('user_id','=', $users[0]->id)->get();
        return response()->json([
            "data" => $services,
        ]);
    }

    public function create(Request $request)
    {
        $categories = Category::where('name','=',$request['category'])->get();
        if(count($categories) == 0){
            return response()->json([
                "error" => "Category does not exist."  
            ]);
        }
        
        $price = Price::create([
            "value" => $request['price'],
            "currency_code" => 'USD',
            "currency_symbol" => '$'
        ]);
        
        $service = Service::create([
            "title" => $request['title'],
            "description" => $request['description'],
            "user_id" => auth('api')->user()['id'],
            "category_id" => $categories[0]['id'],
            "price_id" => $price['id'],
            "rate" => 0
        ]);

        foreach($request["images"] as $img){
            ServiceImage::create([
                "path" => $img,
                "service_id" => $service->id
            ]);
        }

        return response()->json([
            "data" => $service
        ]);
    }
}
