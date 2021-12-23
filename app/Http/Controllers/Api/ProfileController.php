<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    //
    public function index()
    {
        
            $user = auth()->user();
            $items = Item::select('items.*')->where('owner_id', '=', $user->id)->get(); //added products section

            $purchased_items = DB::table('purshased_items')->where('purshased_items.user_id', '=', $user->id)
                ->join('items', 'purshased_items.item_id', '=', 'items.id')
                ->join('users', 'items.owner_id', '=', 'users.id')
                ->select('items.*', 'purshased_items.amount', 'users.Storename')
                ->get();

            $sell_item = DB::table('sellers')->where('sellers.seller_id', '=', $user->id)
                ->join('items', 'sellers.item_id', '=', 'items.id')
                ->select('items.*')
                ->get();
           // return view('user.ProfileScreen', ['items' => $items, 'user' => $user, 'purchased' => $purchased_items,
               // 'selled' => $sell_item]);

               return response()->json([
                   'my items: ' => $items,
                   'purchased items: ' => $purchased_items,
                   'sell item of other stores: ' => $sell_item
               ]);
        
    }

}
