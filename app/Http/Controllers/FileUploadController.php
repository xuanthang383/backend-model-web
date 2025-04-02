<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ProductFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends BaseController
{
    public function getModelFileUrl(Request $request)
    {
        $message = $request->input('message');
        $signatureBase64 = $request->input('signature');

        if (!$message || !$signatureBase64) {
            return $this->errorResponse('Missing message or signature', 400);
        }

        $signature = base64_decode($signatureBase64);
        $clientPublicKey = file_get_contents(storage_path('public.pem'));

        $valid = openssl_verify($message, $signature, $clientPublicKey, OPENSSL_ALGO_SHA256);
        if (!$valid) {
            return $this->errorResponse('Invalid signature', 403);
        }

        $data = json_decode($message, true);
        $product_id = $data['product_id'] ?? null;
        $timestamp = $data['timestamp'] ?? 0;

        if (!$product_id) {
            return $this->errorResponse('Missing product_id in message', 400);
        }

        if (abs(time() - $timestamp) > 30) {
            return $this->errorResponse('Request expired', 403);
        }

        sleep(30);

        $productFile = ProductFiles::where('product_id', $product_id)
            ->where('is_model', 1)
            ->first();

        if (!$productFile) {
            return $this->errorResponse('File not found in product_files', 404);
        }

        $file = File::find($productFile->file_id);
        if (!$file || empty($file->file_path)) {
            return $this->errorResponse('Invalid file record', 404);
        }

        $cleanedPath = str_replace(env('URL_IMAGE'), '', $file->file_path);

        try {
            // 🟢 Trả file về cho client download
            return Storage::disk('s3')->download($cleanedPath, $file->file_name);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to download from S3', ['error' => $e->getMessage()], 500);
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
