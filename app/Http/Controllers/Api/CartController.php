<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Cart;
use App\Product;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $user = Auth::user();
        $input = $request->only('product_id', 'amount');
        $rules = [
            'product_id' => 'required|integer|exists:products,id',
            'amount' => 'required|integer'
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        $amount = $input['amount'];
        $product_id = $input['product_id'];
        if($amount <= 0){
            return response()->json([
                'status' => 'error',
                'message' => 'Amount of product must be positive digit.'
            ]);
        }
        $cart = new Cart();
        $cart->product_id = $product_id;
        $cart->amount = $amount;

        //get product
        $current_product = Product::find($product_id);

        $cart->name = $current_product->name;
        $cart->imageUrl = $current_product->image_url;
        $cart->user_id = $user->id;
        $cart->save();

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Add to cart successfully',
        ]);
    }

    public function getListCart()
    {
        $user = Auth::user();
        $listCart = Cart::where('user_id', $user->id)->get();
        return response()->json([
            'status' => Response::HTTP_OK,
            'data' => $listCart,
        ]);
    }

    public function removeByCartId(Request $request){
        $input = $request->only('cart_id');
        $rules = [
            'cart_id' => 'required|integer|exists:cart,id',
        ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->getMessageBag()
            ]);
        }
        Cart::where('id',$input['cart_id'])->delete();
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Delete cartId successfully',
        ]);
    }

}
