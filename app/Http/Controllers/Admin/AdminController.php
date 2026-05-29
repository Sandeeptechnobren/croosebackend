<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\subscription;
use App\Models\FeedBack;
use App\Models\Customer;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'status' => true,
            'data' => [
                'total_users' => Client::count(),
                'active_users' => Client::where('status', 1)->count(),
                'toal_customer'=> Customer::count(),
                'total_orders' => Order::count(),
                'total_products' => Product::count(),
                'total_services' => Service::count(),
                'total_subscriptions' => subscription::count(),
            ]
        ]);
    }

    // ✅ All Users
    public function users()
    {
        $users = Client::latest()->get();
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    // ✅ Toggle User Active / Inactive
    public function toggleUserStatus($id)
    {
        $user = Client::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User status updated',
            'is_active' => $user->is_active
        ]);
    }

    // ✅ Orders list
    public function orders()
    {
        $orders = Order::latest()->get();

        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }

    // ✅ Update Order Status
    public function updateOrderStatus(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found'
            ]);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => true,
            'message' => 'Order status updated'
        ]);
    }

    // ✅ Products
    public function products()
    {
        return response()->json([
            'status' => true,
            'data' => Product::latest()->get()
        ]);
    }

    // ✅ Delete Product
    public function deleteProduct($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted'
        ]);
    }

    // ✅ Services
    public function services()
    {
        return response()->json([
            'status' => true,
            'data' => Service::latest()->get()
        ]);
    }

    // ✅ Delete Service
    public function deleteService($id)
    {
        Service::findOrFail($id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Service deleted'
        ]);
    }

    // ✅ Subscriptions
    public function subscriptions()
    {
        return response()->json([
            'status' => true,
            'data' => Subscription::latest()->get()
        ]);
    }

    // ✅ Archive Subscription
    public function archiveSubscription($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->is_archived = 1;
        $sub->save();

        return response()->json([
            'status' => true,
            'message' => 'Subscription archived'
        ]);
    }
}