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
    public function payment_details(Request $request)
    {
    $client_id = Auth::user()->id;
    $query = Transaction::where('client_id', $client_id);
    if ($request->filled('status')) {
        $status = trim(strtolower($request->status));

        $query->whereRaw(
            'TRIM(LOWER(transaction_status)) = ?',
            [$status]
        );
    }
    $transactions = $query->orderBy('id', 'desc')->get();
    $data = $transactions->map(function ($transaction) {
        $referenceData = null;
        switch (strtolower($transaction->type)) {
            case 'order':
                $referenceData = Order::find($transaction->reference_id);
                break;
            case 'appointment':
                $referenceData = Appointment::find($transaction->reference_id);
                break;
            case 'customer':
                $referenceData = Customer::find($transaction->reference_id);
                break;
        }
        return [
            'type'               => $transaction->type,
            'reference_id'       => $transaction->reference_id,
            'reference_data'     => $referenceData,
            'amount'             => $transaction->amount,
            'payment_origin'     => $transaction->payment_origin,
            'payment_method'     => $transaction->payment_method,
            'transaction_status'=> $transaction->transaction_status,
            'transaction_id'     => $transaction->transaction_id,
            'created_at'         => optional($transaction->created_at)->toDateTimeString(),
        ];
    });
    return response()->json([
        'status'  => true,
        'data'    => $data,
        'message' => 'transaction data'
    ]);
    }

}
