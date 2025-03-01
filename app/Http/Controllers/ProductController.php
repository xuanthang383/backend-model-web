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
        if ($request->query('is_private') == 1) {
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







    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:products,name',
            'category_id' => 'required|integer|exists:categories,id',
            'platform_id' => 'nullable|integer|exists:platforms,id',
            'render_id' => 'nullable|integer|exists:renders,id',
            'file_url' => ['required', 'url', function ($attribute, $value, $fail) {
                if (!preg_match('/\.(rar|zip)$/i', $value)) {
                    $fail('The file_url must be a valid RAR or ZIP file.');
                }
            }],
            'image_urls' => 'nullable|array',
            'image_urls.*' => ['required', 'url', function ($attribute, $value, $fail) {
                if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $value)) {
                    $fail('Each image must be a valid image URL (jpg, jpeg, png, gif, webp).');
                }
            }],
            // 'image_urls.*' => ['url'],
            'color_ids' => 'nullable|array',
            'color_ids.*' => 'integer|exists:colors,id',
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'integer|exists:materials,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id'
        ]);
        $request->validate([
            'image_urls' => 'nullable|array',

        ]);

        $uploadedBy = Auth::id() ?? 1;
        $filesToInsert = [];

        // 🛑 Xử lý `file_url` (model file)
        $filePath = parse_url($request->file_url, PHP_URL_PATH);
        $relativeFilePath = str_replace('/storage/temp/', '', $filePath);
        $relativeFileName = str_replace('/storage/temp/models/', '', $filePath);

        // 🛑 Tạo Product mới Ubuntu
        //WSL integration with distro 'Ubuntu' unexpectedly stopped. Do you want to restart it?
        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'platform_id' => $request->platform_id,
            'render_id' => $request->render_id,
            'user_id'=> $uploadedBy,
            'public'=>$request->public?:0
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
            'file_path' => env('URL_IMAGE') . $relativeFilePath,
            'uploaded_by' => $uploadedBy
        ]);

        // 🔥 Đẩy lên queue để upload lên S3
        dispatch(new UploadFileToS3($fileRecord->id, $request->file_url, 'models'));

        ProductFiles::create([
            'file_id' => $fileRecord->id,
            'product_id' => $product->id,
            'is_model' => true
        ]);

        $filesToInsert[] = $fileRecord;

        // 🔥 Xử lý danh sách `image_urls`
        $imagePaths = [];

        if (!empty($request->image_urls) && is_array($request->image_urls)) {
            $imageUrls = array_values($request->image_urls);

            foreach ($imageUrls as $key => $imageUrl) {

                $imgPath = parse_url($imageUrl, PHP_URL_PATH);
                $relativeImgPath = str_replace('/storage/temp/', '', $imgPath);
                $relativeImgName = str_replace('/storage/temp/images/', '', $imgPath);

                $imageRecord = File::create([
                    'file_name' => $relativeImgName,
                    'file_path' => env('URL_IMAGE') . $relativeImgPath,
                    'uploaded_by' => $uploadedBy
                ]);

                dispatch(new UploadFileToS3($imageRecord->id, $imageUrl, 'images'));

                $dataInsert = [
                    'file_id' => $imageRecord->id,
                    'product_id' => $product->id,
                ];

                if ($key == 0) {
                    $dataInsert['is_thumbnail'] = true;
                }

                ProductFiles::create($dataInsert);

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
