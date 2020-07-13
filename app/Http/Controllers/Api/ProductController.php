<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
class ProductController extends Controller
{
    public function getPopularProduct()
    {
        $temp = Product::table('products')->get();
        return response()->json($temp, Response::HTTP_OK);
    }
}
