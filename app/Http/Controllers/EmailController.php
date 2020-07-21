<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Mail;
use Illuminate\Http\Response;
class EmailController extends Controller
{
    public function sendEmailConfirm($name, $confirm_code, $email)
    {
        Mail::send('mailfb', array('name'=>$name, 'confirm_code'=>$confirm_code), function($message) use ($email){
	        $message->to($email, 'HEROIN RESTAURANT')->subject('Confirm Email');
	    });
        // Session::flash('flash_message', 'Send message successfully!');
        return response()->json('Send message successfully!', Response::HTTP_OK);
    }

    public function sendEmailOrder($email, $name, $oderId, $date, $time_from, $time_to, $table_no)
    {
        Mail::send('mailOrder', array('name'=>$name, 'orderId'=>$oderId, 'date'=>$date, 'timeFrom'=>$time_from, 'timeTo'=>$time_to, 'table_no'=>$table_no), function($message) use ($email){
	        $message->to($email, 'HEROIN RESTAURANT')->subject('You ordered successfully');
	    });
        // Session::flash('flash_message', 'Send message successfully!');
        return response()->json('Send message successfully!', Response::HTTP_OK);
    }

    public function sendMailResetPassword($email, $new_password){
        Mail::send('mailResetPass', array('new_password'=>$new_password), function($message) use ($email){
	        $message->to($email, 'HEROIN RESTAURANT')->subject('Reset password');
	    });
    }

}
