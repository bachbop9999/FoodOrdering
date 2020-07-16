<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class ProductController extends Controller
{
    public function getPopularProduct()
    {
        $popularProduct = Product::orderBy('rating', 'desc')->get();
        return response()->json($popularProduct, Response::HTTP_OK);
    }

    public function getNewProduct()
    {
        $newProduct = Product::orderBy('created_at', 'desc')->get();
        return response()->json($newProduct, Response::HTTP_OK);
    }

    public function getProductSortByPrice()
    {
        $priceProduct = Product::orderBy('price')->get();
        return response()->json($priceProduct, Response::HTTP_OK);
    }

    public function getDetailProduct(Request $request)
    {
        $input = $request->only('id');
        $rules = [
            'id' => 'required|integer|exists:products'
        ];
        
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $detailProduct = Product::find($input['id']);
        return response()->json($detailProduct, Response::HTTP_OK);

    }
}
