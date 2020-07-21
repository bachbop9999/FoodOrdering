<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailController;
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
        $input = $request->only('email', 'password', 'username', 'fullname', 'phone');

        $rules_require = [
            'email' => 'required',
            'password' => 'required|string',
            'fullname' => 'required|string',
            'username' => 'required|string',
            'phone' => 'required|numeric'
            // 'phone' => 'string|min:10|max:10',
            // 'address' => 'string',
        ];
        
        $rules_other = [
            'email' => 'email|unique:users',
            'password' => 'min:6',
            'username' => 'unique:users',
            'phone' => 'digits:10',
            // 'address' => 'string',
        ];
        $validator = Validator::make($input, $rules_require);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'require',
                'message' =>  $validator->getMessageBag()
            ]);

        }
        $validator_other = Validator::make($input, $rules_other);
        if ($validator_other->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator_other->getMessageBag()
            ]);

        }
        $user = new User();
        $user->email = $input['email'];
        // $user->fullname = $input['fullname']?$input['fullname']:null;
        $user->username = $input['username'];
        $user->phone = $input['phone'];
        // $user->address = $input['address']?$input['address']:null;
        $user->password = Hash::make($input['password']);
        $user->is_active = 0;
        //random a code 4 digit
        $random_num = rand(1000, 9999);
        $user->confirm_code =  $random_num;
        $user->save();
        $emailController = new EmailController();
        $emailController->sendEmailConfirm($input['username'], $random_num, $input['email']);

        return response()->json([
            'status' => 'confirm',
            'message' => 'User created successfully',
        ]);
    }
    public function confirm(Request $request)
    {
        $input = $request->only('confirm_code','username');
        $rules = [
            'confirm_code' => 'required|integer',
            'username' => 'required|string|exists:users,username'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $temp_user = User::where('username', $input['username'])->first();
        if($input['confirm_code'] == $temp_user->confirm_code){
            $temp_user->is_active = 1;
            $temp_user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'confirm successfully'
            ]);
        }
    }
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        $rules = [
            'username' => 'required|string',
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
                    'message' => 'Your username or password is incorrect'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error'
            ]);
        }
        $temp_user = User::where('username', $credentials['username'])->first();
        $is_active = $temp_user->is_active;
        if ($is_active == 0) {
            $emailController = new EmailController();
            $emailController->sendEmailConfirm($temp_user->username, $temp_user->confirm_code, $temp_user->email);
            return response()->json([
                'status' => 'not confirm',
                'token' => 'This account have not been confirmed yet.'
            ]);
        }
        return response()->json([
            'status' => 'success',
            'token' => $token
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
    public function resetPassword(Request $request){
        $input = $request->only('username');
        $rules = [
            'username' => 'required|string|exists:users,username'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $current_user = User::where('username', $input['username'])->first();
        $new_password = $this->generateRandomString();
        $current_user->password = Hash::make($new_password);
        $current_user->save();
       
        //send mail reset pass
        $emailController = new EmailController();
        $emailController->sendMailResetPassword($current_user->email, $new_password);

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'A new password will be sent to your email.'
        ]);


    }
    public function generateRandomString() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function changePassword(Request $request){
        $user = Auth::user();
        $input = $request->only('password','new_password');
        $rules = [
            'password' => 'required|string',
            'new_password' => 'required|string|min:6'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $input_password = $input['password'];
        $new_password = $input['new_password'];
        //check current password
        if (!(Hash::check($input_password, $user->password))) {
            // The passwords matches
            return response()->json([
                'status' => 'error',
                'message' =>  'Your current password does not matches with the password you provided. Please try again.'
            ]);
        }
        if(strcmp($input_password, $new_password) == 0){
            //Current password and new password are same
            return response()->json([
                'status' => 'error',
                'message' =>  'New Password cannot be same as your current password. Please choose a different password.'
            ]);
        }
        $current_user = User::find($user->id);
        $current_user->password = Hash::make($new_password);
        $current_user->save();
        return response()->json([
            'status' => 'success',
            'message' =>  'Change password successfully.'
        ]);
    }

    
}
