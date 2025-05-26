<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category') // Use Eloquent relationship
            ->select('products.*')
            ->addSelect(['category_name' => ProductCategory::select('name')
                ->whereColumn('id', 'products.category_id')
            ])
            ->paginate(6);
            
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'required|image|max:2048',
            'price' => 'required|numeric|min:0',
            'instock' => 'required|integer|min:0',
            'description' => 'required|string',
            'category_id' => 'required|exists:product_categories,id'
        ]);

        try {
            $path = $request->file('photo')->store('products', 'minio');
            $validated['photo'] = $path;
            
            $product = $request->user()->products()->create($validated);
            $product->photo_url = Storage::disk('minio')->url($product->photo);
            
            return response()->json($product, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'File upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('category'); // Eager load the relationship
        $product->photo_url = Storage::disk('minio')->url($product->photo);
        
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        Gate::authorize('modify', $product); // Uncomment when you have the policy
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'price' => 'required|numeric|min:0',
            'instock' => 'required|integer|min:0',
            'description' => 'required|string',
            'category_id' => 'required|exists:product_categories,id'
        ]);

        try {
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($product->photo) {
                    Storage::disk('minio')->delete($product->photo);
                }
                
                $path = $request->file('photo')->store('products', 'minio');
                $validated['photo'] = $path;
            }

            $product->update($validated);
            $product->photo_url = Storage::disk('minio')->url($product->photo);
            
            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        Gate::authorize('modify', $product); // Uncomment when you have the policy
        
        try {
            // Delete associated photo
            if ($product->photo) {
                Storage::disk('minio')->delete($product->photo);
            }
            
            $product->delete();
            
            return response()->json([
                'message' => 'Product was deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Deletion failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}