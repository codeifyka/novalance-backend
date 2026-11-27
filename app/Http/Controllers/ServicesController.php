<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\ServiceRating;
use App\Models\Category;
use App\Models\ServiceImage;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    public function getSelfAll(Request $request)
    {
        return response()->json([
            "data" => Service::with(['price','category','images'])->withCount('ratings')->where('user_id','=',auth('api')->user()->id)->get(),
        ]);
    }

    public function getById(Request $request)
    {
        $services = Service::with(['price','category','images'])->withCount('ratings')->where('id','=', $request->id)->get();
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

        $services = Service::with(['price','category','images'])->withCount('ratings')->where('user_id','=', $users[0]->id)->get();
        return response()->json([
            "data" => $services,
        ]);
    }

    public function getAll2(Request $request)
    {
        $query = Service::with(['price', 'category', 'images'])->withCount('ratings');

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
        $services = Service::with(['price','category','images'])->withCount('ratings')->where('title','=', $request['title'])->get();
        if(count($services) == 0){
            return response()->json([
                "data" => null,
            ]);
        }

        return response()->json([
            "data" => $services,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $service = Service::with(['price', 'category', 'images'])->find($id);
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        if ($service->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to edit this service'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'price' => 'required|numeric|min:1',
            'images' => 'nullable|array',
        ]);

        $category = Category::where('name', $validated['category'])
            ->orWhere('id', is_numeric($validated['category']) ? $validated['category'] : 0)
            ->first();

        if (!$category) {
            return response()->json(['error' => 'Category does not exist.'], 422);
        }

        // Update Price
        if ($service->price) {
            $service->price->update(['value' => $validated['price']]);
        } else {
            $price = Price::create([
                'value' => $validated['price'],
                'currency_code' => 'USD',
                'currency_symbol' => '$'
            ]);
            $service->price_id = $price->id;
        }

        // Update Service
        $service->title = $validated['title'];
        $service->description = $validated['description'];
        $service->category_id = $category->id;
        $service->save();

        // Update Images if provided
        if (isset($validated['images']) && is_array($validated['images'])) {
            ServiceImage::where('service_id', $service->id)->delete();

            foreach ($validated['images'] as $img) {
                $path = is_array($img) ? ($img['path'] ?? $img['url'] ?? '') : $img;
                $cleanPath = preg_replace('#^https?://[^/]+/storage/#', '', $path);
                $cleanPath = preg_replace('#^/storage/#', '', $cleanPath);
                $cleanPath = ltrim($cleanPath, '/');

                if (!empty($cleanPath)) {
                    ServiceImage::create([
                        'path' => $cleanPath,
                        'service_id' => $service->id,
                    ]);
                }
            }
        }

        $service->load(['price', 'category', 'images']);

        return response()->json([
            'data' => $service,
            'message' => 'Service updated successfully'
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $service = Service::with('price')->find($id);
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        if ($service->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to delete this service'], 403);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($service) {
            $price = $service->price;

            ServiceImage::where('service_id', $service->id)->delete();

            $service->delete();

            if ($price) {
                $priceStillReferenced = Service::where('price_id', $price->id)->exists();
                if (!$priceStillReferenced) {
                    try {
                        $price->delete();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning("Could not delete orphan price {$price->id}: " . $e->getMessage());
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Service deleted successfully'
        ], 200);
    }

    public function rate(Request $request, $id)
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'rate' => 'required|integer|min:1|max:5',
        ]);

        $service = Service::find($id);
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $hasEligiblePurchase = Purchase::where('client_id', $user->id)
            ->where('service_id', $service->id)
            ->whereIn('status', ['delivered', 'completed'])
            ->exists();

        if (!$hasEligiblePurchase) {
            return response()->json([
                'error' => 'You can only rate a service after having at least one delivered or completed purchase.'
            ], 403);
        }

        ServiceRating::updateOrCreate(
            [
                'service_id' => $service->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $validated['rate'],
            ]
        );

        $averageRate = (int) round(ServiceRating::where('service_id', $service->id)->avg('rating'));
        $ratingsCount = ServiceRating::where('service_id', $service->id)->count();
        $service->rate = $averageRate;
        $service->save();

        return response()->json([
            'message' => 'Service rated successfully.',
            'data' => [
                'rate' => $service->rate,
                'ratings_count' => $ratingsCount,
                'user_rating' => (int) $validated['rate'],
            ],
        ], 200);
    }
}
