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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'file_url' => ['required', 'url'],
            'image_urls' => 'nullable|array',
            'image_urls.*' => ['url']
        ]);

        // 🛑 Tạo Product mới
        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => 'abc'
        ]);

        $uploadedBy = Auth::id() ?? 1;
        $filesToInsert = [];

        // 🛑 Lưu file model (`file_url`) vào DB trước khi upload lên S3
        if (!empty($request->file_url)) {
            $fileRecord = File::create([
                'file_name' => basename($request->file_url),
                'file_path' => $request->file_url, // Lưu đường dẫn tạm
                'uploaded_by' => $uploadedBy
            ]);

            // 🔥 Đẩy lên queue để upload lên S3
            dispatch(new UploadFileToS3($fileRecord->id, $request->file_url, 'models'));

            $filesToInsert[] = $fileRecord;
        }

        // 🔥 Lặp qua danh sách `image_urls`, lưu vào DB trước rồi đẩy lên queue
        if (!empty($request->image_urls) && is_array($request->image_urls)) {
            foreach ($request->image_urls as $imageUrl) {
                $imageRecord = File::create([
                    'file_name' => basename($imageUrl),
                    'file_path' => $imageUrl, // Lưu đường dẫn tạm
                    'uploaded_by' => $uploadedBy
                ]);

                dispatch(new UploadFileToS3($imageRecord->id, $imageUrl, 'images'));

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
