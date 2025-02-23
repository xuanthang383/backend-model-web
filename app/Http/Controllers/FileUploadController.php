<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Hàm chung lưu file tạm thời
     */
    private function storeTempFile($file, $folder)
    {
        try {
            $fileName = preg_replace('/\s+/', '', $file->getClientOriginalName());
            // $fileName = time() . "_" . $file->getClientOriginalName();
            $filePath = $file->storeAs("temp/{$folder}", $fileName, 'public');

            if (!$filePath) {
                Log::error("Lưu file thất bại!", [
                    'filename' => $file->getClientOriginalName(),
                    'folder' => $folder
                ]);
                throw new \Exception("Không thể lưu file.");
            }

            return asset('storage/' . $filePath); // Trả về đường dẫn truy cập
        } catch (\Exception $e) {
            Log::error("Lỗi khi lưu file: " . $e->getMessage(), [
                'filename' => $file->getClientOriginalName(),
                'folder' => $folder
            ]);
            return null;
        }
    }

    /**
     * API Upload hình ảnh tạm thời
     */
    public function uploadTempImage(Request $request)
    {
        return $this->uploadFile($request, 'images', 10240, 'Image uploaded successfully');
    }

    /**
     * API Upload file model tạm thời
     */
    public function uploadTempModel(Request $request)
    {
        return $this->uploadFile($request, 'models', 102400, 'Model uploaded successfully');
    }

    /**
     * Hàm chung xử lý upload file
     */
    private function uploadFile(Request $request, $folder, $maxSize, $successMessage)
    {
        // dd(sys_get_temp_dir());
        try {
            // Validate file
            $request->validate([
                'file' => "required|file|max:$maxSize"
            ]);

            $file = $request->file('file');

            // Kiểm tra file có hợp lệ không
            if (!$file->isValid()) {
                Log::error("Upload thất bại: " . $file->getErrorMessage(), [
                    'filename' => $file->getClientOriginalName(),
                    'folder' => $folder
                ]);
                return response()->json(['error' => 'File upload failed', 'message' => $file->getErrorMessage()], 400);
            }

            // Lưu file tạm thời
            $fileUrl = $this->storeTempFile($file, $folder);

            if (!$fileUrl) {
                return response()->json(['error' => 'File upload failed'], 500);
            }

            return response()->json([
                'message' => $successMessage,
                'file_url' => $fileUrl
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation lỗi khi upload: " . $e->getMessage(), [
                'folder' => $folder
            ]);
            return response()->json(['error' => 'File upload failed', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error("Lỗi không xác định khi upload: " . $e->getMessage(), [
                'folder' => $folder
            ]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
