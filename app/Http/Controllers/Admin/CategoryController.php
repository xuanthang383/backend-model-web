<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Category\CreateDTO;
use App\DTO\Category\UpdateDTO;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::whereNull('parent_id')->with('children');

        // Filter by name if provided
        if ($request->has('name')) {
            $name = $request->input('name');
            $query->where(function ($q) use ($name) {
                $q->where('name', 'LIKE', "%{$name}%")
                    ->orWhereHas('children', function ($subQ) use ($name) {
                        $subQ->where('name', 'LIKE', "%{$name}%");
                    });
            });
        }

        return $this->paginateResponse($query, $request, "Get list category", function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'children' => $category->children->map(function ($child) use ($category) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'parent_id' => $category->id,
                    ];
                }),
            ];
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $validatedData = new CreateDTO($request->validated());
            $category = Category::create([
                'name' => $validatedData->name,
                'parent_id' => $validatedData->parent_id
            ]);

            return $this->successResponse(
                ['category' => $category],
                'Category created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create category', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $category = Category::with('children')->findOrFail($id);

            return $this->successResponse([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                        ];
                    }),
                ]
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Category not found', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        try {
            $validatedData = new UpdateDTO($request->validated());
            $category = Category::findOrFail($id);

            // Prevent creating circular reference
            if ($validatedData->parent_id == $id) {
                return $this->errorResponse('Category cannot be its own parent', 422);
            }

            // Check if the new parent_id would create a circular reference
            if ($validatedData->parent_id) {
                $parent = Category::find($validatedData->parent_id);
                while ($parent) {
                    if ($parent->id == $id) {
                        return $this->errorResponse('Cannot create circular reference in category hierarchy', 422);
                    }
                    $parent = $parent->parent;
                }
            }

            $category->update([
                'name' => $validatedData->name,
                'parent_id' => $validatedData->parent_id
            ]);

            return $this->successResponse([
                'category' => $category
            ], 'Category updated successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Category not found', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::findOrFail($id);

            // Check if category has children
            if ($category->children()->count() > 0) {
                return $this->errorResponse('Cannot delete category with children. Delete children first or reassign them.', 422);
            }

            $category->delete();

            return $this->successResponse(null, 'Category deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Category not found', 404);
        }
    }
}
