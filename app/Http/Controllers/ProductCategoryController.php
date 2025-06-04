<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ProductCategory::all();
        
        $categories->each(function ($category) {
            if ($category->photo) {
                // Use the correct path format for your bucket
                $category->photo_url = Storage::disk('minio')->url($category->photo);
            }
        });
        
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            // Store directly in the 'images' bucket without subfolder
            $path = $request->file('photo')->store('', 'minio'); // Empty string for root of bucket
            $validated['photo'] = $path;
            
            $category = ProductCategory::create($validated);
            
            // Generate URL - MinIO will serve from the root of 'images' bucket
            $category->photo_url = Storage::disk('minio')->url($path);
            
            return response()->json($category, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'File upload failed',
                'message' => $e->getMessage(),
                'debug' => [
                    'bucket' => config('filesystems.disks.minio.bucket'),
                    'path' => $path ?? 'not stored'
                ]
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $category)
    {
        if ($category->photo) {
            $category->photo_url = Storage::disk('minio')->url($category->photo);
        }
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $category)
    {
        $validated = $request->validate([
            'name' => 'nullable',
            'photo' => 'nullable'
        ]);

        try {
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($category->photo && Storage::disk('minio')->exists($category->photo)) {
                    Storage::disk('minio')->delete($category->photo);
                }
                
                // Store new photo in root of bucket
                $path = $request->file('photo')->store('', 'minio');
                $validated['photo'] = $path;
            }

            $category->update($validated);
            
            // Update URL if new photo was uploaded
            if (isset($validated['photo'])) {
                $category->photo_url = Storage::disk('minio')->url($validated['photo']);
            }
            
            return response()->json($category);
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
    public function destroy(ProductCategory $category)
    {
        try {
            // Delete photo from MinIO if exists
            if ($category->photo && Storage::disk('minio')->exists($category->photo)) {
                Storage::disk('minio')->delete($category->photo);
            }
            
            $category->delete();
            
            return response()->json([
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Deletion failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}