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
        $input = $request->only('name', 'email', 'password');

        DB::table('users')->insert(
            ['name' => $input['name'], 'email' => $input['email'], 'password'=> $input['password']]
        );
        return response()->json('Insert successgully', 200);
    }

}
