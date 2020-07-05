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

Route::get('users', 'Api\ProductController@getUsers');
Route::post('insert', 'Api\ProductController@insertUser');

Route::post('auth/register', 'Api\ProductController@register');
Route::post('auth/login', 'Api\ProductController@login');

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::get('auth', 'Api\ProductController@user');
    Route::post('logout', 'Api\ProductController@logout');
});

Route::middleware('jwt.refresh')->get('/token/refresh', 'Api\ProductController@refresh');
