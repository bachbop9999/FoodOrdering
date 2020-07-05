<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use JWTAuthException;
use Hash;
use App\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{

    // public function getUsers()
    // {
    //     $temp = DB::table('users')->get();
    //     return response()->json($temp, 200);
    // }

    // public function insertUser(Request $request)
    // {
    //     $input = $request->only('username', 'email', 'password', 'fullname', 'phone', 'address');

    //     DB::table('users')->insert(
    //         [
    //             'username' => $input['username'], 'email' => $input['email'], 'password' => $input['password'],
    //             'fullname' => $input['fullname'], 'phone' => $input['phone'],  'address' => $input['address']
    //         ]
    //     );
    //     return response()->json('Insert successgully', 200);
    // }

    public function register(Request $request)
    {
        $input = $request->only('email', 'fullname', 'password','username','phone', 'address');

        $rules = [
            'email' => 'required|email||unique:users',
            'password' => 'required|string|min:6',
            'fullname' => 'required|string',
            'username' => 'required|string||unique:users',
            'phone' => 'required|string',
            'address' => 'required|string',
        ];

        $user = new User();
        $user->email = $input['email'];
        $user->fullname = $input['fullname'];
        $user->username = $input['username'];
        $user->phone = $input['phone'];
        $user->address = $input['address'];
        $user->password = Hash::make($input['password']);
        $user->save();
        
        // $user = $this->user->create([
        //     'fullname' => $request->get('fullname'),
        //     'username' => $request->get('username'),
        //     'phone' => $request->get('phone'),
        //     'address' => $request->get('address'),
        //     'email' => $request->get('email'),
        //     'password' => Hash::make($request->get('password'))
        // ]);

        return response()->json([
            'status' => 200,
            'message' => 'User created successfully',
            'data' => $user
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ];
        $validator = Validator::make($credentials, $rules);
        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        try{
            if(! $token = JWTAuth::attempt($credentials)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'loi2'
                ], 401);
            }
        }catch(JWTException $e){
            return response()->json([
                'status' => 'error',
                'message' => 'loi3'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token
            ]
        ]);
    }
    public function user(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            return response($user, Response::HTTP_OK);
        }

        return response(null, Response::HTTP_BAD_REQUEST);
    }
    // public function getUserInfo(Request $request){
    //     $user = JWTAuth::toUser($request->token);
    //     return response()->json(['result' => $user]);
    // }
}
