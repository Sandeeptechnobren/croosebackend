<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Customer;

use app\Models\clients;

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

}
