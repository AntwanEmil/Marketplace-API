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
    
     public function addItem(Request $request){
        $fields = $request->validate([
            'item_id' => ['required', 'string', 'max:255']
        ]);

        $user = auth()->user();
        if (Item::where('id', $fields['item_id'])->exists()) {
        $item = Item::select('items.*')->where('id',$fields['item_id'])->get()->first();
        if($item->owner_id == $user-> id) return 'Error you already own this item';
        $owner = User::select("users.*")->where('id', $item->owner_id)->get()->first();
        DB::table('sellers')->insert(
            ['item_id' => $item->id, 'owner_id' =>$owner->id , 'seller_id' => $user->id]
        );
        $user->save();
        $owner->save();
        $item->save();
        $op  = 'addItem';
        $this-> update_report($op, $item , $user , $owner , $item->price);
        return 'success Item has been added to be sold on your store';
        }
        else return 'Error , not a valid item_id';


    }
    
    public function buyItem(Request $request){
        $user = auth()->user();
        $fields = $request->validate([
            'item_id' => ['required'],
            'amount' => ['required', 'integer']
        ]);
        if(Item::where('id',$request['item_id'])->exists()){
        $item = Item::select('items.*')->where('id',$fields['item_id'])->get()->first();
        $qty = $fields['amount'];
        $owner = User::select("users.*")->where('id', $item->owner_id)->get()->first();



        if($user->balance < ($item->price * $qty)){
            return response()->json([
                'message' => 'fail You don\'t have enough balance :(( '
            ],476);
            
        }
        if($qty > $item->amount){
            return response()->json([
                'message' => 'fail , The quantity is greater than the available amount !! '
            ],478);
           
        }
        DB::table('purshased_items')->insert(
            ['user_id'=>$user->id, 'item_id' => $item->id, 'amount' =>$qty]
        );
        $user->balance -= ($item->price * $qty);
        $owner->balance += ($item->price * $qty);
        $item->amount -= $qty;
        $user->save();
        $owner->save();
        $item->save();

        $reponse = [
            'message' => 'success , Your order has been done successfully',
            'item'=> $item->name
        ];
        $op  = 'buyItem';
        $this-> update_report($op, $item , $user , $owner , $item->price);
        return  response()->json($reponse, 201);
    }
    else{
        return response()->json([
            'message' => 'item is not found',
            
        ],477);
    }
    }
}
