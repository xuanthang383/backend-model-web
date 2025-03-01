<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ChangeStatusRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Jobs\UploadFileToS3;
use App\Models\File;
use App\Models\Product;
use App\Models\ProductFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['files' => function ($query) {
            $query->wherePivot('is_thumbnail', true);
        }]);

        // Lọc theo tên sản phẩm (nếu có)
        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }

        // Lọc theo category_id (nếu có)
        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        // Lọc theo is_private: nếu is_private = 1 thì chỉ lấy sản phẩm có public = 0 hoặc public IS NULL
        if ($request->boolean('is_private')) {
            $query->where(function ($q) {
                $q->where('public', 0)->orWhereNull('public');
            });
        }

        // Lọc theo điều kiện "saved" (chỉ lấy sản phẩm của user và nằm trong bảng library_product)
        if ($request->boolean('is_saved')) {
            $userId = auth()->id()?:2; // Lấy ID của user hiện tại

            $query->where('user_id', $userId)
                ->whereIn('id', function ($subQuery) {
                    $subQuery->select('product_id')
                        ->from('library_product'); // Kiểm tra product_id có trong library_product
                });
        }

        return $this->paginateResponse($query, $request, "Success", function ($product) {
            // Lấy ảnh thumbnail (nếu có)
            $thumbnailFile = $product->files->first();
            $product->thumbnail = $thumbnailFile ? $thumbnailFile->file_path : null;

            unset($product->files); // Xóa danh sách files để response gọn hơn
            return $product;
        });
    }


    public function show($id)
    {
        $product = Product::with(['category', 'tags', 'files', 'platform', 'render'])->find($id);

        if (!$product) {
            return response()->json(['r' => 0, 'msg' => 'Product not found'], 404);
        }

        // Lấy tất cả `file_path` từ `product_files` và `files`
        $allFiles = File::whereIn('id', ProductFiles::where('product_id', $id)->pluck('file_id'))
            ->pluck('file_path')
            ->map(function ($filePath) {
                return $filePath;
            });

        // Lọc chỉ lấy những file có chứa "images/"
        $imageFiles = $allFiles->filter(function ($filePath) {
            return str_contains($filePath, 'images/');
        })->values(); // Reset index của array

        // Lấy thumbnail từ ảnh có `image = true` trong `product_files`
        $thumbnail = ProductFiles::where('product_id', $id)
            ->where('is_thumbnail', true)
            ->first();

        $thumbnailPath = $thumbnail ? File::find($thumbnail->file_id)->file_path : null;

        // Lấy `file_path` từ bảng `product_files` có `is_model = 1`
        $modelFileRecord = ProductFiles::where('product_id', $id)
            ->where('is_model', 1)
            ->first();

        $modelFilePath = $modelFileRecord ? File::find($modelFileRecord->file_id)->file_path : null;

        return response()->json([
            'r' => 1,
            'msg' => 'Product retrieved successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'is_ads' => $product->is_ads ?? 0,
                'is_favorite' => $product->is_favorite ?? 0,
                'description' => $product->description,
                'category_id' => $product->category_id,
                'platform' => $product->platform,
                'render' => $product->render,
                'file_path' => $modelFilePath, // Lấy file model từ product_files có is_model = 1
                'thumbnail' => $thumbnailPath, // Ảnh được chọn làm thumbnail
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'listImageSrc' => $imageFiles->toArray(), // Danh sách ảnh
                'category' => $product->category,
                'tags' => $product->tags,
                'files' => $product->files,
                'colors' => $product->colors ?? [],
                'materials' => $product->materials ?? []
            ]
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        $uploadedBy = Auth::id();

        // 🛑 Tạo Product mới Ubuntu
        //WSL integration with distro 'Ubuntu' unexpectedly stopped. Do you want to restart it?
        $product = Product::create([
            'name' => $validatedData->name,
            'category_id' => $validatedData->category_id,
            'platform_id' => $validatedData->platform_id,
            'render_id' => $validatedData->render_id,
            'status' => Product::STATUS_DRAFT,
            'user_id' => $uploadedBy,
            'public'=>$request->public
        ]);

        // 🛑 Lưu Colors vào bảng `product_colors`
        if (!empty($validatedData->color_ids)) {
            $product->colors()->attach($validatedData->color_ids);
        }
        // 🛑 Lưu Materials vào bảng `product_materials`
        if (!empty($validatedData->material_ids)) {
            $product->materials()->attach($validatedData->material_ids);
        }
        // 🛑 Lưu Tags vào bảng `product_tags`
        if (!empty($validatedData->tag_ids)) {
            $product->tags()->attach($validatedData->tag_ids);
        }

        // 🛑 Lưu file model (`file_url`) vào DB trước khi upload lên S3
        // 🛑 Xử lý `file_url` (model file)
        $fileName = basename($validatedData->file_url);
        $fileRecord = File::create([
            'file_name' => $fileName,
            'file_path' => config('app.file_path') . File::MODEL_FILE_PATH . $fileName,
            'uploaded_by' => $uploadedBy
        ]);

        // 🔥 Đẩy lên queue để upload lên S3
        dispatch(new UploadFileToS3($fileRecord->id, $validatedData->file_url, 'models'));

        ProductFiles::create([
            'file_id' => $fileRecord->id,
            'product_id' => $product->id,
            'is_model' => true
        ]);

        if (!empty($validatedData->image_urls) && is_array($validatedData->image_urls)) {
            $imageUrls = array_values($validatedData->image_urls);

            foreach ($imageUrls as $key => $imageUrl) {
                $imgName = basename($imageUrl);
                $imageRecord = File::create([
                    'file_name' => $imgName,
                    'file_path' => config("app.file_path") . File::IMAGE_FILE_PATH . $imgName,
                    'uploaded_by' => $uploadedBy
                ]);

                dispatch(new UploadFileToS3($imageRecord->id, $imageUrl, 'images'));

                ProductFiles::create([
                    'file_id' => $imageRecord->id,
                    'product_id' => $product->id,
                    'is_thumbnail' => $key == 0,
                ]);
            }
        }

        return $this->successResponse(
            ['product' => $product->load('colors', 'materials', 'tags')],
            'Product created successfully with colors, materials, and tags',
            201
        );
    }

    public function changeStatus(ChangeStatusRequest $request, $id)
    {
        $requestValidate = $request->validated();

        $product = Product::find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $product['status'] = $requestValidate->status;
        $product->save();

        return $this->successResponse(
            ['product' => $product],
            'Product status updated successfully'
        );
    }
}
