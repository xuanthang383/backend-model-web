<?php

namespace App\Http\Controllers;

use App\DTO\Product\CreateDTO;
use App\DTO\Product\CreateMultipleDTO;
use App\DTO\Product\UpdateDTO;
use App\Http\Requests\Product\StoreMultipleProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Jobs\UploadFileToS3;
use App\Models\Category;
use App\Models\Color;
use App\Models\FavoriteProduct;
use App\Models\File;
use App\Models\FileDownload;
use App\Models\HideProduct;
use App\Models\LibraryProduct;
use App\Models\Material;
use App\Models\Platform;
use App\Models\Product;
use App\Models\ProductFiles;
use App\Models\Render;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// để bắt lỗi token hết hạn

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        $userId = (int)$this->getUserIdFromToken($request);

        $query = Product::query()->with(['files' => function ($query) {
            $query->wherePivot('is_thumbnail', true);
        }]);

        if ($userId) {
            $query->with(["libraries" => function ($query) use ($userId) {
                $query->wherePivot('libraries.user_id', $userId);
            }]);
        }

        //Chỉ lấy ra các bản ghi publish
        $query->where('status', '=', Product::STATUS_APPROVED);

        // Lọc theo tên sản phẩm (nếu có)
        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }
        // Lọc theo category_id (nếu có)
        if ($request->has('category_ids')) {
            $query->whereIn('category_id', $request->query('category_ids'));
        }

        if ($request->has('color_ids')) {
            $query->whereHas('colors', function ($q) use ($request) {
                $q->whereIn('colors.id', $request->query('color_ids'));
            });
        }

        if (!collect(['is_saved', 'is_hidden', 'is_favorite'])->some(fn($param) => $request->boolean($param))) {
            // Nếu tất cả đều FALSE
//dd(111);
            $query->whereDoesntHave('favorites', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

            $query->whereDoesntHave('hides', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

            $query->whereNotIn('id', function ($subQuery) use ($userId) {
                $subQuery->select('product_id')
                    ->from('library_product')
                    ->whereIn('library_id', function ($subQuery2) use ($userId) {
                        $subQuery2->select('id')
                            ->from('libraries')
                            ->where('user_id', $userId);
                    });
            });

        } else {
            // Nếu yêu cầu danh sách yêu thích
            if ($request->boolean('is_favorite')) {
                $query->whereHas('favorites', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            } else if ($request->boolean('is_hidden')) {
                // Nếu is_hide=true -> Lấy sản phẩm mà user đã ẩn
                $query->whereHas('hides', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            } else if ($request->boolean('is_saved')) {
                $query->whereIn('id', function ($subQuery) use ($userId) {
                    $subQuery->select('product_id')
                        ->from('library_product')
                        ->whereIn('library_id', function ($subQuery2) use ($userId) {
                            $subQuery2->select('id')
                                ->from('libraries')
                                ->where('user_id', $userId);
                        });
                });
            }
        }

        return $this->paginateResponse($query, $request, "Success", function ($product) use ($userId) {
            // Lấy ảnh thumbnail (nếu có)
            $thumbnailFile = $product->files->first();
            $product->thumbnail = $thumbnailFile ? $thumbnailFile->file_path : null;
            // Kiểm tra xem sản phẩm có trong danh sách yêu thích của user không
            $product->is_favorite = $userId && FavoriteProduct::where('user_id', $userId)->where('product_id', $product->id)->exists();
            unset($product->files); // Xóa danh sách files để response gọn hơn
            return $product;
        });
    }

    public function productOfUser(Request $request)
    {
        $userId = (int)$this->getUserIdFromToken($request);
        $userId = 3;

        $query = Product::query()
            ->where('user_id', $userId)
            ->with(["libraries" => function ($query) use ($userId) {
                $query->wherePivot('libraries.user_id', $userId);
            }])
            ->orderBy("created_at", "desc");

        return $this->paginateResponse($query, request(), "Success", function ($product) use ($userId) {
            $product->thumbnail = $product->imageFiles->first(function ($file) {
                return $file->pivot->is_thumbnail == 1;
            });
            $product->is_favorite = $userId && FavoriteProduct::where('user_id', $userId)->where('product_id', $product->id)->exists();
            $product->makeHidden("imageFiles", "pivot");
            $product->thumbnail->makeHidden("pivot");

            return $product;
        });
    }

    public function show(Request $request, $id)
    {
        $userId = (int)$this->getUserIdFromToken($request);

        $product = Product::with(['category', 'tags', 'files', 'platform', 'render'])->find($id);
        if (!$product) {
            return response()->json(['r' => 0, 'msg' => 'Product not found'], 404);
        }

        // Nếu user đăng nhập, kiểm tra sản phẩm có trong danh sách yêu thích không
        $isFavorite = $userId && FavoriteProduct::where('user_id', $userId)->where('product_id', $id)->exists();
        // Nếu user đăng nhập, kiểm tra sản phẩm có trong danh sách hide không
        $isHidden = $userId && HideProduct::where('user_id', $userId)->where('product_id', $id)->exists();

        $libraries = null;
        if ($userId) {
            $libraries = LibraryProduct::where('product_id', $id)
                ->with(['library' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }])
                ->get()
                ->reduce(function ($carry, $libraryProduct) {
                    if ($libraryProduct->library) {
                        $carry[] = [
                            'id' => $libraryProduct->library->id,
                            'name' => $libraryProduct->library->name,
                            'description' => $libraryProduct->library->description,
                        ];
                    }
                    return $carry;
                }, []);
        }

        // Lấy tất cả `file_path` từ `product_files` và `files`
        $allFiles = File::whereIn('id', ProductFiles::where('product_id', $id)
            ->pluck('file_id'))
            ->pluck('file_path')
            ->map(function ($filePath) {
                return $filePath;
            });

        // Lọc chỉ lấy những file có chứa "images/"
        $imageFiles = $allFiles->filter(function ($filePath) {
            return str_contains($filePath, 'images/');
        })->values(); // Reset index của array

        // Lấy thumbnail từ ảnh có `image = true` trong `product_files`
        $thumbnail = ProductFiles::where('product_id', $id)
            ->where('is_thumbnail', true)
            ->first();

        $thumbnailPath = $thumbnail ? File::find($thumbnail->file_id)->file_path : null;

        return response()->json(['r' => 1,
            'msg' => 'Product retrieved successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'is_ads' => $product->is_ads ?? 0,
                'is_favorite' => $isFavorite,
                'is_hide' => $isHidden,
                'description' => $product->description,
                'category_id' => $product->category_id,
                'platform' => $product->platform,
                'render' => $product->render,
                'thumbnail' => $thumbnailPath, // Ảnh được chọn làm thumbnail
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'listImageSrc' => $imageFiles->toArray(), // Danh sách ảnh
                'category' => $product->category,
                'tags' => $product->tags,
                'files' => $product->files,
                'colors' => $product->colors ?? [],
                'materials' => $product->materials ?? [],
                'libraries' => $libraries
            ]]);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $validatedData = new CreateDTO($request->validated());

            $product = new Product();
            $productResp = $product->createProduct($validatedData);

            return $this->successResponse(
                ['product' => $productResp],
                'Product created successfully with colors, materials, and tags',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /*
     * Check phân quyền
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $uploadedBy = Auth::id();

            $validateData = new UpdateDTO($request->validated());
            $product = Product::findOrFail($id);

            if ($product["user_id"] !== $uploadedBy) {
                return $this->errorResponse('Unauthorized action.', 403);
            }

            $product->update([
                'name' => $validateData->name,
                'category_id' => $validateData->category_id ?? null,
                'platform_id' => $validateData->platform_id ?? null,
                'render_id' => $validateData->render_id ?? null,
                //                'file_url' => $validateData->file_url ?? null,
            ]);

            $product->colors()->sync($validateData->color_ids ?? []);
            $product->materials()->sync($validateData->material_ids ?? []);
            $product->tags()->sync($validateData->tag_ids ?? []);

            /*
             * Cập nhật file model
             */
            $fileName = basename($validateData->file_url);

            // 🛑 Tìm file cũ của sản phẩm
            $oldProductFile = ProductFiles::where('product_id', $product->id)
                ->where('is_model', true)
                ->first();
            if (!$oldProductFile || $validateData->file_url != $oldProductFile->file->file_path) {
                if ($oldProductFile) {
                    // 🛑 Xóa file cũ khỏi DB
                    // File::where('id', $oldProductFile->file_id)->delete();
                    // 🛑 Xóa product file mapping
                    $oldProductFile->delete();
                }

                // 🛑 Tạo file mới trong DB trước khi upload lên S3
                $fileRecord = File::create([
                    'file_name' => $fileName,
                    'file_path' => config('app.file_path') . File::MODEL_FILE_PATH . $fileName,
                    'uploaded_by' => $uploadedBy
                ]);

                // 🔥 Đẩy lên queue để upload lên S3
                dispatch(new UploadFileToS3($fileRecord->id, $validateData->file_url, 'models'));

                // 🛑 Lưu file mới vào bảng product_files
                ProductFiles::create([
                    'file_id' => $fileRecord->id,
                    'product_id' => $product->id,
                    'is_model' => true
                ]);
            }

            if (!empty($validateData->image_urls) && is_array($validateData->image_urls)) {
                $imageUrls = array_values($validateData->image_urls);
                $newThumbnail = $imageUrls[0] ?? null;

                // 🛑 Lấy danh sách ảnh cũ của sản phẩm
                $oldProductImages = ProductFiles::where('product_id', $product->id)
                    ->whereNull('is_model')->orWhere('is_model', false)
                    ->get();

                $oldThumbnail = null;
                $existingImageUrls = $oldProductImages->map(function ($image) use (&$oldThumbnail) {
                    if ($image->is_thumbnail) {
                        $oldThumbnail = $image;
                    }
                    return $image->file->file_path;
                })->toArray();

                $newThumbnailFileId = null; // Lưu ID ảnh mới nếu cần thay đổi thumbnail
                // 🛑 Duyệt qua danh sách ảnh mới
                foreach ($imageUrls as $key => $imageUrl) {
                    $imgName = basename($imageUrl);
                    $filePath = config("app.file_path") . File::IMAGE_FILE_PATH . $imgName;

                    // 🛑 Kiểm tra xem ảnh có phải là ảnh mới hay không
                    if (!in_array($filePath, $existingImageUrls)) {
                        // 🛑 Nếu là ảnh mới, tạo bản ghi mới
                        $imageRecord = File::create([
                            'file_name' => $imgName,
                            'file_path' => $filePath,
                            'uploaded_by' => $uploadedBy
                        ]);

                        // 🔥 Đẩy lên queue để upload lên S3
                        //                        dispatch(new UploadFileToS3($imageRecord->id, $imageUrl, 'images'));

                        // 🛑 Lưu file mới vào bảng product_files
                        $productFile = ProductFiles::create([
                            'file_id' => $imageRecord->id,
                            'product_id' => $product->id,
                            'is_thumbnail' => false, // Đặt false, sau này mới cập nhật thumbnail nếu cần
                        ]);

                        // Lưu lại file_id của ảnh đầu tiên để cập nhật thumbnail nếu cần
                        if ($key == 0) {
                            $newThumbnailFileId = $productFile->file_id;
                        }
                    }
                }

                // 🛑 Cập nhật ảnh thumbnail nếu cần
                if ($newThumbnailFileId && $oldThumbnail?->file->file_path !== $newThumbnail) {
                    // Nếu thumbnail cũ khác với ảnh đầu tiên mới, cập nhật lại thumbnail
                    ProductFiles::where('product_id', $product->id)
                        ->where('is_thumbnail', true)
                        ->update(['is_thumbnail' => false]);
                    ProductFiles::where('file_id', $newThumbnailFileId)->update(['is_thumbnail' => true]);
                }

                // 🛑 Xóa ảnh cũ nếu không còn tồn tại trong danh sách ảnh mới
                foreach ($oldProductImages as $oldImage) {
                    if (!in_array($oldImage->file->file_path, $imageUrls)) {
                        ProductFiles::where([
                            'product_id' => $oldImage->product_id,
                            'file_id' => $oldImage->file_id
                        ])->delete();
                    }
                }
            }

            return $this->successResponse(
                ['product' => $product],
                'Product updated successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function storeMultiple(StoreMultipleProductRequest $request)
    {
        try {
            $products = [];

            foreach ($request->validated()['products'] as $productData) {
                $validatedData = new CreateMultipleDTO($productData);

                // 🛑 Xử lý category_id, platform_id, render_id từ tên
                $category = Category::where('name', $validatedData->category)->first();
                $platform = Platform::where('name', $validatedData->platform)->first();
                $render = Render::where('name', $validatedData->render)->first();

                if (!$category || !$platform || !$render) {
                    return $this->errorResponse('Invalid category, platform, or render name', 400);
                }

                // 🛑 Xử lý color_ids từ tên
                if (!empty($validatedData->color_ids)) {
                    $colorIds = Color::whereIn('name', $validatedData->color_ids)->pluck('id', 'name')->toArray();

                    // Kiểm tra nếu có tên màu không tìm thấy trong DB
                    $notFoundColors = array_diff($validatedData->color_ids, array_keys($colorIds));
                    if (!empty($notFoundColors)) {
                        return $this->errorResponse('Invalid colors: ' . implode(', ', $notFoundColors), 400);
                    }
                }

                // 🛑 Xử lý material_ids từ tên
                if (!empty($validatedData->material_ids)) {
                    $materialIds = Material::whereIn('name', $validatedData->material_ids)->pluck('id', 'name')->toArray();

                    // Kiểm tra nếu có chất liệu không tìm thấy trong DB
                    $notFoundMaterials = array_diff($validatedData->material_ids, array_keys($materialIds));
                    if (!empty($notFoundMaterials)) {
                        return $this->errorResponse('Invalid materials: ' . implode(', ', $notFoundMaterials), 400);
                    }
                }

                $createDto = new CreateDTO();
                $createDto->name = $validatedData->name;
                $createDto->category_id = $category->id;
                $createDto->platform_id = $platform->id;
                $createDto->render_id = $render->id;
                $createDto->color_ids = $colorIds ?? [];
                $createDto->material_ids = $materialIds ?? [];
                $createDto->tag_ids = $validatedData->tag_ids ?? [];
                $createDto->file_url = $validatedData->file_url;
                $createDto->image_urls = $validatedData->image_urls;

                $product = new Product();
                $createProductRes = $product->createProduct($createDto);
                $products[] = $createProductRes;
            }

            return $this->successResponse(
                ['products' => $products],
                'Products created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function toggleHidden($id)
    {
        try {
            $product = Product::findOrFail($id);

            if ($product->user_id !== Auth::id()) {
                return $this->errorResponse('Unauthorized action.', 403);
            }

            $product->public = !$product->public;
            $product->save();

            return $this->successResponse(
                ['product' => $product],
                'Product visibility toggled successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function downloadModelFile(Request $request)
    {
        $token = $request->input('token');
        $publicKeyClient = $request->input('public_key');
        // public ở dạng
        /*
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwq/u3LUUK9ffxTXH5nK6
        ZW7q5incdc/6tphJJo2CynjahJioxhsH1RD3y/aUqS9ouBsx9py8Y+tGTRXhlY66
        EK+kmNOw86R8g9WDqz4dc66g2gzSENw/h5eUlRQhGuu8AF+VvHg0cJxc5wnl363v
        vIajifzoodnBZCd+Fec+fAndR137QE4KzQYeg6cFNhvTC1XMP395zGqTGbiWUU/n
        DFF0/2Pv8vC7dPN3NKn9nUQ/g9wX6v6IP/FlEbOLRTPT3f9srjBoObHd/QwVDx8i
        1r9uFH1KqiftR3+7ReOh07mkpnycwVbN56Z79Amj0Z4gQgjw+6MatI5Qa3s0buEb
        +QIDAQAB
        -----END PUBLIC KEY-----
        */

        try {
            $publicKey = file_get_contents(storage_path('public.pem'));
            $payload = JWT::decode($token, new Key($publicKey, 'RS256'));

            $uuidToken = $payload->token;

            $fileDownload = FileDownload::where('token', $uuidToken)->first();
            if (!$fileDownload) {
                return $this->errorResponse('File not found', 404);
            }

            if ($payload->pub_key_hash !== hash('sha256', $publicKeyClient)) {
                return $this->errorResponse('Invalid public key.', 403);
            }

            if ($payload->exp < time()) {
                return $this->errorResponse('Token expired.', 403);
            }

            if ($fileDownload->request_ip !== $request->ip()) {
                return $this->errorResponse('Invalid request IP.', 403);
            }

            if ($fileDownload->used) {
                return $this->errorResponse('Link has been used.', 403);
            }

            if ($fileDownload->delay_until > time()) {
                return $this->errorResponse('File is not ready yet.', 403);
            }

            $fileDownload->used = true;
            $fileDownload->save();

            $file = File::find($fileDownload->file_id);
            if (!$file) {
                return $this->errorResponse('File not found', 404);
            }

            $fileKeyS3 = str_replace(config("app.file_path"), '', $file->file_path);
            $signedUrl = Storage::disk('s3')->temporaryUrl($fileKeyS3, now()->addSeconds(20));
            if (!$signedUrl) {
                return $this->errorResponse('Failed to generate download link.', 500);
            }

            // Generate AES key & IV
            $aesKey = random_bytes(32);
            $iv = random_bytes(12);

            $tag = '';
            $ciphertext = openssl_encrypt($signedUrl, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);

            // Mã hóa AES key bằng RSA public key từ client
            openssl_public_encrypt($aesKey, $encryptedAesKey, $publicKeyClient, OPENSSL_PKCS1_OAEP_PADDING);

            return $this->successResponse(
                [
                    'iv' => base64_encode($iv),
                    'tag' => base64_encode($tag),
                    'ciphertext' => base64_encode($ciphertext),
                    'encrypted_key' => base64_encode($encryptedAesKey)
                ],
                'File download link generated successfully'
            );
        } catch (ExpiredException|Exception) {
            return $this->errorResponse('Invalid or expired token.', 403);
        }
    }

    public function requestDownload(Request $request)
    {
        $id = $request->input('id');
        $publicKeyClient = $request->input('public_key');

        $product = Product::find($id);
        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $productModel = ProductFiles::where('product_id', $id)
            ->where('is_model', 1)
            ->first();
        if (!$productModel) {
            return $this->errorResponse('Model file not found', 404);
        }

        // Tạo file_key ngẫu nhiên
        $uuidToken = Str::random(64);

        // Hash public key để nhúng vào token
        $pubKeyFingerprint = hash('sha256', $publicKeyClient);

        $payload = [
            'token' => $uuidToken,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(10)->timestamp,  // JWT hết hạn sau 10 phút
            'nbf' => now()->timestamp,
            "pub_key_hash" => $pubKeyFingerprint,
        ];

        $privateKey = file_get_contents(storage_path('private.pem'));
        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        FileDownload::create([
            'file_id' => $productModel->file_id,
            'token' => $uuidToken,
            'used' => false,
            'delay_until' => now()->addSeconds(1)->timestamp,
            'request_ip' => $request->ip()
        ]);

        return $this->successResponse(
            ['token' => $jwt],
            'Token generated successfully'
        );
    }

}
