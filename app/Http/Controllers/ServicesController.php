<?php
namespace App\Http\Controllers;
use App\Models\Service;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
class ServicesController extends Controller
{
public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'space_id'          => 'required|integer',
            'description'       => 'nullable|string',
            'duration_minutes'  => 'required|integer',
            'price'             => 'required|numeric',
            'unit'              => 'nullable|string|max:50',
            'category'          => 'nullable|string|max:100',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'type'              => 'nullable|in:in_store,at_home,virtual',
            'buffer_minutes'    => 'nullable|integer',
            'available_days'    => 'nullable|array',
            'ai_tags'           => 'nullable|array',
            'is_active'         => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
        ]);
        $client_id = $request->user()->id;
        $space = Space::where('id', $validated['space_id'])
                        ->where('client_id', $client_id)
                        ->first();
        if (!$space) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected space does not belong to your account.'
                ], 403);
            }
        $currency=$space->currency;
    DB::beginTransaction();
    try {
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            if ($image->isValid()) {
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('services', $imageName, 'public');
            }
        }
        $service = Service::create([
            ...$validated,   
            'client_id' => $client_id,
            'image' => $imagePath,
            'currency'=>$currency,
        ]);
        DB::commit(); 
        return response()->json([
            'success' => true,
            'message' => 'Service Created Successfully!',
            'service' => $service,
            'image_url' => $imagePath ? asset('storage/' . $imagePath) : null,
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack(); 
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        return response()->json([
            'success' => false,
            'message' => 'Service Creation failed!',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function update(Request $request, $id)
    {
    DB::beginTransaction();
    try {
        $client = Auth::user();
        if (!$client) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $service = Service::where('id', $id)->where('client_id', $client->id)->first();
        if (!$service) {
            return response()->json(['message' => 'Service not found or unauthorized'], 403);
        }
        $validated = $request->validate([
            'name'              => 'sometimes|required|string|max:255',
            'space_id'          => 'sometimes|required|integer',
            'description'       => 'nullable|string',
            'duration_minutes'  => 'sometimes|required|integer',
            'price'             => 'sometimes|required|numeric',
            'unit'              => 'nullable|string|max:50',
            'category'          => 'nullable|string|max:100',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type'              => 'sometimes|required|in:in_store,at_home,virtual',
            'buffer_minutes'    => 'nullable|integer',
            'available_days'    => 'nullable|array',
            'ai_tags'           => 'nullable|array',
            'is_active'         => 'boolean',
            'is_featured'       => 'boolean',
        ]);
        if (isset($validated['space_id'])) {
            $space = Space::where('id', $validated['space_id'])
                        ->where('client_id', $client->id)
                        ->first();
            if (!$space) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'The selected space does not belong to your account.'
                ], 403);
            }
        }
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            if ($image->isValid()) {
                if ($service->image && \Storage::disk('public')->exists($service->image)) {
                    \Storage::disk('public')->delete($service->image);
                }
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('services', $imageName, 'public');
                $validated['image'] = $imagePath;
            }
        }
        $service->update($validated);
        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully!',
            'service' => $service,
            'image_url' => $service->image ? asset('storage/' . $service->image) : null,
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Service update failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong!',
            'error' => $e->getMessage()
        ], 500);
    }
    }    
public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();
        return response()->json(['success' => true, 'message' => 'Service deleted']);
    }
public function get_services(Request $request)
    {
        $client = Auth::user();
        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $search   = $request->input('search');
        $space_id = $request->input('space_id');
        $status   = $request->input('status');
        $perPage  = $request->input('per_page', 10);
        $query = Service::where('client_id', $client->id)->with('space:id,name')
            ->orderBy('name', 'asc');;
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                ->orWhere('category', 'LIKE', "%$search%");
            });
        }
        if ($space_id) {
            $query->where('space_id', $space_id);
        }
        if (!is_null($status)) {
            $query->where('status', $status);
        }
        $services = $query->paginate($perPage);
        $formatted = $services->through(function ($service) {
            return [
                'service_id'      => $service->id,
                'space_id'        => $service->space->id ?? null,
                'space_name'      => $service->space->name ?? null,
                'service_name'    => $service->name,
                'service_category'=> $service->category,
                'service_duration'=> $service->duration_minutes,
                'service_price'   => $service->price,
                'available_days'  => $service->available_days,
                'status'          => $service->status,
                'date_added'      => $service->created_at,
            ];
        });
        return response()->json([
            'status'  => true,
            'message' => 'Services Fetched Successfully',
            'data'    => $formatted->items(),
            'meta'    => [
                'current_page' => $services->currentPage(),
                'last_page'    => $services->lastPage(),
                'per_page'     => $services->perPage(),
                'total'        => $services->total(),
            ]
        ]);
    }
public function showById(Request $request)
    {
        $validated = $request->validate([
                'id' => 'required|integer|exists:services,id',
        ]);
        $service = Service::findOrFail($validated['id']);
        return response()->json(['service' => $service]);
    }
public function addbulkservices(Request $request)
    {
        $request->validate([
            'space_id' => 'required|exists:spaces,id',
            'file'     => 'required|file|mimes:xlsx,csv',
        ]);
        $client = Auth::user();
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $space = Space::where('id', $request->space_id)
                    ->where('client_id', $client->id)
                    ->first();
        if (!$space) {
            return response()->json(['success' => false, 'message' => 'Space not found or unauthorized'], 403);
        }
        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $header = array_map('strtolower', array_map('trim', $sheetData[1]));
            unset($sheetData[1]);
            foreach ($sheetData as $row) {
                $data = array_combine($header, array_values($row));
                if (!isset($data['name'])) {
                    continue;
                }
                $service = Service::create([
                    'client_id'        => $client->id,
                    'space_id'         => $space->id,
                    'name'             => $data['name'],
                    'description'      => $data['description'] ?? null,
                    'duration_minutes' => $this->sanitizeInteger($data['duration_minutes'] ?? 0),
                    'buffer_minutes'   => $this->sanitizeInteger($data['buffer_minutes'] ?? 0),
                    'price'            => $this->sanitizePrice($data['price'] ?? 0),
                    'unit'             => $data['unit'] ?? null,
                    'category'         => $data['category'] ?? null,
                    'type'             => $data['type'] ?? null,
                    'available_days'   => isset($data['available_days']) ? explode(',', $data['available_days']) : [],
                ]);
            }
            return response()->json(['success' => true, 'message' => 'Bulk upload completed successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during upload.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
private function sanitizePrice($value)
    {
        $clean = preg_replace('/[^\d.]/', '', $value);
        $clean = preg_replace('/\.(?=.*\.)/', '', $clean);
        return is_numeric($clean) ? floatval($clean) : 0;
    }

private function sanitizeInteger($value)
    {
        $clean = preg_replace('/[^\d]/', '', $value);
        return is_numeric($clean) ? intval($clean) : 0;
    }
public function getServicesBySpace(Request $request)
{
    $validated = $request->validate([
        'space_id' => 'required|exists:spaces,id',
    ]);
    $currency = Space::where('id', $validated['space_id'])->value('currency');
    $services = Service::where('space_id', $validated['space_id'])
        ->get(['id','name', 'price','currency']);
    $formatted = $services->map(function ($service) use ($currency) {
        return [
            'id'    => $service->id,
            'name'  => $service->name,
            'stock' => $service->stock,
            'label' => $service->name . ' (' . $service->price . ' ' . $currency . ')',
        ];
    });
    return response()->json([
        'status'  => 200,
        'message' => 'Services List according to the space',
        'data'    => $formatted
    ]);
}
}
