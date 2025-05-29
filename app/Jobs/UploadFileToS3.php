<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\File;

class UploadFileToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileId;
    protected $fileUrl;
    protected $folder;
    protected $extension; // Thêm biến này

    public function __construct($fileId, $fileUrl, $folder, $extension)
    {
        $this->fileId = $fileId;
        $this->fileUrl = $fileUrl;
        $this->folder = $folder;
        $this->extension = $extension; // Khởi tạo biến này
    }

    public function handle()
    {
        $parsedUrl = parse_url($this->fileUrl);
        $filePath = $parsedUrl['path'];
        $filePath = preg_replace('/^\/storage/', '', $filePath);

        $fileContent = Storage::disk('public')->get($filePath);

        if (empty($fileContent)) {
            return false;
        }

        // Sử dụng tên file gốc từ URL
        $fileName = basename($this->fileUrl);
        $s3Path = "{$this->folder}/" . $fileName;
        $uploaded = Storage::disk('s3')->put($s3Path, $fileContent);

        if ($uploaded) {
            // Delete the local file
            Storage::disk('public')->delete($filePath);
            
            // Update the file path in the database to point to the S3 URL
            if ($this->fileId) {
                $s3Url = Storage::disk('s3')->url($s3Path);
                Log::info('Updating file path in database', [
                    'fileId' => $this->fileId,
                    'originalPath' => $this->fileUrl,
                    's3Path' => $s3Path,
                    's3Url' => $s3Url
                ]);
                
                \App\Models\File::where('id', $this->fileId)->update([
                    'file_path' => $s3Url
                ]);
            }
            
            return true;
        } else {
            return false;
        }
    }
}

