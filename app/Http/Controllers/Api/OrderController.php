<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Order;
use Illuminate\Http\Request;
use App\Schedule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class OrderController extends Controller
{
    public function test(Request $request)
    {
        $input = $request->only('category_id');
        $firstSchedule = Schedule::first();
        $temp = Carbon::parse('21:00:00');
        $temp2 = Carbon::parse('20:00:00');
        if($temp->lt($temp2)){
            return response()->json('ok', Response::HTTP_OK);
        }else{
            return response()->json('error', Response::HTTP_OK);
        }
        // $rules = [
        //     'category_id' => 'required|integer|exists:categories,id'
        // ];
        // $validator = Validator::make($input, $rules);
        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' =>  $validator->getMessageBag()
        //     ]);
        // }
       ;
        // return response()->json('ok', Response::HTTP_OK);
    }

    public function insertToOrder(Request $request)
    {
        $user = Auth::user();
        $input = $request->only('total_price','status','payment_id');
        $rules = [
            'total_price' => 'required',
            'status' => 'required|string',
            'payment_id' => 'required|integer|exists:payment,id',
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $order = new Order();
        $order->total_price = $input['total_price'];
        $order->status = $input['status'];
        $order->user_id = $user->id;
        $order->payment_id = $input['payment_id'];
        $order->save();
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Order created successfully',
            'data' => $order
        ]);

    }
}
