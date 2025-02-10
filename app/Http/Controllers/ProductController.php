<?php

namespace App\Http\Controllers;

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
            'category_id' => 'required|int',
            'file_url' => ['required', 'url', function ($attribute, $value, $fail) {
                $path = str_replace(url('/storage'), 'public', $value); // Chuyển đổi URL về đường dẫn trong storage

                // Chuyển URL sang đường dẫn tương đối (storage/app/public/)
                $relativePath = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));

                if (!Storage::disk('public')->exists($relativePath)) {
                    $fail("The file does not exist in temporary storage.");
                }
            }],
            'image_urls' => 'nullable|array',
            'image_urls.*' => ['url', function ($attribute, $value, $fail) {
                $path = str_replace(url('/storage'), 'public', $value);

                // Chuyển URL sang đường dẫn tương đối (storage/app/public/)
                $relativePath = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));

                if (!Storage::disk('public')->exists($relativePath)) {
                    $fail("One or more image URLs do not exist in temporary storage.");
                }
            }]
        ]);

        // Tạo product
        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category,
            'description' => 'abc'
        ]);

        $uploadedBy = Auth::id() ?? 1; // Nếu có user đăng nhập, lấy user_id

        $filesToInsert = [];

        // 🔥 Hàm xử lý upload file lên S3 theo thư mục mong muốn
        function moveToS3($fileUrl, $folder)
        {
            $localPath = str_replace(url('/storage'), 'public', parse_url($fileUrl, PHP_URL_PATH));

            if (Storage::disk('public')->exists($localPath)) {
                $s3Path = "$folder/" . basename($fileUrl);
                Storage::disk('s3')->put($s3Path, Storage::disk('public')->get($localPath));
                Storage::disk('public')->delete($localPath); // Xóa file tạm sau khi đẩy lên S3
                return Storage::disk('s3')->url($s3Path);
            }

            return $fileUrl; // Nếu file không tồn tại, giữ nguyên URL
        }

        // 🛑 Lưu file chính (`file_url`) vào thư mục `models/` trên S3
        if (!empty($request->file_url)) {
            $s3Url = moveToS3($request->file_url, 'models');
            $filesToInsert[] = [
                'file_name' => basename($s3Url),
                'file_path' => $s3Url,
                'uploaded_by' => $uploadedBy,
                'created_at' => now()
            ];
        }

        // 🔥 Lặp qua danh sách `image_urls`, upload vào thư mục `images/` trên S3
        if (!empty($request->image_urls) && is_array($request->image_urls)) {
            foreach ($request->image_urls as $imageUrl) {
                $s3Url = moveToS3($imageUrl, 'images');
                $filesToInsert[] = [
                    'file_name' => basename($s3Url),
                    'file_path' => $s3Url,
                    'uploaded_by' => $uploadedBy,
                    'created_at' => now()
                ];
            }
        }

        // 🛑 Chèn tất cả file vào bảng `files`
        if (!empty($filesToInsert)) {
            File::insert($filesToInsert);
        }

        return response()->json([
            'message' => 'Product created successfully and files uploaded to S3',
            'product' => $product,
            'files' => $filesToInsert
        ], 201);
    }
}
