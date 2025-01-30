<?php

namespace App\Http\Controllers;

use App\Traits\files;
use App\Models\JobPost;
use App\Models\Category;
use App\Models\Job_post;
use App\Models\jobPostFiles;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JobPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use files;
    public function index()
    {
        $user = auth('api')->user();
        $Jobs = JobPost::with(['categories'])->where('user_id', $user->id)->get();
        return response()->json(['data'=>$Jobs]);


    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('job.store');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $file = $request->file('files');
            $file_name = time().'_'.'file'.'_'.$file->getClientOriginalName();
            $file->move('uploads/job_files', $file_name);
            $file = 'uploads/illustrative_files/'.$file_name;
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'expected_delivery_time' => 'required|integer'  ,
                'budjet' =>'required|integer' ,
                'size' =>'required' ,
                'level' =>'required' ,
                'skills' =>'required' ,
            ]);
            $user = auth('api')->user(); 
            $job = new JobPost() ; 
            $job->title = strip_tags( $request->input('title'));
            $job->description = strip_tags( $request->input('description'));
            $job->size = strip_tags( $request->input('size'));
            $job->experience_level = strip_tags( $request->input('level'));
            $job->user_id = $user->id;
            $job->expected_delivery_time = strip_tags( $request->input('expected_delivery_time'));
            $job->budjet = strip_tags( $request->input('budjet'));
            if($file){
                $job->illustrative_files = $file;
            }  
            $job->save(); 
            $categories = json_decode($request->skills, true);
            foreach ($categories as $item) {
                $category = Category::where('name', $item['name'])->first();
                    $job->categories()->attach($category->id); 
            }
            return response()->json(["data" => $job]);
        }catch (ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } 
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // $JobPost = JobPost::findOrFail($id);
        $JobPost = JobPost::with(['categories'])->findOrFail($id);

        return response()->json(['data'=>$JobPost]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'expected_delivery_time' => 'required|integer'  ,
                'budjet' =>'required|integer' ,
                'size' =>'required' ,
                'level' =>'required' ,
                'skills' =>'required' ,
            ]);
            $JobPost_update = JobPost::findOrFail($id);  
            $JobPost_update->title = strip_tags( $request->input('title'));
            $JobPost_update->description = strip_tags( $request->input('description'));
            $JobPost_update->size = strip_tags( $request->input('size'));
            $JobPost_update->experience_level = strip_tags( $request->input('level'));
            $JobPost_update->expected_delivery_time = strip_tags( $request->input('expected_delivery_time'));
            $JobPost_update->budjet = strip_tags( $request->input('budjet'));
            $category = Category::where('name', $request->input('skills'))->first();
            $JobPost_update->category_id = $category->id; 
            if($request->input('illustrative_files'))
            $JobPost_update->illustrative_files = strip_tags( $request->input('illustrative_files'));
            else          $JobPost_update->illustrative_files = 'file.pdf';
            $JobPost_update->save();     
            return response()->json(['data'=>$JobPost_update]);
        }catch (ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $JobPost_delete = JobPost::findOrFail($id);  
        $JobPost_delete->delete();
        return response()->json([
            'status' => true,
            'msg' => 'Deleted successfully',
        ]);
    }

    public function getAll()
    {
        // $Jobs = JobPost::all();
        return response()->json(['data'=>'succes']);
    }
    
}
