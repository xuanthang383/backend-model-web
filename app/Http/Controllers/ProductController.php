<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|int',
            'file_url' => ['required', 'url', function ($attribute, $value, $fail) {
                $path = str_replace(url('/storage'), 'public', $value); // Chuyển đổi URL về đường dẫn trong storage
        
                // Chuyển URL sang đường dẫn tương đối (storage/app/public/)
                $relativePath = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));
        
                if (!Storage::disk('public')->exists($relativePath)) {
                    $fail("The file does not exist in temporary storage.");
                }
            }],
            'image_urls' => 'nullable|array',
            'image_urls.*' => ['url', function ($attribute, $value, $fail) {
                $path = str_replace(url('/storage'), 'public', $value);
        
                // Chuyển URL sang đường dẫn tương đối (storage/app/public/)
                $relativePath = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));
        
                if (!Storage::disk('public')->exists($relativePath)) {
                    $fail("One or more image URLs do not exist in temporary storage.");
                }
            }]
        ]);

        // Tạo product
        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category,
            'description'=>'abc'
        ]);
        dd($product);
        // // Lưu file model vào bảng `product_files`
        // ProductFile::create([
        //     'product_id' => $product->id,
        //     'file_name' => basename($request->file_url),
        //     'file_path' => $request->file_url,
        //     'type' => 'model' // Phân loại đây là file model
        // ]);

        // // Lưu danh sách ảnh vào bảng `product_files`
        // $productImages = [];
        // if (!empty($request->image_urls)) {
        //     foreach ($request->image_urls as $imageUrl) {
        //         $productImages[] = ProductFile::create([
        //             'product_id' => $product->id,
        //             'file_name' => basename($imageUrl),
        //             'file_path' => $imageUrl,
        //             'type' => 'image' // Phân loại đây là ảnh
        //         ]);
        //     }
        // }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
            'file_model' => [
                'file_name' => basename($request->file_url),
                'file_path' => $request->file_url
            ],
            'images' => $productImages
        ]);
    }
}
