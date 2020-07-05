<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    public function getUsers()
    {
        $temp = DB::table('users')->get();
        return response()->json($temp, 200);
    }

    public function insertUser(Request $request)
    {
        $input = $request->only('username', 'email', 'password', 'fullname', 'phone', 'address');

        DB::table('users')->insert(
            ['username' => $input['username'], 'email' => $input['email'], 'password'=> $input['password'],
            'fullname'=> $input['fullname'], 'phone'=> $input['phone'],  'address'=> $input['address']
            ]
        );
        return response()->json('Insert successgully', 200);
    }

}
