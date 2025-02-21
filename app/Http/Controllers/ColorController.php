<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;

class ColorController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->paginateResponse(Color::query(), $request);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate dữ liệu đầu vào
            $request->validate([
                'name' => 'required|string|max:255|unique:colors,name',
            ]);

            // Tạo color
            $color = Color::create([
                'name' => $request->name,
                'hex_code'=> $request->hex_code
            ]);

            return response()->json([
                'message' => 'Color created successfully!',
                'color' => $color
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong!',
                'details' => $e->getMessage()
            ], 500);
        }
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
