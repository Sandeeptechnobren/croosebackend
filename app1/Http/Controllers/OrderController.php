<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Client;
use Carbon\Carbon;
use App\Models\Space;
use App\Models\Customer;
use App\Models\ClientCustomer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class OrderController extends Controller
{


public function show()
{
    $client_id = Auth::user()->id;
    $orders = Order::with(['customer', 'product', 'space'])
        ->where('client_id', $client_id)
        ->orderBy('created_at', 'desc')
        ->paginate(10);
    $formattedOrders = $orders->through(function ($order) {
        $space = $order->space;
        $customer = $order->customer;
        $product = $order->product;
        return [
            'id' => $order->id,
            'space_name' => $space->name ?? null,
            'customer_name' => $customer->name ?? null,
            'customer_number' => $customer->whatsapp_number ?? null,
            'product_name' => $product->name ?? null,
            'order_amount' => (float) $order->order_amount,
            'currency'  =>$space->currency,
            'payment_status' => $order->payment_status,
            'order_date' => Carbon::parse($order->created_at)->format('d-M-Y'),
            'order_status' => $order->status,
        ];
    });
    return response()->json([
        'success' => true,
        'data' => $formattedOrders->items(),
        'meta' => [
            'current_page' => $orders->currentPage(),
            'last_page'    => $orders->lastPage(),
            'per_page'     => $orders->perPage(),
            'total'        => $orders->total(),
        ]
    ]);
}

public function updateOrderStatus(Request $request)
    {
    try {
        $client = auth()->user(); 
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,delivered,cancelled,returned,refunded',
            'id'     => 'required|integer'
        ]);
        $order =Order::where('id', $validated['id'])
                    ->where('client_id', $client->id)
                    ->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not belong to you.'
            ], 404);
        }
        $order->status = $validated['status'];
        $order->save();
        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => [
                'id'     => $order->id,
                'status' => $order->status,
            ]
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
    }

public function order_statistics()
{
    try {
        $client_id = Auth::user()->id;
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $total_orders = Order::where('client_id', $client_id)->count();
        $total_orders_today = Order::where('client_id', $client_id)
            ->whereDate('created_at', $today)
            ->count();
        $total_orders_yesterday = Order::where('client_id', $client_id)
            ->whereDate('created_at', $yesterday)
            ->count();
        $total_orders_growth = $this->calculateGrowth($total_orders_yesterday, $total_orders_today);
        $total_new_orders = Order::where('client_id', $client_id)
            ->whereIn('status', ['processing', 'shipped', 'delivered'])
            ->whereDate('created_at', $today)
            ->count();
        $new_orders_yesterday = Order::where('client_id', $client_id)
            ->whereIn('status', ['processing', 'shipped', 'delivered'])
            ->whereDate('created_at', $yesterday)
            ->count();
        $total_new_orders_growth = $this->calculateGrowth($new_orders_yesterday, $total_new_orders);
        $cancelled_orders = Order::where('client_id', $client_id)
            ->where('status', 'cancelled')
            ->count();
        $cancelled_orders_today = Order::where('client_id', $client_id)
            ->where('status', 'cancelled')
            ->whereDate('created_at', $today)
            ->count();
        $cancelled_orders_yesterday = Order::where('client_id', $client_id)
            ->where('status', 'cancelled')
            ->whereDate('created_at', $yesterday)
            ->count();
        $cancelled_orders_growth = $this->calculateGrowth($cancelled_orders_yesterday, $cancelled_orders_today);
        return response()->json([
            'status' => true,
            'cancelled_orders' => $cancelled_orders,
            'total_orders' => $total_orders,
            'total_new_orders' => $total_new_orders,
            'total_orders_growth' => $total_orders_growth,
            'total_new_orders_growth' => $total_new_orders_growth,
            'cancelled_orders_growth' => $cancelled_orders_growth,
            'message' => 'Order statistics fetched successfully.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}
private function calculateGrowth($previous, $current)
{
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 2);
}

public function createmanualorder(Request $request)
{
    DB::beginTransaction();
    try {
        $client_id = Auth::user()->id;
        $validated = $request->validate([
            'space_id'        => 'required|integer|exists:spaces,id',
            'product_id'      => 'required|integer|exists:products,id',
            'order_quantity'  => 'nullable|numeric|min:1',
            'customer_name'   => 'required|string',
            'whatsapp_number' => 'required|string|max:20',
            'email'           => 'required|email',
            'address'         => 'required|string',
        ]);
        $space = Space::where('id', $validated['space_id'])
            ->where('client_id', $client_id)
            ->first();
        if (!$space) {
            return response()->json([
                'status'  => 403,
                'message' => 'Invalid space. This space does not belong to you.'
            ], 403);
        }
        $product = Product::where('id', $validated['product_id'])
            ->where('space_id', $space->id)
            ->first();
        if (!$product) {
            return response()->json([
                'status'  => 403,
                'message' => 'Invalid product. This product does not belong to the specified space.'
            ], 403);
        }
        $whatsapp_number = $validated['whatsapp_number'];
        $customer = Customer::where('whatsapp_number', $whatsapp_number)->first();
        if (!$customer) {
            $customer = Customer::create([
                'name'            => $validated['customer_name'],
                'whatsapp_number' => $whatsapp_number,
                'email'           => $validated['email'],
                'address'         => $validated['address'],
            ]);
        }
        $order_quantity = (float) ($validated['order_quantity'] ?? 1);
        $payment_amount = (float) $product->price * $order_quantity;
        $order = Order::create([
            'client_id'      => $client_id,
            'space_id'       => $space->id,
            'product_id'     => $product->id,
            'order_quantity' => $order_quantity,
            'customer_id'    => $customer->id,
            'order_date'     => now(),
            'address'        => $validated['address'],
            'status'         => 'pending',
            'created_by'     => $client_id,
            'payment_method' => 'Manual',
            'payment_type'   => 'Cash',
            'payment_status' => 'Success-Self',
            'payment_origin' => 'Self',
            'order_amount'   => $payment_amount,
        ]);
        if (!ClientCustomer::where([
            'client_id'   => $client_id,
            'space_id'    => $space->id,
            'customer_id' => $customer->id,
        ])->exists()) {
            ClientCustomer::create([
                'client_id'   => $client_id,
                'space_id'    => $space->id,
                'customer_id' => $customer->id,
            ]);
        }
        if ($product->stock < $order_quantity) {
            return response()->json([
                'status'  => 400,
                'message' => 'Not enough stock available.'
            ], 400);
        }
        $product->decrement('stock', $order_quantity);
        DB::commit();
        return response()->json([
            'status'   => 200,
            'message'  => 'Order created successfully',
            'customer' => $customer,
            'order'    => $order,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage()
        ], 500);
    }
}




}