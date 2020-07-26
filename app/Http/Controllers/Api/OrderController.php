<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailController;
use App\Order;
use App\OrderDetail;
use App\User;
use App\Cart;
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
        $now = Carbon::now()->format('d-m-Y G:i');
        return $now;
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
                'message' =>  'Did you forget something? T.T'
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
        if ($table_no == 0) {
            return response()->json([
                'status' => 'error',
                'message' =>  'All of tables are ordered at this time range. Please choose other time range.'
            ]);
        }

        //process with voucher code
        $currenUser = User::find($user->id);
        $discount_result = null;
        if ($input['voucher_code'] == "") {
            //check if user used voucher
            $string_voucher_code = $user->array_voucher;

            if ($string_voucher_code) {
                $split = explode(",", $string_voucher_code);
                for ($i = 0; $i < sizeof($split) - 1; $i++) {
                    //  if($split[$i] == ""){
                    //      continue;
                    //  }
                    if ($split[$i] == $input['voucher_code']) {
                        return response()->json([
                            'status' => Response::HTTP_BAD_REQUEST,
                            'message' => 'You used this voucher code'
                        ]);
                    }
                }
            }

            $discount_result = Voucher::where('voucher_code', strtoupper($input['voucher_code']))->first();
            if ($discount_result) {
                //giam so lan su dung di 1
                $time_before_apply =  Voucher::where('id', $discount_result->id)->first()->time_use;
                if ($time_before_apply != 0) {
                    $time_after_apply = $time_before_apply - 1;
                    $discount_result->time_use = $time_after_apply;
                    $discount_result->save();
                    // them voucher vao bang user
                    if ($currenUser->array_voucher == null) {
                        $currenUser->array_voucher = strtoupper($input['voucher_code']) . ',';
                        $currenUser->save();
                    } else {
                        $temp = $currenUser->array_voucher . strtoupper($input['voucher_code']) . ',';
                        $currenUser->array_voucher = $temp;
                        $currenUser->save();
                    }
                } else {
                    return response()->json([
                        'status' => Response::HTTP_BAD_REQUEST,
                        'message' => 'This code has expired'
                    ]);
                }
            }else{
                return response()->json([
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => 'This code is invalid'
                ]);
            }
        }


        //save to order
        $order = new Order();
        $order->total_price = $input['total_price'];
        $order->status = $input['status'];
        // $order->user_id = $user->id;
        $order->user_id = $user->id;
        if ($discount_result != null) {
            $order->voucher_id = $discount_result->id;
        } else {
            $order->voucher_id = null;
        }

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

        //clear cart
        Cart::where('user_id', $user->id)->delete();

        $data_return = [
            'order_id' => $order->id,
            'total_price' => $order->total_price,
            'status' => $order->status,
            'date_order' => Carbon::parse($schedule->date_order)->format('d-m-Y'),
            'time_from' => Carbon::parse($schedule->time_from)->format('G:i'),
            'time_to' => Carbon::parse($schedule->time_to)->format('G:i'),
            'table_no' => $schedule->table_id

        ];
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Order created successfully',
            'data' => $data_return
        ]);
    }

    public function getListOrder()
    {
        $user = Auth::user();
        $listOrderByUserId = Order::where('user_id', $user->id)->get();
        $data_array = [];
        foreach ($listOrderByUserId as $item) {
            $temp_data = [
                'orderId' => $item->id,
                'created_date' => Carbon::parse($item->created_at)->format('d-m-Y'),
                'created_time' => Carbon::parse($item->created_at)->format('G:i'),
                'total_price' => $item->total_price
            ];
            array_push($data_array, $temp_data);
        }
        return response()->json([
            'status' => Response::HTTP_OK,
            'data' => $data_array,
        ]);
    }
    public function getDetailOrder(Request $request)
    {
        $input = $request->only('order_id');
        $rules = [
            'order_id' => 'required|integer|exists:orders,id',
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $order_id = $input['order_id'];
        $order = Order::find($order_id);
        $schedule = Schedule::where('order_id', $order_id)->first();
        $data_detail_order = [
            'order_id' => $order_id,
            'total_price' => $order->total_price,
            'status' => $order->status,
            'date_order' => Carbon::parse($schedule->date_order)->format('d-m-Y'),
            'time_from' => Carbon::parse($schedule->time_from)->format('G:i'),
            'time_to' => Carbon::parse($schedule->time_to)->format('G:i'),
            'table_no' => $schedule->table_id
        ];
        return response()->json([
            'status' => Response::HTTP_OK,
            'data' => $data_detail_order,
        ]);
    }
    public function getListVoucher()
    {
        $listVoucher = DB::table('voucher')->select('voucher_code', 'discount')->get();
        return response()->json([
            'status' => Response::HTTP_OK,
            'data' => $listVoucher,
        ]);
    }
    public function applyVoucher(Request $request)
    {
        $user = Auth::user();
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
        if (!$discount_result) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'This code is invalid'
            ]);
        }
        if ($discount_result->time_use == 0) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'This code has expired'
            ]);
        }
        //check if user used voucher
        $string_voucher_code = $user->array_voucher;

        if ($string_voucher_code) {
            $split = explode(",", $string_voucher_code);
            for ($i = 0; $i < sizeof($split); $i++) {
                if ($split[$i] == $input['voucher_code']) {
                    return response()->json([
                        'status' => Response::HTTP_BAD_REQUEST,
                        'message' => 'You used this voucher code'
                    ]);
                }
            }
        }


        return response()->json([
            'status' => Response::HTTP_OK,
            'discount' => $discount_result->discount,
        ]);
    }
}
