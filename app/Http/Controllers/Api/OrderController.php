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
        if ($temp->lt($temp2)) {
            return response()->json('ok', Response::HTTP_OK);
        } else {
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
    public function test2(Request $request)
    {
        $input = $request->all();
        $temp = $input;
    }
    public function insertToOrder(Request $request)
    {
        $user = Auth::user();
        $input = $request->only('total_price', 'status', 'payment_id');
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


    public function applyVoucher(Request $request)
    {
        $input = $request->only('voucher_code');
        $rules = [
            'voucher_code' => 'required|string',
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $discount_result = DB::table('voucher')->where('voucher_code', strtoupper($input['voucher_code']))->first();
        if($discount_result){
            //giam so lan su dung di 1
            $time_before_apply =  DB::table('voucher')->where('id',$discount_result->id)->first()->time_use;
            if($time_before_apply != 0){
                $time_after_apply = $time_before_apply-1;
                DB::table('voucher')->where('id',$discount_result->id)->update(['time_use', $time_after_apply]);
            }else{
                return response()->json([
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message'=> 'This code has expired'
                ]);
            }
           


            return response()->json([
                'status' => Response::HTTP_OK,
                'discount' => $discount_result->discount,
            ]);
        }
        return response()->json([
            'status' => Response::HTTP_BAD_REQUEST,
            'message'=> 'Invalid Code'
        ]);
    
    }
}
