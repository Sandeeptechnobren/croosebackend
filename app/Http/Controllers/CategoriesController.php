<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Categories;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;


class CategoriesController extends Controller
{
    public function add_categories(Request $request)
        {
            $user = Auth::user();
            $client_id = $user->id;

            // Validate request data
            $validated = $request->validate([
                'name'       => 'required|string|max:255',
                // 'slug'       => 'nullable|string|max:155',  // allow auto-generation
                'type'       => 'required|in:product,service',
                'is_active'  => 'nullable|boolean'
            ]);

            $validated['is_active'] = $validated['is_active'] ?? true;

            // Create the category
            $category = Categories::create([
                'client_id' => $client_id,
                ...$validated
            ]);

            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => 'Category added successfully'
            ]);
        }

    //     public function products_categories(){
    //         $user=Auth::user();
    //         $client_id=$user->id;
    //         $categories = DB::table('categories')
    //                 ->where('client_id',$client_id)
    //                 ->where('type','product')
    //                 ->get();
    //         return response()->json([
    //             'success'=>true,
    //             'data'=>$categories,
    //             'message'=>'Products List Fetched'
    //         ]);
    //     }


    // public function services_categories(){
    //     $user=Auth::user();
    //     $client_id=$user->id;
    //     $categories = DB::table('categories')
    //             ->where('client_id',$client_id)
    //             ->where('type','service')
    //             ->get();
    //     return response()->json([
    //         'success'=>true,
    //         'data'=>$categories,
    //         'message'=>'Services Category'
    //     ]);
    // }
        public function get_categories(Request $request)
            {
                $user = Auth::user();
                $client_id = $user->id;

                $validated = $request->validate([
                    'type' => 'required|in:product,service'
                ]);

                $type = $validated['type'];

                $categories = Categories::where('client_id', $client_id)
                    ->where('type', $type)
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $categories,
                    'message' => ucfirst($type) . ' categories fetched successfully'
                ]);
            }
}
