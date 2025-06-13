<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Material;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;

class MaterialController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->paginateResponse(Material::query(), $request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate dữ liệu đầu vào
            $request->validate([
                'name' => 'required|string|max:255|unique:materials,name',
            ]);

            // Tạo material
            $material = Material::create([
                'name' => $request->name
            ]);

            return $this->successResponse(
                $material,
                'Material created successfully!',
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->validator->errors());
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Something went wrong!',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $material = Material::findOrFail($id);
            return $this->successResponse($material, 'Material fetched successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Material not found!',
                404,
                $e->getMessage()
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:materials,name,' . $id,
            ]);

            $material = Material::findOrFail($id);
            $material->name = $request->name;
            $material->save();

            return $this->successResponse(
                $material,
                'Material updated successfully!'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->validator->errors());
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Something went wrong!',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $material = Material::findOrFail($id);
            $material->delete();
            return $this->successResponse(null, 'Material deleted successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Something went wrong!',
                500,
                $e->getMessage()
            );
        }
    }
}
