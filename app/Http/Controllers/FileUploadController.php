<?php

namespace App\Http\Controllers;

use App\Models\ProductFile;
use Aws\Credentials\CredentialProvider;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Hàm chung lưu file tạm thời
     */
    private function storeTempFile($file, $folder)
    {
        $filePath = $file->store("temp/{$folder}", 'public');
        return asset("storage/$filePath"); // Trả về đường dẫn truy cập
    }

    /**
     * API Upload hình ảnh tạm thời
     */
    public function uploadTempImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:10240' // Ảnh tối đa 10MB
        ]);

        $imageUrl = $this->storeTempFile($request->file('file'), 'images');

        return response()->json([
            'message' => 'Image uploaded successfully',
            'file_url' => $imageUrl
        ]);
    }

    /**
     * API Upload file model tạm thời
     */
    public function uploadTempModel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:200000'
        ]);

        $fileUrl = $this->storeTempFile($request->file('file'), 'models');

        return response()->json([
            'message' => 'Model uploaded successfully',
            'file_url' => $fileUrl
        ]);
    }
}
