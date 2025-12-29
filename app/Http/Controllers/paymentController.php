<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Appointment;
use App\Models\Customer;

class paymentController extends Controller
{
public function payment_details(Request $response)
{
    $client_id = Auth::user()->id;

    $transactions = Transaction::where('client_id', $client_id)
        ->whereNotNull('transaction_id')
        ->orderBy('created_at', 'desc')
        ->get(['type','reference_id','amount','payment_origin','payment_method','transaction_status','transaction_id','created_at'])
        ->map(function ($transaction) {
            return [
                'type'              => $transaction->type,
                'reference_id'      => $transaction->reference_id,
                'amount'            => $transaction->amount,
                'payment_origin'    => $transaction->payment_origin,
                'payment_method'    => $transaction->payment_method,
                'transaction_status'=> $transaction->transaction_status,
                'transaction_id'    => $transaction->transaction_id,
                'created_at'        => $transaction->created_at->toDateTimeString(),

            ];
        });

    return response()->json([
        'status'  => true,
        'data'    => $transactions,
        'message' => "transaction data"
    ]);
}

}
