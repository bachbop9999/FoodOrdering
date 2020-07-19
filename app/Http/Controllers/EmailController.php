<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Mail;
use Illuminate\Http\Response;
class EmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        $input = $request->all();
        Mail::send('mailfb', array('name'=>$input["name"]), function($message){
	        $message->to('bachhvhe130603@fpt.edu.vn', 'ITALIAN RESTAURANT')->subject('Confirm Email');
	    });
        // Session::flash('flash_message', 'Send message successfully!');
        return response()->json('Send message successfully!', Response::HTTP_OK);
    }

    public static function sendEmailOrder($email, $name, $oderId, $date, $time_from, $time_to)
    {
        Mail::send('mailOrder', array('name'=>$name, 'orderId'=>$oderId, 'date'=>$date, 'timeFrom'=>$time_from, 'timeTo'=>$time_to), function($message) use ($email){
	        $message->to($email, 'ITALIAN RESTAURANT')->subject('You ordered successfully');
	    });
        // Session::flash('flash_message', 'Send message successfully!');
        return response()->json('Send message successfully!', Response::HTTP_OK);
    }

}
