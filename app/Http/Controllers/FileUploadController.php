<?php

namespace App\Http\Controllers;

use Aws\S3\Exception\S3Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function upload3DModel(Request $request)
{
    // try {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $request->validate([
            'file' => 'required|file|max:102400',
        ]);

        $file = $request->file('file');
        if (!$file->isValid()) {
            return response()->json(['error' => 'Invalid file'], 400);
        }

        $fileName = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = 'models/' . $fileName;
        try {
        // ✅ Thử upload file lên S3 và bắt lỗi chi tiết
        $upload = Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');

        if (!$upload) {
            throw new \Exception("Upload failed: Unable to upload file to S3.");
        }
    } catch (S3Exception $e) {
        return response()->json([
            'error' => 'AWS S3 Exception: ' . $e->getAwsErrorMessage(),
            'code' => $e->getAwsErrorCode(),
            'type' => $e->getAwsErrorType(),
            'request_id' => $e->getAwsRequestId(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }

        return response()->json([
            'message' => 'File uploaded successfully!',
            'file_url' => Storage::disk('s3')->url($filePath),
        ]);
    // } catch (\Throwable $e) {
    //     return response()->json([
    //         'error' => 'AWS Exception: ' . $e->getMessage(),
    //         'trace' => $e->getTraceAsString(),
    //     ], 500);
    // }
}


public function postUpload(Request $request)
    {
        dd(file_get_contents($request->file('file')), 'images/' . $request->file->getClientOriginalName());
        // dd(Storage::disk('s3')->get('images/robots.txt'));
        $path = Storage::disk('s3')->put('images/' . $request->file->getClientOriginalName(), $request->file('file'), 'public');

        dd($path);
    }

}
