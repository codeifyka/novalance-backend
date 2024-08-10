<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FilesUploadController;
use App\Http\Controllers\JobPostController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProposalController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/info',function (Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'HelloWorld',
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'user'
], function () {
    Route::get('/',[UserController::class, 'getInfo']);
    Route::post('/',[UserController::class, 'updateInfo']);
    Route::get('/services',[ServicesController::class, 'getSelfAll']);
    Route::get('/projects',[ProjectsController::class, 'getSelfAll']);
    Route::get('/{username}/services',[ServicesController::class, 'getAll']);
    Route::get('/{username}/projects',[ProjectsController::class, 'getAll']);
    Route::get('job/getAll', [UserController::class, 'getAllJobs']);//i have problem with jobcontroller recources thats why i wrote here
    Route::get('/{user_id}', [UserController::class, 'getUserById']);
});



Route::group([
    'prefix' => 'services',
    'middleware' => 'auth:sanctum',], function () {
    Route::post('upload_images',[FilesUploadController::class, 'uploadServicesImages']);
    Route::post('create',[ServicesController::class, 'create']);
    Route::get('{id}',[ServicesController::class, 'getById']);
    Route::get('search/{title}',[ServicesController::class, 'searchByTitle']);
});

Route::group(['middleware' => 'auth:sanctum'], function(){
    Route::get('/categories',[CategoriesController::class, 'getAll']);
    Route::get('/getAll2',[ServicesController::class, 'getAll2']);
    Route::resource('job', JobPostController::class);
    Route::post('job/upload_files',[FilesUploadController::class, 'uploadJobPostFiles']);
});

Route::group([  'prefix' => 'projects', 
                'middleware' => 'auth:sanctum',],
    function () {
    Route::post('upload_images',[FilesUploadController::class, 'uploadServicesImages']);
    Route::post('create',[ProjectsController::class, 'create']);
    Route::get('{id}',[ProjectsController::class, 'getById']);
});

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);

});


Route::group(
    [  'prefix' => 'proposals', 'middleware' => 'auth:sanctum'],
    function () {
        Route::get('/getMyProposals', [ProposalController::class, 'getMyProposals']);
        Route::post('/create', [ProposalController::class, 'store']);
        Route::get('/getFreelancerProposals', [ProposalController::class, 'getFreelancerProposals']);
        Route::get('/{job_post_id}', [ProposalController::class, 'getByJobPostAndFreelancer']);
        Route::put('{id}/accept', [ProposalController::class, 'acceptProposal']);
    }
);

Route::group(
    [
        'prefix' => 'chats',
        'middleware' =>'auth:sanctum'
    ],
    function () {
        Route::get('/getAll', [ChatController::class, 'index']);
        Route::post('/storeMessage', [ChatController::class, 'storeMessage']);
        Route::post('/getAllMessages', [ChatController::class, 'getAllMessages']);
        Route::post('/setExpiryDate', [ChatController::class, 'setExpiryDate']);
    }
);