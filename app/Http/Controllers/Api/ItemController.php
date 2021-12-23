<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Item;

class ItemController extends Controller
{
    public function ViewItem($id)
    {
      
        if (Item::where('id', $id)->exists()) {
            $item = Item::where('items.id', $id)
            ->join('users','users.id','=','items.owner_id')
            ->select('items.*','users.Storename')
            ->first();
            
            return response()->json([
                'item: ' => $item
            ]);

           // return view('products.ProductDetails', ['item' => $item]);
        } else {
          
           return response()->json([
               'message' =>'fail ,No such a product'
           ]);
        }
    
    }
    
    public function search(Request $request)
    {
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:255']
        ]);
        $search_text= $fields['name'];
        $items=Item::where('items.name','LIKE','%'.$search_text.'%')
         ->join('users', 'items.owner_id','=','users.id')
        ->select ('items.*' , 'users.Storename')->get();
        return $items;
    }

 //view all products (that doesn't belong to the user)
    public function products(Request $request){
        $user = auth()->user();
        $orig_items = Item::select('items.*')->where('owner_id','!=',$user->id)
        ->join('users', 'items.owner_id','=','users.id')
        ->select ('items.*' , 'users.Storename')
        ->get();  
        return response()->json([
            'data: ' => $orig_items
        ]) ;
    }

    
//add new product
    public function store(Request $request){
        if (auth()->user()) {
            $user = auth()->user();
            $fields = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['required' , 'string'],
                'price' => ['required' ,'integer'],
                'amount' => ['required' , 'integer']
            ]);
            $item = new Item;
            $item->name = $fields['name'];
            $item->price =  $fields['price'];
            $item->amount = $fields['amount'];
            $item->description = $fields['description'];
            $item->owner_id = $user->id;
        
        if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $exten = $file->getClientOriginalExtension();
                    $filename = time() . '.' . $exten;
                    $file->move('upload/items/', $filename);
                    $item->image = $filename;
                }

                $item->save();
                $reponse = [
                    'message' => 'success , The item is added successfully',
                    'item'=> $item
                ];
            $op  = 'store';
            $this->update_report($op, $item,$user);
            return  response()->json($reponse, 201);
            }
    }
    //update product
    public function Update(Request $request)
    {      
        $user = auth()->user();
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required' , 'string'],
            'price' => ['required' ,'integer'],
            'amount' => ['required' , 'integer'],
            'id' =>['required']
        ]);
        if (Item::where('id', $fields['id'])->exists()) {
            $item = Item::where('id', $fields['id'])->first();
            if($item->owner_id == $user->id){
            $item->name = $fields['name'];
            $item->price =  $fields['price'];
            $item->amount = $fields['amount'];
            $item->description = $fields['description'];
            $item->owner_id = $user->id;
            if ($request->hasFile('image')) {
              
                $file = $request->file('image');
                $exten = $file->getClientOriginalExtension();
                $filename = time() . '.' . $exten;
                $file->move('upload/items/', $filename);
                $item->image = $filename;
            }
            $item->save();
            $reponse = [
                'message' => 'success , The item is updated successfully',
                'item'=> $item
            ];
            $op  = 'update';
            $this->update_report($op, $item,$user);
            return  response()->json($reponse, 201);
             }

        else  {
            $reponse = [
                'message: ' => 'fail this item doesn\'t belong to your store'
            ];
            return response()->json($reponse, 214);
        } 
        }
        else{
          $reponse = [
                'message: ' => 'fail No such an item'
            ];
            return response()->json($reponse, 215);
        }
      
}
