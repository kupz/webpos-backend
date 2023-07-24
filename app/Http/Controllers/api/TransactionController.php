<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;   
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;

use App\Models\Transaction;
use App\Models\Product;

class TransactionController extends Controller
{
    //
    public function index(Request $request){
        $transactions = $request->user()->transactions;
        $transactions->map(function ($transaction)  {
            $transaction->products;
            $transaction->total();
        });
        return response()->json(['ok' => true, 'data' => $transactions], 200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'cart' => 'array|required|min:1',
            'cart.*' => 'required|array',
            'type' => 'required|min:1|max:3', // 1 = Invoice, 2 = Receive, 3 = Pull-out
            'cart.*.product' => [
                'required',
                Rule::exists('products', 'id')->where(fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ],
            'cart.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max: 1000000000'
            ],
        ]);
        if($validator->fails()){
            return response()->json(['ok' => false, 'errors' => $validator->errors(), 'message' => "Request didn't pass the validation!"], 400);
        }
        else{
            $validated = $validator->validated();
            $transaction = Transaction::create(['user_id' => $request->user()->id, 'type' => $validated['type']]);
            foreach ($validated['cart'] as $item){
                $product = Product::where('id', $item['product'])->where('user_id', $request->user()->id)->first();
                $transaction->products()->attach($product->id, ['price' => $product->price, 'quantity' => $validated['type'] == 1 ? $item['quantity'] * -1 : $item['quantity']]);
            }
            $transaction->products;
            $transaction->total();
            return response()->json(['ok' => true, 'data' => $transaction, 'message' => "Transaction has been created!"], 200);
        }



    }
}
