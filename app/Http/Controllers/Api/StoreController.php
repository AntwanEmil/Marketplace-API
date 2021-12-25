<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
//update the report
public function update_report($op, $item , $user , $store , $transfered_cash){
    // adding description to transactions table //
    $name = $item->name;


    if($op == 'buyItem'){
        $description_add =  'you purchased a(an) "' . $name  .  '"  with price= '.$item->price.'$'.', from store: '.$store->Storename;
        $description_add_recipient =  $user->name.' purchased your item "' . $name  .  '"  with price= '.$item->price.'$';
        $recipient_transaction_id = DB::table('transactions')->insertGetId([
            'description' => $description_add_recipient
        ]);
        $transaction_id = DB::table('transactions')->insertGetId([
            'description' => $description_add
        ]);


    }
    else if ($op == 'addItem'){
        $description_add =  'you added a(an) "' . $name  .  '"  with price= '.$item->price.'$ to be sold in your store'.', from store: '.$store->Storename;
        $description_add_recipient = 'store:'.$user->Storename.'added your item "' . $name  .  '"  with price= '.$item->price.'$'.'to be sold in his store';
       
        $transaction_id = DB::table('transactions')->insertGetId([
            'description' => $description_add
        ]);

        $recipient_transaction_id = DB::table('transactions')->insertGetId([
            'description' => $description_add_recipient
        ]);

    }
    else if ($op == 'removeItem'){
        $description_add =  'you removed a(an) "' . $name  .  '"  with price= '.$item->price.'$ from your store';
        $transaction_id = DB::table('transactions')->insertGetId([
            'description' => $description_add
        ]);
    }
    else if ($op == 'transferCash'){

        $description_add =  'you transfered amount of cash =' .$transfered_cash.'$ from your store to store: "'.$store->Storename.'" that is owned by: '.$store->name;
        $description_add_recipient = 'store:'.$user->Storename.' who is owned by '.$user->name .' transferred amount of cash= '.$transfered_cash.'$ to you (congrats)';

        $transaction_id = DB::table('transactions')->insertGetId([
            'description' => $description_add
        ]);
        $recipient_transaction_id = DB::table('transactions')->insertGetId([
            'description' => $description_add_recipient
        ]);

        DB::table('transferred_cash')->insert([ 'from_user_id'=>$user->id, 'to_user_id'=>$store->id, 'amount'=>$transfered_cash ]);
    }

    // adding mapping info to reports table //
    DB::table('reports')->insert(
        ['transaction_id' => $transaction_id, 'user_id' => $user->id]
    );
    if($op != 'removeItem'){
    DB::table('reports')->insert(
        ['transaction_id' => $recipient_transaction_id, 'user_id' => $store->id]
    );
     }

}

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
        if($item->owner_id == $user-> id)return response()->json(['message' => 'Error, you are the owner of this item'],481);
        if ( DB::table('sellers')->select('sellers.*')->where([['item_id','=',$item->id] , ['seller_id','=',$user->id]])->exists()) return response()->json(['message' => 'Error, you already selling this item'],482);
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
    public function removeSoldItem(Request $request){
        $fields = $request->validate([
            'item_id' => ['required', 'string', 'max:255']
        ]);
        $user = auth()->user();
        if (Item::where('id', $fields['item_id'])->exists()) {
            $item = Item::select('items.*')->where('id',$fields['item_id'])->get()->first();
            if ( DB::table('sellers')->select('sellers.*')->where([['item_id','=',$item->id] , ['seller_id','=',$user->id]])->exists())
                {
            DB::table('sellers')->select('sellers.*')->where([['item_id','=',$item->id] , ['seller_id','=',$user->id]])->delete();
            $user->save();
            $op  = 'removeItem';
            $this-> update_report($op, $item , $user , $user , $item->price);
            return response()->json([
                'message' =>  'success ,Item has been removed from your sold items ',

            ],201);}
            else return response()->json([
                'message' => 'Error , you are not selling this item'
            ],481);
        }
       else return response()->json([
           'message' => 'Error , not a valid item_id '
       ],480);


    }

public function transferCash(Request $request){
    $user = auth()->user();
    $fields = $request->validate([
        'store_id' => ['required'],
        'amount' => ['required', 'integer']
    ]);
    $store = User::select('users.*')->where('id',$fields['store_id'])->get()->first();
    if (!$store) return 'Error , store_id doesn\'t exist';
    if ($store->id == $user->id) return response()->json([
        'message' =>  'Error , you can\'t transfer money to yourself'
    ],478);
    $transfered_cash = $fields['amount'];
    if($transfered_cash > $user->balance) return response()->json([
        'message' =>  'Error , your balance is less than the amount to be transferred'
    ],479);
    else{
    $new_doner_balance = $user->balance - $request->input('transfer');
    $new_store_balance = $store-> balance +  $request->input('transfer');
    DB::table('users')
    ->where('id', $user->id)
    ->update(['balance' => $new_doner_balance]);
  
  DB::table('users')
    ->where('id', $store->id)
    ->update(['balance' => $new_store_balance]);
  
  
    $user->save();
    $store->save();
    $op  = 'transferCash';
    $this-> update_report($op, $user , $user , $store , $transfered_cash);


    $reponse = [
        'message' => 'success ,Cash transferred succesfully',
        'transferred cash = ' => $transfered_cash,
        'cash transferred to store :'=> $store->Storename,
        'owned by :' => $store->name
    ];
    return  response()->json($reponse, 201);
        }
    }
public function report(Request $request){

    $user = auth()->user();
    $reports = DB::table ('reports')->where ('user_id','=',$user->id)
            ->join('transactions','reports.transaction_id','=','transactions.id')
            ->select('transactions.date','transactions.description')
            ->orderBy('transactions.date', 'desc')
            ->get();

    $transfers = DB::table ('transferred_cash')->where('from_user_id','=',$user->id)->get();
    $reponse = [
        'reports' => $reports,
        'transfers'=> $transfers
    ];
    return  response()->json($reponse, 201);

    }

}
