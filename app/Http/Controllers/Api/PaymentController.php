<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PaymentController extends Controller
{
    public function getListPayment()
    {
        $listPayment = DB::table('payment')->get();
        return response()->json([
            'status' => 'success',
            'data' =>  $listPayment
        ]);
    }
}
