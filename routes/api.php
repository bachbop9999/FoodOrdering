<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('users', 'Api\UserController@getUsers');
Route::post('insert', 'Api\UserController@insertUser');

Route::post('auth/register', 'Api\UserController@register');
Route::post('auth/login', 'Api\UserController@login');
Route::post('auth/confirm', 'Api\UserController@confirm');
Route::group(['middleware' => 'jwt.auth'], function () {
    Route::get('users', 'Api\UserController@getUsers');
    Route::get('auth', 'Api\UserController@user');
    Route::post('logout', 'Api\UserController@logout');
    //apply voucher code
    Route::post('apply-voucher', 'Api\OrderController@applyVoucher');

    //insert to order
    Route::post('insert-order', 'Api\OrderController@insertToOrder');
    
    //add to cart
    Route::post('add-to-cart', 'Api\CartController@addToCart');
    //get list cart
    Route::post('list-cart', 'Api\CartController@getListCart');
    //remove by cart id
    Route::post('remove-cart-item', 'Api\CartController@removeByCartId');

    //get list payment
    Route::post('list-payment', 'Api\PaymentController@getListPayment');
});



//test
Route::get('test', 'Api\OrderController@test');
Route::post('test2', 'Api\OrderController@test2');
//outside jwt
Route::post('products-popular', 'Api\ProductController@getPopularProduct');
Route::post('products-newest', 'Api\ProductController@getNewProduct');
Route::post('products-price', 'Api\ProductController@getProductSortByPrice');
Route::post('detail-product', 'Api\ProductController@getDetailProduct');
Route::post('categories', 'Api\CategoryController@getListCategory');

Route::middleware('jwt.refresh')->get('/token/refresh', 'Api\UserController@refresh');

//send mail
Route::post('send-mail', 'EmailController@sendEmail');