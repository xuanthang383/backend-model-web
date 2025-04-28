<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
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

        // Sử dụng tên file từ $this->fileId nếu có, hoặc từ URL
        $fileName = $this->fileId ? "{$this->fileId}.{$this->extension}" : basename($this->fileUrl);
        $s3Path = "{$this->folder}/" . $fileName;
        $uploaded = Storage::disk('s3')->put($s3Path, $fileContent);

        if ($uploaded) {
            Storage::disk('public')->delete($filePath);
            return true;
        } else {
            return false;
        }
    }
}

