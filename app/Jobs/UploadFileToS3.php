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
        $localPath = str_replace(url('/storage'), 'public', parse_url($this->fileUrl, PHP_URL_PATH));

        if (Storage::disk('public')->exists($localPath)) {
            $s3Path = "{$this->folder}/" . basename($this->fileUrl);
            Storage::disk('s3')->put($s3Path, Storage::disk('public')->get($localPath));
            Storage::disk('public')->delete($localPath);

            // 🛑 Cập nhật đường dẫn chính xác trên S3 trong DB
            File::where('id', $this->fileId)->update([
                'file_path' => Storage::disk('s3')->url($s3Path)
            ]);
        }
    }
}
