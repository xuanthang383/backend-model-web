<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;

class TagController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tag::query();

        // Nếu có tham số search, thực hiện tìm kiếm LIKE
        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }
        return $this->paginateResponse($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate dữ liệu đầu vào
            $request->validate([
                'name' => 'required|string|max:255|unique:tags,name',
            ]);

            // Tạo tag
            $tag = Tag::create([
                'name' => $request->name
            ]);

            return $this->successResponse($tag, 'Tag created successfully!', 201);
        } catch (ValidationException $e) {
            // Trả về lỗi nếu validation không thành công
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            // Trả về lỗi chung nếu có ngoại lệ khác
            return $this->errorResponse('Something went wrong!', 500, $e->getMessage());
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
