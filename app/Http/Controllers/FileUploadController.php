<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ProductFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Jobs\UploadFileToS3;

class FileUploadController extends BaseController
{
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
    public function uploadAvatar(Request $request)
    {
        try {
            // Validate file
            $request->validate([
                'file_url' => 'required|string'
            ]);

            $fileUrl = $request->input('file_url');
            $originalFileName = basename($fileUrl);
            $timestamp = time();
            $fileName = $timestamp . "_" . $originalFileName;

            // Lưu URL avatar cũ
            $user = Auth::user();
            $oldAvatarUrl = $user->avatar;

            // Đẩy lên queue để upload lên S3
            dispatch(new UploadFileToS3(null, $fileUrl, 'images'));

            // Giả sử bạn có một phương thức để lấy URL từ S3 sau khi upload
            $s3Url = Storage::disk('s3')->url("images/{$fileName}");

            // Cập nhật avatar của người dùng
            $user->avatar = $s3Url;
            $user->save();

            // Xóa avatar cũ trên S3 nếu tồn tại
            if ($oldAvatarUrl) {
                $oldFilePath = parse_url($oldAvatarUrl, PHP_URL_PATH);
                $oldFileName = basename($oldFilePath);
                $oldS3Path = "images/{$oldFileName}";

                Storage::disk('s3')->delete($oldS3Path);
            }

            return response()->json([
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => $s3Url // Trả về URL từ S3
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }




}
