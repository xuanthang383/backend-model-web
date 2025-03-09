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

    public function __construct($fileId, $fileUrl, $folder)
    {
        $this->fileId = $fileId;
        $this->fileUrl = $fileUrl;
        $this->folder = $folder;
    }

    public function handle()
    {
        $fileUrl = request()->get('file_url');
        $parsedUrl = parse_url(url: $this->fileUrl);
        $filePath = $parsedUrl['path'];
        $filePath = preg_replace('/^\/storage/', '', $filePath);

        $fileContent = Storage::disk('public')->get($filePath);

        if (empty($fileContent)) {
            return false;
        }

        $s3Path = "{$this->folder}/" . basename(path: $this->fileUrl);
        $uploaded = Storage::disk('s3')->put($s3Path, $fileContent);

        if ($uploaded) {
            File::delete($fileContent); // Xóa file tạm khi upload thành công
            return true;
        } else {
            return false;
        }
    }
}
