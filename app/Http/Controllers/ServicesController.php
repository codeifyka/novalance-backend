<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Price;
use App\Models\Service;
use App\Models\Category;
use App\Models\ServiceImage;
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

    public function getAll2(Request $request)
    {
        $query = Service::with(['price', 'category', 'images']);

        if ($request->filled('category_id')) {
            $query->where('services.category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . strtolower($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(services.title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(services.description) LIKE ?', [$search]);
            });
        }

        $sort = strtolower($request->input('sort', 'newest'));
        if ($sort === 'oldest') {
            $query->orderBy('services.created_at', 'asc');
        } elseif ($sort === 'best price' || $sort === 'best_price' || $sort === 'price_asc') {
            $query->join('prices', 'services.price_id', '=', 'prices.id')
                  ->orderBy('prices.value', 'asc')
                  ->select('services.*');
        } else {
            $query->orderBy('services.created_at', 'desc');
        }

        if ($request->has('page') || $request->has('per_page') || $request->boolean('paginate')) {
            $perPage = (int) $request->input('per_page', 6);
            $paginated = $query->paginate($perPage);

            return response()->json([
                "data" => [
                    "items" => $paginated->items(),
                    "current_page" => $paginated->currentPage(),
                    "last_page" => $paginated->lastPage(),
                    "per_page" => $paginated->perPage(),
                    "total" => $paginated->total(),
                ]
            ]);
        }

        return response()->json([
            "data" => $query->get(),
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

    function searchByTitle(Request $request){
        $services = Service::with(['price','category','images'])->where('title','=', $request['title'])->get();
        if(count($services) == 0){
            return response()->json([
                "data" => null,
            ]);
        }

        return response()->json([
            "data" => $services,
        ]);
    }

}
