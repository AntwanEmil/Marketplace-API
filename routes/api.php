<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use App\Models\Item;


use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\StoreController;
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


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//register & login
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login' , [AuthController::class , 'login']);


Route::group(['middleware' => ['auth:sanctum']], function(){
    // functions that require authentiaction
    Route::post('/addProduct', [ItemController::class, 'store']);
    Route::post('/updateProduct', [ItemController::class, 'Update']);
    Route::post('/delete', [ItemController::class, 'destroy']);
    //Route::post('/BuyProduct/{id}',[ItemController::class,'ViewForBuy']);
    Route::post('/BuyProduct',[StoreController::class, 'buyItem']);
    Route::get('/products' , [ItemController::class , 'products']);
    Route::post('/search' , [ItemController::class , 'search']);
    
    Route::get('store/{id}', [StoreController::class,'show']);
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::post('/updateProfile',[ProfileController::class, 'updatePro']);
    Route::get('ProductDetail/{id}', [ItemController::class, 'ViewItem']);
    
    Route::post('/AddProductForSale',[StoreController::class,'addItem']);
    Route::post('/RemoveSoldProduct',[StoreController::class,'removeSoldItem'])->name('removeSoldItem');
    Route::post('/report', [StoreController::class, 'report' ]);
    Route::post('/transferCash',[StoreController::class,'transferCash'])->name('transferCash');
    Route::post('/logout',[AuthController::class, 'logout']);


});

