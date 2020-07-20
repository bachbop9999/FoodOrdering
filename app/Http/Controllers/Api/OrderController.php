<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailController;
use App\Order;
use App\OrderDetail;
use Illuminate\Http\Request;
use App\Schedule;
use App\Voucher;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use function GuzzleHttp\Promise\all;

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
        $input = $request->all();
        $rules = [
            'total_price' => 'required',
            'status' => 'required|string',
            'payment_id' => 'required|integer|exists:payment,id',
            'date' => 'required|date',
            'time_from' => 'required',
            'time_to' => 'required',
            'cart' => 'required|array'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        //check time valid or not
        $date = $input['date'];
        $time_from = $input['time_from'];
        $time_to = $input['time_to'];
        $carbon_time_from = Carbon::parse($time_from);
        $carbon_time_to = Carbon::parse($time_to);

        if ($carbon_time_from->lt(Carbon::parse("09:00")) || $carbon_time_to->gt(Carbon::parse("21:00"))) {
            return response()->json([
                'status' => 'error',
                'message' =>  'Working time of restaurant is from 9:00 to 21:00'
            ]);
        }
        $time_gap = $carbon_time_to->diffInHours($carbon_time_from);
        if ($time_gap <= 0) {
            return response()->json([
                'status' => 'error',
                'message' =>  'Time gaps must be postive'
            ]);
        }
        if ($time_gap > 3) {
            return response()->json([
                'status' => 'error',
                'message' =>  'Time gaps can not exceed 3 hours'
            ]);
        }

        $number_of_table = DB::table('table_order')->count();


        //get schedule of choosen day
        $schedule_of_choosen_date = Schedule::where('date_order', $date)->get();
        $table_no = 0;
        for ($i = 1; $i <= $number_of_table; $i++) {
            $temp_flag = true;
            foreach ($schedule_of_choosen_date as $ele) {
                $timeFrom = Carbon::parse($ele->time_from);
                $timeTo = Carbon::parse($ele->time_to);
                if ($ele->table_id == $i && !($carbon_time_from->gte($timeTo) || $carbon_time_to->lte($timeFrom))) {
                    $temp_flag = false;
                    break;
                }
            }
            //if table is ok
            if ($temp_flag) {
                $table_no = $i;
                break;
            }
        }
        if($table_no ==0){
            return response()->json([
                'status' => 'error',
                'message' =>  'All of tables are ordered at this time range. Please choose other time range.'
            ]);
        }


        //save to order
        $order = new Order();
        $order->total_price = $input['total_price'];
        $order->status = $input['status'];
        // $order->user_id = $user->id;
        $order->user_id = $user->id;
        $order->payment_id = $input['payment_id'];
        $order->save();

        $array_cart = $input['cart'];
        foreach ($array_cart as $item) {
            //save to order detail
            $order_detail = new OrderDetail();
            $order_detail->quantity = $item['quantity'];
            $order_detail->product_id = $item['product_id'];
            $order_detail->order_id = $order->id;
            $order_detail->save();
        }

        $schedule = new Schedule();
        $schedule->order_id = $order->id;
        $schedule->table_id = $table_no;
        $schedule->date_order = $date;
        $schedule->time_from = $time_from;
        $schedule->time_to = $time_to;
        $schedule->save();

        //send mail
        $emailController = new EmailController();
        $emailController->sendEmailOrder($user->email, $user->fullname, $order->id, $date, $time_from, $time_to, $table_no);

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Order created successfully',
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
        $discount_result = Voucher::where('voucher_code', strtoupper($input['voucher_code']))->first();
        if ($discount_result) {
            //giam so lan su dung di 1
            $time_before_apply =  Voucher::where('id', $discount_result->id)->first()->time_use;
            if ($time_before_apply != 0) {
                $time_after_apply = $time_before_apply - 1;
                $discount_result->time_use = $time_after_apply;
                $discount_result->save();
            } else {
                return response()->json([
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => 'This code has expired'
                ]);
            }



            return response()->json([
                'status' => Response::HTTP_OK,
                'discount' => $discount_result->discount,
            ]);
        }
        return response()->json([
            'status' => Response::HTTP_BAD_REQUEST,
            'message' => 'Invalid Code'
        ]);
    }
}
