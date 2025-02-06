<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function upload3dModel(Request $request)
    {
        // 1️⃣ Xác thực file tải lên
        $request->validate([
            'file' => 'required|file|max:102400', // Giới hạn 100MB
        ]);

        // 2️⃣ Lấy file từ request
        $file = $request->file('file');
        $fileName = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = 'uploads/' . $fileName;

        // 3️⃣ Upload file lên S3
        Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');

        // 4️⃣ Trả về URL file trên S3
        return response()->json([
            'message' => 'File uploaded successfully!',
            'file_url' => Storage::disk('s3')->url($filePath),
        ]);
    }
}

