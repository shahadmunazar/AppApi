<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\Admin\CategoryController;
use App\Http\Controllers\API\Admin\PlayedGameController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\User\AllTransactionController;
use App\Http\Controllers\API\User\CategoryControllerUser;
use App\Http\Controllers\API\User\PlayGameController;
use App\Http\Controllers\RegisterControllerRefferal;
use Database\Seeders\PlayGamesTableSeeder;
use Illuminate\Support\Facades\Route;

Route::post('signup', [RegisterControllerRefferal::class, 'signup']);
Route::post('login', [RegisterControllerRefferal::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // User Routes
    Route::prefix('user')->middleware(['check.user.type:user'])->group(function () {
        Route::get('profile', [UserController::class, 'dashboard']);
        Route::get('all-transaction', [AllTransactionController::class, 'alltransaction']);
        Route::post('add-money', [RegisterControllerRefferal::class, 'add_money']);


        Route::post('withdrawal-money-request', [PlayGameController::class, 'Request_money']);

        Route::post('add-money-to-wallet', [PlayGameController::class, 'Add_money']);

        Route::get('all-money-added-request', [PlayGameController::class, 'Request_Add_money_list']);

        Route::put('payment-confirmation/{payment_id}', [PlayGameController::class, 'confirm_payment']);

        //for All Transaction Statement

        Route::get('all-transaction', [PlayGameController::class, 'AllTransaction']);
        Route::get('withdrawal-money', [PlayGameController::class, 'WithdrawalMoney']);
        Route::get('add-money-list', [PlayGameController::class, 'AddMoneyList']);
        Route::get('won-money-list', [PlayGameController::class, 'WonMoneyList']);


        // Category Routes
        Route::get('testing', [CategoryControllerUser::class, 'testing']);
        Route::get('get-category', [CategoryControllerUser::class, 'index']);
        Route::get('sub-category', [CategoryControllerUser::class, 'subcategory']);
        Route::get('enter-play-game', [CategoryControllerUser::class, 'play_game']);
        Route::post('calculate-number', [CategoryControllerUser::class, 'crossing_number']);
        Route::post('play-game-set-bet', [PlayGameController::class, 'playGame']);
        Route::get('statement-user', [AllTransactionController::class, 'All_StateMents']);
    });

    // Admin Routes
    Route::prefix('admin')->middleware(['check.user.type:admin'])->group(function () {
        Route::post('admin-logout', [RegisterControllerRefferal::class, 'admin_logout']);
        Route::get('admin-get', [AdminController::class, 'getAdmin']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // Category Routes

        Route::post('open-current-number', [PlayedGameController::class, 'played_game']);

        Route::get('get-all-category-list', [CategoryController::class, 'get_All_Category']);
        Route::post('add-category', [CategoryController::class, 'add_category']);
        Route::get('get-category_details/{category_id}', [CategoryController::class, 'GetCategory_Details']);
        Route::put('update-category/{category_id}', [CategoryController::class, 'update_category']);
        Route::delete('delete-category/{category_id}', [CategoryController::class, 'delete_category']);

        // Sub Category Routes
        Route::get('get-all-subcategory', [CategoryController::class, 'get_subcategory']);
        Route::post('add-sub-category', [CategoryController::class, 'add_subcategory']);
        Route::get('get-subcategory-details/{category_id}', [CategoryController::class, 'GetSubCategory_Details']);
        Route::put('update-subcategory/{category_id}', [CategoryController::class, 'update_subcategory']);
        Route::delete('delete-subcategory/{category_id}', [CategoryController::class, 'delete_subcategory']);
    });
});
