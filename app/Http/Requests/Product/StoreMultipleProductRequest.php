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
            'products.required' => 'Danh sách sản phẩm không được để trống.',
            'products.array' => 'Dữ liệu sản phẩm phải là một mảng.',
            'products.min' => 'Phải có ít nhất một sản phẩm để tạo.',
            'products.*.name.required' => 'Tên sản phẩm không được để trống.',
            'products.*.name.max' => 'Tên sản phẩm không được quá 255 ký tự.',
            'products.*.category_id.required' => 'Danh mục sản phẩm là bắt buộc.',
            'products.*.platform_id.required' => 'Nền tảng sản phẩm là bắt buộc.',
            'products.*.render_id.required' => 'Render sản phẩm là bắt buộc.',
            'products.*.color_ids.array' => 'Màu sắc phải là một mảng.',
            'products.*.material_ids.array' => 'Chất liệu phải là một mảng.',
            'products.*.tag_ids.array' => 'Tags phải là một mảng.',
            'products.*.file_url.required' => 'File nén (zip hoặc rar) là bắt buộc.',
            'products.*.file_url.regex' => 'File nén chỉ được phép là định dạng .zip hoặc .rar.',
            'products.*.image_urls.required' => 'Ít nhất một hình ảnh là bắt buộc.',
            'products.*.image_urls.array' => 'Danh sách ảnh phải là một mảng.',
            'products.*.image_urls.min' => 'Phải có ít nhất một hình ảnh.',
            'products.*.image_urls.*.required' => 'Tên tệp ảnh không được để trống.',
            'products.*.image_urls.*.regex' => 'Chỉ chấp nhận ảnh định dạng .jpg, .jpeg, .png, .gif, hoặc .webp.',
        ];
    }
}
