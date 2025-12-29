<?php

namespace App\Http\Controllers;
use App\Models\Space;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductsController extends Controller
{
public function get_products(Request $request)
    {
        $client = Auth::user();
        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $query = Product::where('client_id', $client->id)
            ->with('space:id,name,currency')
            ->orderBy('name', 'asc'); 
            
        if ($request->has('search') && trim($request->search) !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%");
            });
        }
        if ($request->has('status')) {
            $query->where('is_active', $request->status);
        }
        if ($request->has('space_id')) {
            $query->where('space_id', $request->space_id);
        }
        $perPage = $request->get('per_page', 10);
        $products = $query->paginate($perPage);
        $transformed = $products->through(function ($product) {
        if (!is_null($product->image)) {
            $imagePath = 'https://api.joincroose.com/croose/storage/app/public/' . $product->image;
        } else {
            $imagePath = null;
        }
            return [
                'status'         => true,
                'message'        => 'Data Fetched Successfully',
                'space_id'       => $product->space->id ?? null,
                'product_id'     => $product->id,
                'space_name'     => $product->space->name ?? null,
                'product_image'  => $imagePath,
                'product_name'   => $product->name,
                'product_price'  => $product->price.' '.optional(optional($product)->space)->currency,
                'product_stock'  => $product->stock,
                'product_status' => $product->is_active,
                'date_added'     => $product->created_at,
            ];
        });
        return response()->json([
            'status'  => true,
            'message' => 'Products fetched successfully',
            'data'    => $transformed->items(),
            'meta'    => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ]
        ]);
    }
public function store(Request $request)
    {
    DB::beginTransaction();
    try {
            $validator = Validator::make($request->all(), [
                'name'         => 'required|string|max:255',
                'space_id'     => 'required|integer',
                'description'  => 'nullable|string',
                'price'        => 'required|numeric',
                'unit'         => 'nullable|string|max:50',
                'type' => [
                                'required',
                                Rule::in([
                                    'Wigs',
                                    'physical',
                                    'bundle',
                                    'custom',
                                    'Extensions',
                                    'Oils',
                                    'Brushes',
                                    'Custom Wigs',
                                    'Braids',
                                    'Haircuts',
                                    'Facials',
                                    'Makeup',
                                    'Skincare',
                                    'Beard Care',
                                    'Ponytails',
                                    'Closures',
                                    'Tape-ins',
                                    'Shaving',
                                    'Hair Coloring',
                                    'Retouching',
                                    'Dreadlocks',
                                    'Cornrows',
                                    'Nails',
                                    'Pedicure',
                                    'Manicure',
                                    'Loc Maintenance',
                                    'Styling Tools',
                                    'Bonnets',
                                    'Edge Control',
                                    'Mousse',
                                    'Shampoo',
                                    'Conditioner',
                                    'Body Butter',
                                    'Lip Gloss',
                                    'Foundation',
                                    'Lashes',
                                    'Appointments',
                                    'Consultations',
                                    'Gift Cards',
                                    'Bundles',
                                    'Accessories',
                                    'Clippers',
                                    'Durags',
                                    'Wave Caps',
                                    'Dye Kits',
                                    'Detanglers',
                                ]),
                            ],
                'stock'        => 'nullable|integer',
                'sku'          => 'nullable|string|max:100',
                'category'     => 'nullable|string|max:100',
                'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
                'tags'         => 'nullable|array',
                'is_featured'  => 'nullable|boolean',
                'is_active'    => 'nullable|boolean',
            ]);
            if ($validator->fails()) {                    
                DB::rollback();
                $errors = $validator->errors()->all();
                $errorsString = implode(', ', $errors);
                return response()->json(['status'  => false, 'message' => $errorsString, 'errors'  => $validator->errors()], 422);  
                    }
            $validated = $validator->validated();
            $client_id = $request->user()->id;
            $space = Space::where('id', $validated['space_id'])
                        ->where('client_id', $client_id)
                        ->first();
            if (!$space) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'The selected space does not belong to your account.'
                ], 403);
            }
            $currency=$space->currency;
            if($request->hasFile('image')) {
            $image = $request->file('image');
            if ($image->isValid()) 
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('products', $imageName, 'public');
            }
            else{
                $imagePath=NULL;
            }
            $product = Product::create([
                ...$validated,
                'slug' => Str::slug($validated['name']),
                'client_id' => $request->user()->id,
                'image' => $imagePath,
                'currency'=>$currency,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'product' => $product,
                'image_url' => $imagePath ? asset('storage/' . $imagePath) : null,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            dd($e);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }
public function show($phoneNumber)
    {
        $space = DB::table('spaces')->where('space_phone', $phoneNumber)->first();
        if (!$space) {
            return response()->json(['message' => 'Space not Found'], 404);
        }
        $space_id=$space->id;
        $products = DB::table('products')->where('space_id', $space_id)->get();
        $products->transform(function ($product) {
        $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
        return $product;
            });
        return response()->json([
            'status'=>"200",
            'data' => $products,
            'message'=>"Products List"
        ]);
    }
public function update(Request $request, $id)
    {
    DB::beginTransaction();
    try {
        $client = Auth::user();
        if (!$client) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $product = Product::where('id', $id)->where('client_id', $client->id)->first();
        if (!$product) {
            return response()->json(['message' => 'Product not found or unauthorized'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:255',
            'space_id'     => 'sometimes|required|integer',
            'description'  => 'nullable|string',
            'price'        => 'sometimes|required|numeric',
            'unit'         => 'nullable|string|max:50',
            'type' => [
                'sometimes',
                Rule::in([
                    'Wigs', 'physical', 'bundle', 'custom', 'Extensions', 'Oils', 'Brushes', 'Custom Wigs', 'Braids',
                    'Haircuts', 'Facials', 'Makeup', 'Skincare', 'Beard Care', 'Ponytails', 'Closures', 'Tape-ins',
                    'Shaving', 'Hair Coloring', 'Retouching', 'Dreadlocks', 'Cornrows', 'Nails', 'Pedicure',
                    'Manicure', 'Loc Maintenance', 'Styling Tools', 'Bonnets', 'Edge Control', 'Mousse', 'Shampoo',
                    'Conditioner', 'Body Butter', 'Lip Gloss', 'Foundation', 'Lashes', 'Appointments',
                    'Consultations', 'Gift Cards', 'Bundles', 'Accessories', 'Clippers', 'Durags', 'Wave Caps',
                    'Dye Kits', 'Detanglers',
                ])
            ],
            'stock'        => 'nullable|integer',
            'sku'          => 'nullable|string|max:100',
            'category'     => 'nullable|string|max:100',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'tags'         => 'nullable|array',
            'is_featured'  => 'boolean',
            'is_active'    => 'boolean',
        ]);
        if ($validator->fails()) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validator->errors()->all()),
                'errors' => $validator->errors(),
            ], 422);
        }
        $validated = $validator->validated();
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
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('products', $imageName, 'public');
                $validated['image'] = $imagePath;
            }
        }
        $product->update($validated);
        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product,
            'image_url' => $product->image ? asset('storage/' . $product->image) : null,
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Product update failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong. Please try again.',
        ], 500);
        }
    }
public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted successfully']);
    }

public function addBulkProducts(Request $request)
    {
        $validated=$request->validate([
            'space_id' => 'required|exists:spaces,id',
            'file' => 'required|file|mimes:xlsx,csv',
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
                if (empty($data['name']) || empty($data['price'])) continue;
                Product::create([
                    'client_id'     => $client->id,
                    'space_id'      => $request->space_id,
                    'name'          => $data['name'],
                    'price'         => $this->sanitizePrice($data['price']),
                    'stock'         => $this->sanitizeStock($data['stock'] ?? 0),
                    'type'          => $data['type'] ?? 'product',
                    'unit'          => $data['unit'] ?? null,
                    'category'      => $data['category'] ?? null,
                    'sku'           => $data['sku'] ?? null,
                    'description'   => $data['description'] ?? null,
                    'is_featured'   => $data['is_featured'] ?? 0,
                    'is_active'     => $data['is_active'] ?? 1,
                ]);
            }
            return response()->json(['success' => true, 'message' => 'Bulk products uploaded successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during product upload.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
private function sanitizePrice($value)
    {
        $clean = preg_replace('/[^\d.]/', '', $value);
        $clean = preg_replace('/\.(?=.*\.)/', '', $clean);
        return is_numeric($clean) ? floatval($clean) : 0;
    }
private function sanitizeStock($value)
    {
        $clean = preg_replace('/[^\d]/', '', $value);
        return is_numeric($clean) ? intval($clean) : 0;
    }

public function getProductBySpace(Request $request){
    $validated=$request->validate([
        'space_id' => 'required|exists:spaces,id',
    ]);
    $currency = Space::where('id', $validated['space_id'])->value('currency');
    $products = Product::where('space_id', $validated['space_id'])
        ->get(['id','name', 'price','currency','stock']);
    $formatted = $products->map(function ($product) use ($currency) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'stock'=>$product->stock,
            'label' => $product->name . ' (' . $product->price . ' ' . $currency . ')' .'( Stock '. $product->stock.')',
        ];
    });
    return response()->json([
        'status'  => 200,
        'message' => 'Products List according to the space',
        'data'    => $formatted
    ]);
}
public function productStatusUpdate(){

}

}
