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

    Route::post('/logout',[AuthController::class, 'logout']);


});

