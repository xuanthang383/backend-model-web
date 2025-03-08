<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMultipleProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules()
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.category_id' => ['required', 'string', 'max:255'], // Truyền tên
            'products.*.platform_id' => ['required', 'string', 'max:255'], // Truyền tên
            'products.*.render_id' => ['required', 'string', 'max:255'], // Truyền tên
            'products.*.color_ids' => ['nullable', 'array'],
            'products.*.color_ids.*' => ['string', 'max:255'], // Truyền tên màu
            'products.*.material_ids' => ['nullable', 'array'],
            'products.*.material_ids.*' => ['string', 'max:255'], // Truyền tên vật liệu
            'products.*.tag_ids' => ['nullable', 'array'],
            'products.*.tag_ids.*' => ['string', 'max:255'], // Truyền tên tag, tạo mới nếu không có
            'products.*.file_url' => ['required', 'string', 'regex:/\.(zip|rar)$/i'], // Bắt buộc và chỉ chấp nhận .zip, .rar
            'products.*.image_urls' => ['required', 'array', 'min:1'], // Bắt buộc có ít nhất 1 ảnh
            'products.*.image_urls.*' => ['required', 'string', 'regex:/\.(jpg|jpeg|png|gif|webp)$/i'], // Chỉ chấp nhận ảnh
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'The product list cannot be empty.',
            'products.array' => 'Product data must be an array.',
            'products.min' => 'At least one product is required to create.',
            'products.*.name.required' => 'Product name is required.',
            'products.*.name.max' => 'Product name cannot exceed 255 characters.',
            'products.*.category_id.required' => 'Product category is required.',
            'products.*.platform_id.required' => 'Product platform is required.',
            'products.*.render_id.required' => 'Product render is required.',
            'products.*.color_ids.array' => 'Colors must be an array.',
            'products.*.material_ids.array' => 'Materials must be an array.',
            'products.*.tag_ids.array' => 'Tags must be an array.',
            'products.*.file_url.required' => 'A compressed file (zip or rar) is required.',
            'products.*.file_url.regex' => 'The compressed file must be in .zip or .rar format.',
            'products.*.image_urls.required' => 'At least one image is required.',
            'products.*.image_urls.array' => 'The image list must be an array.',
            'products.*.image_urls.min' => 'At least one image is required.',
            'products.*.image_urls.*.required' => 'Image file name cannot be empty.',
            'products.*.image_urls.*.regex' => 'Only .jpg, .jpeg, .png, .gif, or .webp image formats are allowed.',
        ];
    }
}
