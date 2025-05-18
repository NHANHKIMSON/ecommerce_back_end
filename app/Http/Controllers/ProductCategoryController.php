<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductCategory $productCategory)
    {
        return response()->json(ProductCategory::all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // $ProductCategories = ProductCategory::create($field);
        // return response($ProductCategories);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $field = $request->validate([
            'name'=> 'required',
            'photo' => 'required'
        ]);
        $productCategories = ProductCategory::create($field);
        return response()->json($productCategories);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $category)
    {
        return response()->json($category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $category)
    {
        $field = $request->validate([
            'name' => 'required',
            'photo' => 'required'
        ]);
        $category->update($field);
        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $category)
    {
        $category->delete();
        return response()->json('Category was delted successfully!');        
    }
}
