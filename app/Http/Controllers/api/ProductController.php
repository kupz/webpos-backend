<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;

class ProductController extends Controller
{
    

    // MIDDLEWARE: auth:api
    // ROUTE: GET: api/products
    public function index(Request $request){
        return response()->json(['ok' => true, 'data' => Product::all()], 200);
    }

    // MIDDLEWARE: auth:api
    // ROUTE: POST api/products
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'sku' => [
                'required', 'max:200', 'string',
                Rule::unique('products')->where(fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ],
            'name' => 'required|max:200|string',
            'description' => 'required|max:200|string',
            'barcode' => 'required|max:200|string',
            'price' => 'required|max:200|string',
        ]);
        if($validator->fails()){
            return response()->json(['ok' => false, 'message' => "Request didn't pass the validation.", 'errors' => $validator->errors()], 400);
        }
        else{
            $validated = $validator->validated();
            $product = $request->user()->products()->create($validated);
            $request->user()->logs()->create([
                'table_name' => 'products',
                'object_id' => $product->id,
                'label' => 'product-store',
                'description' => "Product ($product->id) has been created!",
                'properties' => json_encode(array_merge($validated, ['user-agent' => $request->userAgent(), 'token' => $request->user()->token()->id])),
                'ip' => $request->ip()
            ]);
            return response()->json(["ok" => true, 'message' => "Product has been created!", 'data' => $product], 200);
        }
    }

    // MIDDLEWARE: auth:api
    // ROUTE: PATCH: api/products/{id}
    public function update(Request $request, Product $product){
        $validator = Validator::make($request->all(), [
            'sku' => [
                "required", "max:200", "string",
                Rule::unique('products')->where(fn (Builder $query) => $query->where('user_id', $request->user()->id))->ignore($product->id)
            ],
            'name' => 'required|max:200|string',
            'description' => 'required|max:200|string',
            'barcode' => 'required|max:200|string',
            'price' => 'required|max:200|string',
        ]);
        if($validator->fails()){
            return response()->json(['ok' => false, 'message' => "Request didn't pass the validation.", 'errors' => $validator->errors()], 400);
        }
        else{
            $validated = $validator->validated();
            $changes = [];
            foreach($validated as $key => $value){
                if($value != $product[$key]){
                    $changes[$key] = ["old" => $product[$key], "new" => $value];
                }
            }
            $product->update($validated);
            $request->user()->logs()->create([
                'table_name' => 'products',
                'object_id' => $product->id,
                'label' => 'product-update',
                'description' => "Product ($product->id) has been updated!",
                'properties' => json_encode(array_merge(["changes" => $changes], ['user-agent' => $request->userAgent(), 'token' => $request->user()->token()->id])),
                'ip' => $request->ip()
            ]);
            return response()->json(['ok' => true, 'message' => 'Product has been updated!', 'data' => $product], 200);
        }
    }

}
