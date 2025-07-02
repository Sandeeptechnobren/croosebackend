<?php
namespace App\Http\Controllers;


use App\Models\ClientCustomer;
use Illuminate\Http\Request;
use App\Models\Customer;

// use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth;


class CustomerController extends Controller
{
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'phone' => 'required|string|unique:customers,phone',
        'email' => 'nullable|email',
        'whatsapp_number' => 'nullable|string',
    ]);
 
    $customer = Customer::create($validated);

    return response()->json([
        'success' => true,
        'message' => 'Customer registered successfully',
        'data' => $customer,
    ]);
}

    public function getCustomer(){
        $user=Auth::user();
        $customer=ClientCustomer::where('client_id',$user->id)->get();

        return response()->json([
            'success' =>true,
            'data'=>$customer
        ]);

    } 

}
