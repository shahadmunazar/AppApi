<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\Admin\CategoryController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\User\AllTransactionController;
use App\Http\Controllers\API\User\CategoryControllerUser;
use App\Http\Controllers\API\User\PlayGameController;
use App\Http\Controllers\RegisterControllerRefferal;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::post('signup', [RegisterControllerRefferal::class, 'Signup']);

Route::post('login', [RegisterControllerRefferal::class, 'login']);

Route::prefix('user')->middleware(['auth:sanctum', 'check.user.type:user'])->group(function () {

    Route::get('profile', [UserController::class, 'dashboard']);

    Route::get('all-transaction', [AllTransactionController::class, 'alltransaction']);
    Route::post('add-money', [RegisterControllerRefferal::class, 'add_money']);

    //Start of Category
    Route::get('testing', [CategoryControllerUser::class, 'testing']);
    Route::get('get-category', [CategoryControllerUser::class, 'index']);

    Route::get('sub-category', [CategoryControllerUser::class, 'subcategory']);
    Route::get('enter-play-game', [CategoryControllerUser::class, 'play_game']);
    Route::post('calculate-number', [CategoryControllerUser::class, 'crossing_number']);

    Route::post('play-game-set-bet', [PlayGameController::class, 'playGame']);

    Route::get('statement-user', [AllTransactionController::class, 'All_StateMents']);

});

Route::prefix('admin')->middleware(['auth:sanctum', 'check.user.type:admin'])->group(function () {
    //for dashboard Api

    Route::post('admin-logout', [RegisterControllerRefferal::class, 'admin_logout']);

    Route::get('admin-get', [AdminController::class, 'getAdmin']);
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    //Category Routes
    Route::get('get-all-category-list', [CategoryController::class, 'get_All_Category']);
    Route::post('add-category', [CategoryController::class, 'add_category']);
    Route::get('get-category_details/{category_id}', [CategoryController::class, 'GetCategory_Details']);
    Route::put('update-category/{category_id}', [CategoryController::class, 'update_category']);
    Route::delete('delete-category/{category_id}', [CategoryController::class, 'delete_category']);

    //add for sub Category
    Route::get('get-all-subcategory', [CategoryController::class, 'get_subcategory']);
    Route::post('add-sub-category', [CategoryController::class, 'add_subcategory']);
    Route::get('get-subcategory-details/{category_id}', [CategoryController::class, 'GetSubCategory_Details']);
    Route::put('update-subcategory/{category_id}', [CategoryController::class, 'update_subcategory']);
    Route::delete('delete-subcategory/{category_id}', [CategoryController::class, 'delete_subcategory']);
});
