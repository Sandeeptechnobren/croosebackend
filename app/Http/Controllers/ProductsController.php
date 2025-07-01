<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductsController extends Controller
{
    // 🔍 Get all products for the logged-in client
    public function index(Request $request)
    {
        $clientId = $request->user()->id;
        $products = Product::where('client_id', $clientId)->get();

        return response()->json($products);
    }

    // ➕ Create new product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'unit' => 'nullable|string|max:50',
            'type' => 'required|in:physical,digital,service_addon',
            'stock' => 'nullable|integer',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $product = Product::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
            'client_id' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'product' => $product], 201);
    }

    // 📄 Show single product
    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    // ✏️ Update product
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric',
            'unit' => 'nullable|string|max:50',
            'type' => 'sometimes|required|in:physical,digital,service_addon',
            'stock' => 'nullable|integer',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return response()->json(['success' => true, 'product' => $product]);
    }

    // ❌ Delete product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Product deleted successfully']);
    }
}
