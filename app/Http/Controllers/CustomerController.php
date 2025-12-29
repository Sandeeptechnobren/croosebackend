<?php
namespace App\Http\Controllers;
use App\Models\ClientCustomer;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class CustomerController extends Controller
{
public function store(Request $request)
    {
    $validated = $request->validate([
        'name' => 'required|string',
        'phone' => 'required|string|unique:customers,phone',
        'email' => 'nullable|email',
        'whatsapp_number' => 'required|string',
    ]);
    $customer = Customer::create($validated);
    return response()->json([
        'success' => true,
        'message' => 'Customer registered successfully',
        'data' => $customer,
    ]);
    }

public function getCustomer()
    {
    $user = Auth::user();
    $customerIds = ClientCustomer::where('client_id', $user->id)
        ->pluck('customer_id');
    $customers = Customer::whereIn('id', $customerIds)->get();
    return response()->json([
        'success' => true,
        'data' => $customers
    ]);
    }



public function customerStatistics()
    {
    $client_id = Auth::user()->id;
    $total_customers = ClientCustomer::where('client_id', $client_id)->distinct()->count();
    $new_customers = ClientCustomer::where('client_id', $client_id)
        ->where('created_at', '>=', Carbon::now()->subDays(10))
        ->count();
    $returning_customer = DB::table(DB::raw("(
            SELECT customer_id FROM appointments WHERE client_id = $client_id
            UNION ALL
            SELECT customer_id FROM orders WHERE client_id = $client_id
        ) as combined"))
        ->select('customer_id', DB::raw('COUNT(*) as total_visits'))
        ->groupBy('customer_id')
        ->having('total_visits', '>', 1)
        ->count();
    $return_customer_rate = $total_customers > 0
        ? round(($returning_customer / $total_customers) * 100, 2)
        : 0;
    $inactive_customers = collect([
        DB::table('appointments')
            ->where('client_id', $client_id)
            ->where('created_at', '<=', now()->subDays(60))
            ->pluck('customer_id'),
        DB::table('orders')
            ->where('client_id', $client_id)
            ->where('created_at', '<=', now()->subDays(60))
            ->pluck('customer_id')
    ])->flatten()->unique()->count();
    $highest_order_value = Order::where('client_id', $client_id)->max('order_amount');
    $total_order_value = Order::where('client_id', $client_id)->sum('order_amount');
    $total_orders = Order::where('client_id', $client_id)->count();
    $average_customer_value = $total_orders > 0
        ? round($total_order_value / $total_orders, 2)
        : 0;
    return response()->json([
        'status' => true,
        'message' => 'Customer Statistics',
        'new_customers' => $new_customers,
        'returning_customer' => $returning_customer,
        'return_customer_rate' => $return_customer_rate,
        'inactive_customers' => $inactive_customers,
        'highest_order_value' => $highest_order_value ?? 0,
        'average_customer_value' => $average_customer_value,
    ]);
    }

public function getCustomerByPhone(Request $request){
        $validated = $request->validate([
            'whatsapp_number' => 'required|string|max:20',
        ]);
        $whatsapp_number = $validated['whatsapp_number'];
        $_isCustomer = Customer::where('whatsapp_number', $whatsapp_number)->first();
        if(!$_isCustomer){
            return response()->json([
                'status'=>200,
                'message'=>'Customer does not Exists . Add the details manually...'
            ]);
        }
        return response()->json([
            'status'=>200,
            'message'=>'Customer Data Fetched Successfully',
            'customer_name'=>$_isCustomer->name,
            'customer_address' => $_isCustomer->address . ' ' . $_isCustomer->country . ' ' . $_isCustomer->zipcode,
            'customer_email'=>$_isCustomer->email,

        ]);

}




}
