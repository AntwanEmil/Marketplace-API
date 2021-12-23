<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use App\Models\User;



class AuthController extends Controller
{
   public function register(Request $request){
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'balance' =>['required'],
            'image' =>['required'],
            'Storename' =>['required','string']
        ]);

        $user = new User;
        $user->name = $fields['name'];
        $user->email = $fields['email'];
        $user->password = Hash::make( $fields['password']);
        $user->balance = $fields['balance'];
        $user->Storename = $fields['Storename'];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $exten = $file->getClientOriginalExtension();
            $filename = time() . '.' . $exten;
            $file->move('uploads/users/', $filename);
            $user->image = $filename;
        }

        $user->save();
        $token = $user->createToken('myapptoken')->plainTextToken;

        $reponse = [
            'user' => $user,
            'token'=> $token
        ];

    return  response()->json($reponse, 201);
    }
}
