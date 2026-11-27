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
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SavedJobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\Admin\AdminDisputeController;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Controllers\Admin\AdminSettingsController;
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
    Route::delete('/',[UserController::class, 'deleteAccount']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::post('/profile_picture', [UserController::class, 'uploadProfilePicture']);
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
    Route::put('{id}',[ServicesController::class, 'update']);
    Route::post('{id}',[ServicesController::class, 'update']);
    Route::delete('{id}',[ServicesController::class, 'destroy']);
    Route::post('{id}/rate',[ServicesController::class, 'rate']);
    Route::get('search/{title}',[ServicesController::class, 'searchByTitle']);
});

Route::group(['middleware' => 'auth:sanctum'], function(){
    Route::get('/categories/{job_post_id}',[CategoriesController::class, 'getAllByJobPost']);
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
    Route::put('{id}',[ProjectsController::class, 'update']);
    Route::post('{id}',[ProjectsController::class, 'update']);
    Route::delete('{id}',[ProjectsController::class, 'destroy']);
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
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

});


Route::group(
    [  'prefix' => 'proposals', 'middleware' => 'auth:sanctum'],
    function () {
        Route::get('/getMyProposals', [ProposalController::class, 'getMyProposals']);
        Route::post('/create', [ProposalController::class, 'store']);
        Route::get('/getFreelancerProposals', [ProposalController::class, 'getFreelancerProposals']);
        Route::get('/{job_post_id}', [ProposalController::class, 'getByJobPostAndFreelancer']);
        Route::put('{id}/accept', [ProposalController::class, 'acceptProposal']);
        Route::put('{id}/complete', [ProposalController::class, 'completeProposal']);
    }
);

Route::group(
    [
        'prefix' => 'chats',
        'middleware' =>'auth:sanctum'
    ],
    function () {
        Route::get('/getAll', [ChatController::class, 'index']);
        Route::post('/getOrCreate', [ChatController::class, 'getOrCreate']);
        Route::post('/storeMessage', [ChatController::class, 'storeMessage']);
        Route::post('/getAllMessages', [ChatController::class, 'getAllMessages']);
        Route::post('/setExpiryDate', [ChatController::class, 'setExpiryDate']);
    }
);

Route::group(
    [
        'prefix' => 'purchases',
        'middleware' => 'auth:sanctum',
    ],
    function () {
        Route::get('/', [PurchaseController::class, 'index']);
        Route::post('/', [PurchaseController::class, 'store']);
        Route::get('/{id}', [PurchaseController::class, 'show']);
        Route::put('/{id}/complete', [PurchaseController::class, 'complete']);
        Route::put('/{id}/deliver', [PurchaseController::class, 'deliver']);
        Route::put('/{id}/extend', [PurchaseController::class, 'extend']);
    }
);

Route::group(
    [
        'prefix' => 'saved-jobs',
        'middleware' => 'auth:sanctum',
    ],
    function () {
        Route::get('/', [SavedJobController::class, 'index']);
        Route::post('/toggle/{jobPostId}', [SavedJobController::class, 'toggle']);
        Route::get('/check/{jobPostId}', [SavedJobController::class, 'check']);
    }
);

Route::group(
    [
        'prefix' => 'notifications',
        'middleware' => 'auth:sanctum',
    ],
    function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    }
);

Route::group(
    [
        'prefix' => 'wallet',
        'middleware' => 'auth:sanctum',
    ],
    function () {
        Route::get('/', [WalletController::class, 'getMyWallet']);
        Route::post('/deposit/intent', [WalletController::class, 'depositIntent']);
        Route::post('/deposit/confirm', [WalletController::class, 'confirmDeposit']);
        Route::post('/payout-request', [WalletController::class, 'requestPayout']);
    }
);

Route::group(
    [
        'prefix' => 'disputes',
        'middleware' => 'auth:sanctum',
    ],
    function () {
        Route::get('/', [DisputeController::class, 'index']);
        Route::post('/purchase/{purchaseId}', [DisputeController::class, 'storeForPurchase']);
        Route::post('/{proposalId}', [DisputeController::class, 'store']);
    }
);

Route::group(
    [
        'prefix' => 'admin',
        'middleware' => 'auth:sanctum',
    ],
    function () {
        Route::get('/disputes', [AdminDisputeController::class, 'index']);
        Route::get('/disputes/{id}', [AdminDisputeController::class, 'show']);
        Route::post('/disputes/{id}/resolve', [AdminDisputeController::class, 'resolve']);
        Route::post('/proposals/{id}/fail', [ProposalController::class, 'setFailed']);

        Route::get('/payouts', [AdminPayoutController::class, 'index']);
        Route::post('/payouts/{id}/process', [AdminPayoutController::class, 'process']);
        Route::post('/payouts/{id}/reject', [AdminPayoutController::class, 'reject']);

        Route::get('/settings', [AdminSettingsController::class, 'index']);
        Route::put('/settings', [AdminSettingsController::class, 'update']);
    }
);


