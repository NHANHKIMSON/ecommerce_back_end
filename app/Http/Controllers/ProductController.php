<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\ProductCategory;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(){
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::join('product_categories', 'products.category_id', '=', 'product_categories.id')
        ->select('products.*', 'product_categories.name as category_name')->paginate(8);
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $field = $request->validate([
            'name' => 'required',
            'photo' => 'required|image|max:2048',
            'price' => 'required',
            'instock' => 'required',
            'description' => 'required',
            'category_id' => 'required|exists:product_categories,id'
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');

            // Upload to MinIO in 'products/' folder
            $path = $file->store('products', 'minio');

            if ($path) {
                $field['photo'] = $path;
            } else {
                return response()->json(['error' => 'File upload failed'], 500);
            }
        } else {
            return response()->json(['error' => 'No photo file found'], 422);
        }

        $product = $request->user()->products()->create($field);

        if (!empty($product->photo)) {
            $product->photo_url = Storage::disk('minio')->url($product->photo);
        }

        return response()->json($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product = Product::join('product_categories', 'products.category_id', '=', 'product_categories.id')
        ->select('products.*', 'product_categories.name as category_name')->where('products.id', '=', $product->id)->get();
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // Gate::authorize('modify', $product);
        $field = $request->validate([
            'name' => 'required',
            'photo' => 'nullable|image|max:2048',
            'price' => 'required',
            'instock' => 'required',
            'description' => 'required',
            'category_id' => 'required|exists:product_categories,id'
        ]);

        // Only handle photo if it's uploaded
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');

            // Upload to MinIO
            $path = $file->store('products', 'minio');

            if ($path) {
                $field['photo'] = $path;
            } else {
                return response()->json(['error' => 'File upload failed'], 500);
            }
        }

        // Update the existing product
        $product->update($field);

        // Append the photo URL
        if (!empty($product->photo)) {
            $product->photo_url = Storage::disk('minio')->url($product->photo);
        }

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return ['message: ' => 'Prodcut Was Deleted!'];
    }
}
