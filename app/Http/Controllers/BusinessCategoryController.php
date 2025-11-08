<?php

namespace App\Http\Controllers;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
class BusinessCategoryController extends Controller
{
    public function index()
            {
                try {
                    $categories = BusinessCategory::all();
                    return response()->json([
                        'data' => $categories,
                        'message' => 'Business Categories Fetched Successfully',
                    ], 200);

                } catch (Exception $e) {
                    return response()->json([
                        'message' => 'Failed to fetch business categories',
                        'error' => $e->getMessage(),
                    ], 500);
                }
            }
            

    public function store(Request $request)
            {
                $validated = $request->validate([
                    'name' => 'required|string|unique:business_categories,name',
                    'template'=>'required|string',
                    'description' => 'nullable|string',
                    'main_products_services' => 'nullable|string',
                ]);

            
            DB::beginTransaction();

            try {
                $category = BusinessCategory::create($validated);
                DB::commit(); 
                return response()->json([
                    'data' => $category,
                    'message' => 'Category created',
                ], 201);

            } catch (Exception $e) {
                DB::rollBack(); 
                return response()->json([
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

    public function show($id)
            {
                $category = BusinessCategory::find($id);

                if (!$category) {
                    return response()->json(['message' => 'Not found'], 404);
                }

                return response()->json($category, 200);
            }

        }