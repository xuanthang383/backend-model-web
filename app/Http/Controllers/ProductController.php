<?php

namespace App\Http\Controllers;

use App\Jobs\UploadFileToS3;
use App\Models\File;
use App\Models\Product;
use App\Models\ProductFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::with(['category', 'platform', 'render', 'material', 'colors', 'tags'])->get(), 200);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'platform', 'render', 'material', 'colors', 'tags'])->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product, 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'string',
            'category_id' => 'required|integer|exists:categories,id',
            'file_url' => ['required', 'url'],
            'image_urls' => 'nullable|array',
            'image_urls.*' => ['url']
        ]);
        $path = parse_url($request->file_url, PHP_URL_PATH);

        $relativePath = str_replace('/storage/temp/', '', $path);
        // dd ('file_path1'. $relativePath);
        $relativeName = str_replace('/storage/temp/models/', '', $path);

        // 🛑 Tạo Product mới
        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
        ]);

        $uploadedBy = Auth::id() ?? 1;
        $filesToInsert = [];

        // 🛑 Lưu file model (`file_url`) vào DB trước khi upload lên S3
        if (!empty($request->file_url)) {
            $fileRecord = File::create([
                'file_name' => $relativeName,
                'file_path' => $relativePath, // Lưu đường dẫn tạm
                'uploaded_by' => $uploadedBy
            ]);
            // dd($fileRecord->id);
            // 🔥 Đẩy lên queue để upload lên S3
            dispatch(new UploadFileToS3($fileRecord->id, $request->file_url, 'models'));

            ProductFiles::create(
                [
                    'file_id' => $fileRecord->id,
                    'product_id' => $product->id
                ]
                );

            $filesToInsert[] = $fileRecord;
        }

        // 🔥 Lặp qua danh sách `image_urls`, lưu vào DB trước rồi đẩy lên queue
        if (!empty($request->image_urls) && is_array($request->image_urls)) {
            foreach ($request->image_urls as $imageUrl) {
                $path = parse_url($imageUrl, PHP_URL_PATH);

                $relativePath = str_replace('/storage/temp/', '', $path);
                $relativeName = str_replace('/storage/temp/images/', '', $path);
                $imageRecord = File::create([
                    'file_name' => $relativeName,
                    'file_path' => $relativePath,
                    'uploaded_by' => $uploadedBy
                ]);

                dispatch(new UploadFileToS3($imageRecord->id, $imageUrl, 'images'));
                ProductFiles::create(
                    [
                        'file_id' => $imageRecord->id,
                        'product_id' => $product->id
                    ]
                    );

                $filesToInsert[] = $imageRecord;
            }
        }

        return response()->json([
            'message' => 'Product created successfully, files are being uploaded in the background',
            'product' => $product,
            'files' => $filesToInsert
        ], 201);
    }
}
