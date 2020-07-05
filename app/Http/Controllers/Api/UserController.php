<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Hash;
use App\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function getUsers()
    {
        $temp = DB::table('users')->get();
        return response()->json($temp, Response::HTTP_OK);
    }



    public function register(Request $request)
    {
        $input = $request->only('email', 'fullname', 'password', 'username', 'phone', 'address');

        $rules = [
            'email' => 'required|email||unique:users',
            'password' => 'required|string|min:6',
            'fullname' => 'required|string',
            'username' => 'required|string|unique:users',
            'phone' => 'required|string|min:10|max:10',
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

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }

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
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'loi2'
                ], 401);
            }
        } catch (JWTException $e) {
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

    public function logout(Request $request)
    {
        $token = $request->header('Authorization');
        try {
            JWTAuth::invalidate($token);
            return response()->json([
                'status' => 'success',
                'message' => 'User log out successfully'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fail to log out, please try again'
            ], 500);
        }
    }
    public function user(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            return response($user, Response::HTTP_OK);
        }

        return response(null, Response::HTTP_BAD_REQUEST);
    }
}
