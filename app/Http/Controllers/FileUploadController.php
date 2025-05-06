<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ProductFiles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Jobs\UploadFileToS3;

class FileUploadController extends BaseController
{
    /**
     * Hàm chung xử lý upload file
     *
     * @param Request $request Request chứa file cần upload
     * @param string $folder Thư mục lưu trữ file
     * @param int $maxSize Kích thước tối đa của file (KB)
     * @param string $successMessage Thông báo khi upload thành công
     * @param array $options Các tùy chọn bổ sung:
     *      - uploadToS3: boolean - Có upload trực tiếp lên S3 không (mặc định: true)
     *      - s3Folder: string - Thư mục trên S3 (mặc định là $folder)
     *      - fileName: string - Tên file tùy chỉnh (nếu không sẽ tự động tạo)
     *      - fileField: string - Tên trường chứa file trong request (mặc định là 'file')
     *      - validateRules: array - Quy tắc validate bổ sung
     *      - updateUserAvatar: boolean - Có cập nhật avatar của user không (mặc định: false)
     * @return \Illuminate\Http\JsonResponse
     */
    private function uploadFile(Request $request, $folder, $maxSize, $successMessage, $options = [])
    {
        try {
            // Thiết lập các tùy chọn mặc định
            $defaultOptions = [
                'uploadToS3' => true,
                's3Folder' => $folder,
                'fileName' => null,
                'fileField' => 'file',
                'validateRules' => [],
                'updateUserAvatar' => false
            ];

            $options = array_merge($defaultOptions, $options);
            $fileField = $options['fileField'];

            // Validate file
            $validateRules = [
                $fileField => "required|file|max:$maxSize"
            ];

            // Thêm các quy tắc validate bổ sung nếu có
            if (!empty($options['validateRules'])) {
                $validateRules = array_merge($validateRules, $options['validateRules']);
            }

            $request->validate($validateRules);

            $file = $request->file($fileField);

            // Kiểm tra file có hợp lệ không
            if (!$file->isValid()) {
                Log::error("Upload thất bại: " . $file->getErrorMessage(), [
                    'filename' => $file->getClientOriginalName(),
                    'folder' => $folder
                ]);
                return response()->json(['error' => 'File upload failed', 'message' => $file->getErrorMessage()], 400);
            }

            // Xác định tên file
            $extension = $file->getClientOriginalExtension();
            $fileName = $options['fileName'] ?? (time() . "_" . preg_replace('/\s+/', '', $file->getClientOriginalName()));

            // Upload lên S3
            $s3Folder = $options['s3Folder'];
            $s3Path = "{$s3Folder}/{$fileName}";

            // Upload trực tiếp lên S3 với quyền truy cập public-read
            try {
                // Kiểm tra file có tồn tại và có thể đọc được không
                if (!$file->isValid() || !file_exists($file->getPathname())) {
                    Log::error("File không hợp lệ hoặc không tồn tại", [
                        'filename' => $fileName,
                        'path' => $file->getPathname(),
                        'is_valid' => $file->isValid(),
                        'exists' => file_exists($file->getPathname())
                    ]);
                    return response()->json(['error' => 'Invalid file or file does not exist'], 400);
                }

                // Đọc nội dung file
                $fileContents = file_get_contents($file->getPathname());
                if ($fileContents === false) {
                    Log::error("Không thể đọc nội dung file", [
                        'filename' => $fileName,
                        'path' => $file->getPathname()
                    ]);
                    return response()->json(['error' => 'Cannot read file contents'], 500);
                }

                // Upload lên S3 không sử dụng ACL hoặc visibility
                $options = [
                    'ContentType' => $file->getMimeType(),
                ];

                $uploaded = Storage::disk('s3')->put(
                    $s3Path,
                    $fileContents,
                    $options
                );

                if (!$uploaded) {
                    Log::error("Upload lên S3 thất bại", [
                        'filename' => $fileName,
                        'folder' => $s3Folder
                    ]);
                    return response()->json(['error' => 'Failed to upload to S3'], 500);
                }
            } catch (\Exception $e) {
                Log::error("Exception khi upload lên S3: " . $e->getMessage(), [
                    'filename' => $fileName,
                    'folder' => $s3Folder,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Failed to upload to S3', 'message' => $e->getMessage()], 500);
            }

            // Lấy URL của file từ S3
            $fileUrl = Storage::disk('s3')->url($s3Path);

            // Cập nhật avatar trong DB nếu cần
            if (isset($options['updateUserAvatar']) && $options['updateUserAvatar']) {
                $user = Auth::user();

                // Kiểm tra xem user có tồn tại không
                if (!$user) {
                    Log::error('User not found in Auth::user()');

                    // Thử lấy user từ Auth::id()
                    $userId = Auth::id();
                    Log::info('Auth::id() result', ['user_id' => $userId]);

                    if ($userId) {
                        // Lấy user từ ID
                        $user = User::find($userId);

                        if (!$user) {
                            Log::error('User not found with ID: ' . $userId);
                            return response()->json(['error' => 'User not found'], 404);
                        }
                    } else {
                        Log::error('Auth::id() returned null');
                        return response()->json(['error' => 'User not authenticated'], 401);
                    }
                }

                // Log thông tin user và tên file
                Log::info('Updating user avatar', [
                    'user_id' => $user->id,
                    'new_avatar' => $fileName
                ]);

                $user->avatar = $fileName;
                $result = $user->save();
                
                // Thử cập nhật bằng cách khác nếu không thành công
                if (!$result) {
                    Log::warning('Failed to update avatar with save(), trying update()');
                    $updateResult = User::where('id', $user->id)->update(['avatar' => $fileName]);
                    Log::info('Update result with update()', ['result' => $updateResult]);
                }

                // Log kết quả lưu
                Log::info('User avatar update result', [
                    'user_id' => $user->id,
                    'result' => $result,
                    'new_avatar' => $user->avatar
                ]);
            }

            return response()->json([
                'message' => $successMessage,
                'file_url' => $fileUrl,
                'file_name' => $fileName
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation lỗi khi upload: " . $e->getMessage(), [
                'folder' => $folder
            ]);
            return response()->json(['error' => 'File upload failed', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error("Lỗi không xác định khi upload: " . $e->getMessage(), [
                'folder' => $folder,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Hàm chuẩn bị file từ request
     *
     * @param Request $request Request chứa file cần upload
     * @return array|null Thông tin về file hoặc null nếu không có file
     */
    private function prepareFileFromRequest(Request $request)
    {
        try {
            // Lấy tất cả các file được gửi lên
            $allFiles = $request->allFiles();

            // Debug thông tin request
            Log::info('Request information', [
                'all_files' => $allFiles,
                'has_file' => $request->hasFile('file'),
                'has_avatar' => $request->hasFile('avatar'),
                'content_type' => $request->header('Content-Type'),
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'request_all' => $request->all()
            ]);

            // Nếu không có file nào được gửi lên
            if (empty($allFiles)) {
                Log::warning('No files uploaded in request');
                return null;
            }

            // Lấy file đầu tiên từ request, bất kể tên trường là gì
            $fileField = array_key_first($allFiles);
            $file = $allFiles[$fileField];

            Log::info('Selected file field', [
                'field' => $fileField,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime' => $file->getMimeType(),
                'file_extension' => $file->getClientOriginalExtension(),
                'file_path' => $file->getPathname()
            ]);

            return [
                'file' => $file,
                'fileField' => $fileField,
                'extension' => $file->getClientOriginalExtension()
            ];
        } catch (\Exception $e) {
            Log::error('Error in prepareFileFromRequest: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * API Upload hình ảnh tạm thời
     */
    public function uploadTempImage(Request $request)
    {
        $fileInfo = $this->prepareFileFromRequest($request);

        if (!$fileInfo) {
            return response()->json(['error' => 'File upload failed', 'message' => 'No file uploaded or invalid file'], 400);
        }

        return $this->uploadFile($request, 'images', 10240, 'Image uploaded successfully', [
            'fileField' => $fileInfo['fileField'],
            'validateRules' => [
                $fileInfo['fileField'] => 'required|image|max:10240' // Cho phép tất cả các định dạng hình ảnh, max 10MB
            ],
            'updateUserAvatar' => false
        ]);
    }

    /**
     * API Upload file model tạm thời
     */
    public function uploadTempModel(Request $request)
    {
        $fileInfo = $this->prepareFileFromRequest($request);

        if (!$fileInfo) {
            return response()->json(['error' => 'File upload failed', 'message' => 'No file uploaded or invalid file'], 400);
        }

        return $this->uploadFile($request, 'models', 102400, 'Model uploaded successfully', [
            'fileField' => $fileInfo['fileField'],
            'validateRules' => [
                $fileInfo['fileField'] => 'required|file|max:102400' // Cho phép tất cả các loại file, max 100MB
            ],
            'updateUserAvatar' => false
        ]);
    }

    /**
     * API Upload file chung lên S3
     */
    public function uploadFileToS3(Request $request)
    {
        // Lấy thông tin từ request
        $folder = $request->input('folder', 'uploads');
        $maxSize = $request->input('max_size', 10240); // Mặc định 10MB
        $fileTypes = $request->input('file_types', 'file'); // Mặc định cho phép tất cả các loại file

        // Xác thực thông tin
        if ($maxSize > 102400) { // Giới hạn tối đa 100MB
            $maxSize = 102400;
        }

        $fileInfo = $this->prepareFileFromRequest($request);

        if (!$fileInfo) {
            return response()->json(['error' => 'File upload failed', 'message' => 'No file uploaded or invalid file'], 400);
        }

        return $this->uploadFile($request, $folder, $maxSize, 'File uploaded successfully', [
            'fileField' => $fileInfo['fileField'],
            'validateRules' => [
                $fileInfo['fileField'] => 'required|' . $fileTypes . '|max:' . $maxSize
            ],
            'updateUserAvatar' => false
        ]);
    }

    /**
     * API Upload avatar trực tiếp lên S3
     */
    public function uploadAvatar(Request $request)
    {
        $userId = Auth::id();

        // Debug thông tin request và auth
        Log::info('Upload avatar request', [
            'all_files' => $request->allFiles(),
            'has_file' => $request->hasFile('file'),
            'has_avatar' => $request->hasFile('avatar'),
            'content_type' => $request->header('Content-Type'),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'request_all' => $request->all(),
            'auth_id' => $userId,
            'auth_user' => Auth::user() ? Auth::user()->id : null,
            'auth_check' => Auth::check(),
            'bearer_token' => $request->bearerToken()
        ]);

        // Kiểm tra xem có file nào được upload không
        if (!$request->hasFile('avatar') && !$request->hasFile('file')) {
            Log::error('No avatar file uploaded');
            return response()->json(['error' => 'File upload failed', 'message' => 'No avatar file uploaded'], 400);
        }

        // Xác định trường chứa file
        $fileField = $request->hasFile('avatar') ? 'avatar' : 'file';
        $file = $request->file($fileField);

        // Xác định extension
        $fileExtension = $file->getClientOriginalExtension() ?: 'jpg';

        Log::info('Avatar file information', [
            'field' => $fileField,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_mime' => $file->getMimeType(),
            'file_extension' => $fileExtension,
            'file_path' => $file->getPathname()
        ]);
        
        // Thử cập nhật avatar trực tiếp
        try {
            // Lấy user từ Auth::user() hoặc từ database
            $user = Auth::user();
            if (!$user && $userId) {
                $user = User::find($userId);
            }
            
            if ($user) {
                // Tạo tên file
                $fileName = "{$userId}.{$fileExtension}";
                
                // Log thông tin trước khi cập nhật
                Log::info('Updating user avatar directly', [
                    'user_id' => $user->id,
                    'old_avatar' => $user->avatar,
                    'new_avatar' => $fileName
                ]);
                
                // Cập nhật avatar
                $user->avatar = $fileName;
                $result = $user->save();
                
                // Thử cập nhật bằng cách khác nếu không thành công
                if (!$result) {
                    Log::warning('Failed to update avatar with save(), trying update()');
                    $updateResult = User::where('id', $user->id)->update(['avatar' => $fileName]);
                    Log::info('Update result with update()', ['result' => $updateResult]);
                }
                
                // Log kết quả
                Log::info('Direct update result', [
                    'user_id' => $user->id,
                    'result' => $result,
                    'new_avatar' => $user->avatar
                ]);
            } else {
                Log::error('User not found for direct update');
            }
        } catch (\Exception $e) {
            Log::error('Error updating avatar directly: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $this->uploadFile($request, 'avatars', 5120, 'Avatar uploaded successfully', [
            'fileField' => $fileField,
            'fileName' => "{$userId}.{$fileExtension}",
            'validateRules' => [
                $fileField => 'required|image|max:5120' // Cho phép tất cả các định dạng hình ảnh, max 5MB
            ],
            'updateUserAvatar' => true
        ]);
    }
}
