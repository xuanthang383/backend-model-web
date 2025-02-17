<?php

namespace App\Http\Controllers;

use App\Jobs\UploadFileToS3;
use App\Models\File;
use App\Models\Product;
use App\Models\ProductFiles;
use App\Models\ProductColor;
use App\Models\ProductMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        return $this->paginateResponse(Product::query(), $request);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'tags', 'files', 'colors', 'materials'])->find($id);

        if (!$product) {
            return response()->json(['r' => 0, 'msg' => 'Product not found'], 404);
        }

        return response()->json([
            'r' => 1,
            'msg' => 'Product retrieved successfully',
            'data' => $product
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category_id' => 'required|integer|exists:categories,id',
            'platform_id' => 'nullable|integer|exists:platforms,id',
            'render_id' => 'nullable|integer|exists:renders,id',
            'file_url' => ['required', 'url'],
            'image_urls' => 'nullable|array',
            'image_urls.*' => ['url'],
            'color_ids' => 'nullable|array',
            'color_ids.*' => 'integer|exists:colors,id',
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'integer|exists:materials,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id'
        ]);

        $uploadedBy = Auth::id() ?? 1;
        $filesToInsert = [];

        // 🛑 Xử lý `file_url` (model file)
        $filePath = parse_url($request->file_url, PHP_URL_PATH);
        $relativeFilePath = str_replace('/storage/temp/', '', $filePath);
        $relativeFileName = str_replace('/storage/temp/models/', '', $filePath);

        // 🛑 Tạo Product mới
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'platform_id' => $request->platform_id,
            'render_id' => $request->render_id,
            'file_path' => $relativeFilePath,
            'image_path' => null,
        ]);

        // 🛑 Lưu Colors vào bảng `product_colors`
        if (!empty($request->color_ids)) {
            $product->colors()->attach($request->color_ids);
        }

        // 🛑 Lưu Materials vào bảng `product_materials`
        if (!empty($request->material_ids)) {
            $product->materials()->attach($request->material_ids);
        }

        // 🛑 Lưu Tags vào bảng `product_tags`
        if (!empty($request->tag_ids)) {
            $product->tags()->attach($request->tag_ids);
        }

        // 🛑 Lưu file model (`file_url`) vào DB trước khi upload lên S3
        $fileRecord = File::create([
            'file_name' => $relativeFileName,
            'file_path' => $relativeFilePath,
            'uploaded_by' => $uploadedBy
        ]);

        // 🔥 Đẩy lên queue để upload lên S3
        dispatch(new UploadFileToS3($fileRecord->id, $request->file_url, 'models'));

        ProductFiles::create([
            'file_id' => $fileRecord->id,
            'product_id' => $product->id
        ]);

        $filesToInsert[] = $fileRecord;

        // 🔥 Xử lý danh sách `image_urls`
        $imagePaths = [];
        if (!empty($request->image_urls) && is_array($request->image_urls)) {
            foreach ($request->image_urls as $imageUrl) {
                $imgPath = parse_url($imageUrl, PHP_URL_PATH);
                $relativeImgPath = str_replace('/storage/temp/', '', $imgPath);
                $relativeImgName = str_replace('/storage/temp/images/', '', $imgPath);

                $imageRecord = File::create([
                    'file_name' => $relativeImgName,
                    'file_path' => $relativeImgPath,
                    'uploaded_by' => $uploadedBy
                ]);

                dispatch(new UploadFileToS3($imageRecord->id, $imageUrl, 'images'));

                ProductFiles::create([
                    'file_id' => $imageRecord->id,
                    'product_id' => $product->id
                ]);

                $filesToInsert[] = $imageRecord;
                $imagePaths[] = $relativeImgPath;
            }
        }

        // 🛑 Cập nhật ảnh đại diện cho product từ danh sách `image_urls`
        if (!empty($imagePaths)) {
            $product->update(['image_path' => $imagePaths[0]]);
        }

        return response()->json([
            'r' => 0,
            'msg' => 'Product created successfully with colors, materials, and tags',
            'data' => [
                'product' => $product->load('colors', 'materials', 'tags'),
                'files' => $filesToInsert
            ]
        ], 201);
    }
}
