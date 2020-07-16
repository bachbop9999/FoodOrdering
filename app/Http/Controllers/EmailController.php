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
}
