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
    public function updatePro(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'Storename' => ['required', 'string'],
            'email' => ['required', 'string'],
            'password' => ['required'],
            're' =>['required']
        ]);

        $user = auth()->user();
        $user->name = $request->input('name');
        $user->Storename = $request->input('Storename');
        $user->email = $request->input('email');
        $s = strlen($request->input('password'));
        $pass = $request->input('password');
        if ($request->input('password') == $request->input('re') && $pass != null) {
            if ($s >= 8) {
                $user->password = Hash::make($request->input('password'));
            } else {
                return response()->json([
                    'message' => 'password has to be more than 8 characters',
                ]);
            }

        } else {
            return response()->json([
                'message' => 'Passwords don`t match',
            ]);
        }

        $user->save();
        return response()->json([
            'message' => 'profile has been updated successfully',
        ]);
    }

}
