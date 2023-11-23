<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\FilesUploadController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ServicesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/services',[ServicesController::class, 'getSelfAll']);
    Route::get('/projects',[ProjectsController::class, 'getSelfAll']);
    Route::get('/{username}/services',[ServicesController::class, 'getAll']);
    Route::get('/{username}/projects',[ProjectsController::class, 'getAll']);
});


Route::get('/categories',[CategoriesController::class, 'getAll']);

Route::group(['prefix' => 'services'], function () {
    Route::post('upload_images',[FilesUploadController::class, 'uploadServicesImages']);
    Route::post('create',[ServicesController::class, 'create']);
});

Route::group(['prefix' => 'projects'], function () {
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
