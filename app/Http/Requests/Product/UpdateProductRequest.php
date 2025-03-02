<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('products', 'name')->ignore(1),
            ],
            'category_id' => 'required|integer|exists:categories,id',
            'platform_id' => 'nullable|integer|exists:platforms,id',
            'render_id' => 'nullable|integer|exists:renders,id',
            'file_url' => 'required|url',
            'image_urls' => 'required|array',
            'image_urls.*' => ['url', function ($attribute, $value, $fail) {
                if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $value)) {
                    $fail('Each image must be a valid image URL (jpg, jpeg, png, gif, webp).');
                }
            }],
            'color_ids' => 'nullable|array',
            'color_ids.*' => 'integer|exists:colors,id',
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'integer|exists:materials,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.string' => 'Tên sản phẩm phải là chuỗi ký tự.',
            'name.unique' => 'Tên sản phẩm đã tồn tại.',
            'category_id.required' => 'Danh mục sản phẩm là bắt buộc.',
            'category_id.integer' => 'Danh mục phải là một số nguyên.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'platform_id.integer' => 'Platform ID phải là số nguyên.',
            'platform_id.exists' => 'Platform ID không tồn tại.',
            'render_id.integer' => 'Render ID phải là số nguyên.',
            'render_id.exists' => 'Render ID không tồn tại.',
            'file_url.required' => 'File URL là bắt buộc.',
            'file_url.url' => 'File URL phải là một đường dẫn hợp lệ.',
            'image_urls.required' => 'Danh sách hình ảnh là bắt buộc.',
            'image_urls.array' => 'Danh sách hình ảnh phải là một mảng.',
            'image_urls.*.url' => 'Hình ảnh phải là một đường dẫn hợp lệ.',
            'color_ids.array' => 'Danh sách màu sắc phải là một mảng.',
            'color_ids.*.integer' => 'Mỗi ID màu sắc phải là số nguyên.',
            'color_ids.*.exists' => 'ID màu sắc không hợp lệ.',
            'material_ids.array' => 'Danh sách chất liệu phải là một mảng.',
            'material_ids.*.integer' => 'Mỗi ID chất liệu phải là số nguyên.',
            'material_ids.*.exists' => 'ID chất liệu không hợp lệ.',
            'tag_ids.array' => 'Danh sách tag phải là một mảng.',
            'tag_ids.*.integer' => 'Mỗi ID tag phải là số nguyên.',
            'tag_ids.*.exists' => 'ID tag không hợp lệ.',
        ];
    }
}
