<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
 public function show($UserID){
        if(!$UserID){
            return response()->json([
                'message' =>'store id is required'
            ],244);
        }
        
        if(User::where('id','=',$UserID)->exists()){
        $info = User::where('id','=',$UserID)
        ->select('users.image as userImage','users.Storename','users.name as userName','users.email')
        ->first();
        
        $items = Item::select('items.*')->where('owner_id','=',$UserID)->get();
        $sold_items = DB::table('sellers')->where('sellers.seller_id', '=', $UserID)
        ->join('items', 'sellers.item_id', '=', 'items.id')
        ->join('users', 'items.owner_id','=', 'users.id')
        ->select ('items.*' , 'users.Storename')
        ->get();
        //return view('user.StoreScreen',['info'=>$info, 'items'=>$items ,'sold_items'=>$sold_items, 'user'=>$user , 'id'=>$UserID ]);
        return response()->json([
            'info of the store' => $info,
            'items'=> $items,
            'sold_items of other stores' => $sold_items
        ]);
    }
    else{
        return response()->json([
            'message' => 'there is no such a store'
        ]);
    }
    }
}
