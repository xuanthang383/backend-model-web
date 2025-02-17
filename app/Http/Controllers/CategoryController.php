<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Material;
use App\Models\Platform;
use App\Models\Render;
use App\Models\Tag;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->paginateResponse(Category::query(), $request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Material::create(['name' => 'Wood']);
        Material::create(['name' => 'Metal']);
        Material::create(['name' => 'Plastic']);
        Material::create(['name' => 'Glass']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
