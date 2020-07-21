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
    public function getPopularProduct(Request $request)
    {
        $input = $request->only('category_id');
        $rules = [
            'category_id' => 'required|integer|exists:categories,id'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $popularProduct = Product::where('category_id', $input['category_id'])->orderBy('rating', 'desc')->get();
        return response()->json($popularProduct, Response::HTTP_OK);
    }

    public function getNewProduct(Request $request)
    {

        $input = $request->only('category_id');
        $rules = [
            'category_id' => 'required|integer|exists:categories,id'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $newProduct = Product::where('category_id', $input['category_id'])->orderBy('created_at', 'desc')->get();
        return response()->json($newProduct, Response::HTTP_OK);
    }

    public function getProductSortByPrice(Request $request)
    {
        $input = $request->only('category_id');
        $rules = [
            'category_id' => 'required|integer|exists:categories,id'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $priceProduct = Product::where('category_id', $input['category_id'])->orderBy('price')->get();
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

    public function searchProduct(Request $request)
    {
        $input = $request->only('keyword');
        $rules = [
            'keyword' => 'nullable|string'
        ];

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        
        if ($request->exists('keyword')) {
            // $search_product = Product::where('name', 'like' ,'%'.$input['keyword'].'%')->get();
            $search_product = Product::whereRaw('LOWER(`name`) LIKE %? ',[trim(strtolower($input['keyword'])).'%'])->get();
        }else{
            $search_product = Product::get();
        }

        return response()->json($search_product, Response::HTTP_OK);
    }
}
