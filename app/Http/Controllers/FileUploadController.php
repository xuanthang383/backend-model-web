<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ProductFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends BaseController
{
    public function getModelFileUrl($product_id)
    {
        // Tìm file liên quan đến sản phẩm và có is_model = true
        $productFile = ProductFiles::where('product_id', $product_id)
            ->where('is_model', true)
            ->first();

        if (!$productFile) {
            return response()->json(['error' => 'File not found in product_files'], 404);
        }

        // Lấy thông tin file từ bảng files
        $file = File::find($productFile->file_id);

        if (!$file) {
            return response()->json(['error' => 'File not found in files table'], 404);
        }

        // Kiểm tra xem file_path có null không
        if (empty($file->file_path)) {
            return response()->json(['error' => 'file_path is null'], 400);
        }

        // Tạo Presigned URL từ S3 (hết hạn sau 10 phút)
        try {
            $presignedUrl = Storage::disk('s3')->temporaryUrl(
                $file->file_path,
                now()->addMinutes(5)
            );
                return $this->successResponse(['url' => $presignedUrl]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate S3 URL');
        }
    }
    /**
     * Hàm chung lưu file tạm thời
     */
    private function storeTempFile($file, $folder)
    {
        try {
            $fileName = time() . "_" . preg_replace('/\s+/', '', $file->getClientOriginalName());
            // $fileName = time() . "_" . $file->getClientOriginalName();
            $filePath = $file->storeAs("temp/{$folder}", $fileName, 'public');

            if (!$filePath) {
                Log::error("Lưu file thất bại!", context: [
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
