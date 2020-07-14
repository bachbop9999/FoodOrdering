<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Category;
class CategoryController extends Controller
{
    public function getListCategory()
    {
        $listCategory = Category::get();
        return response()->json($listCategory, Response::HTTP_OK);
    }
}
