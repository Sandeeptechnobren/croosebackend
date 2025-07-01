<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServicesController extends Controller
{
    // ✅ Get all services for the authenticated client
    public function index(Request $request)
    {
        $clientId = $request->user()->id; // assuming authenticated client
        $services = Service::where('client_id', $clientId)->get();

        return response()->json($services);
    }

    // ✅ Store a new service
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer',
            'price' => 'required|numeric',
            'unit' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'type' => 'required|in:in_store,at_home,virtual',
            'buffer_minutes' => 'nullable|integer',
            'available_days' => 'nullable|array',
            'ai_tags' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $service = Service::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
            'client_id' => $request->user()->id, // client-authenticated route
        ]);

        return response()->json(['success' => true, 'service' => $service], 201);
    }

    // ✅ Show single service
    public function show($id)
    {
        $service =  Service::findOrFail($id);
        return response()->json($service);
    }

    // ✅ Update service
    public function update(Request $request, $id)
    {
        $service =  Service::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'sometimes|required|integer',
            'price' => 'sometimes|required|numeric',
            'unit' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'type' => 'sometimes|required|in:in_store,at_home,virtual',
            'buffer_minutes' => 'nullable|integer',
            'available_days' => 'nullable|array',
            'ai_tags' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $service->update($validated);

        return response()->json(['success' => true, 'service' => $service]);
    }

    // ✅ Delete service
    public function destroy($id)
    {
        $service =  Service::findOrFail($id);
        $service->delete();

        return response()->json(['success' => true, 'message' => 'Service deleted']);
    }
}
