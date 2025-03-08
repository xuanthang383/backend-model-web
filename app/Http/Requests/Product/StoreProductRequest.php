<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:products,name',
            'category_id' => 'required|integer|exists:categories,id',
            'platform_id' => 'nullable|integer|exists:platforms,id',
            'render_id' => 'nullable|integer|exists:renders,id',
            'file_url' => ['required', 'url', function ($attribute, $value, $fail) {
                if (!preg_match('/\.(rar|zip)$/i', $value)) {
                    $fail('The file_url must be a valid RAR or ZIP file.');
                }
            }],
            'image_urls' => 'nullable|array',
            'image_urls.*' => ['required', 'url', function ($attribute, $value, $fail) {
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
            'name.required' => 'Product name is required.',
            'name.string' => 'Product name must be a string.',
            'name.unique' => 'Product name already exists.',
            'category_id.required' => 'Product category is required.',
            'category_id.integer' => 'Category must be an integer.',
            'category_id.exists' => 'Category does not exist.',
            'platform_id.integer' => 'Platform ID must be an integer.',
            'platform_id.exists' => 'Platform ID does not exist.',
            'render_id.integer' => 'Render ID must be an integer.',
            'render_id.exists' => 'Render ID does not exist.',
            'file_url.required' => 'File URL is required.',
            'file_url.url' => 'File URL must be a valid URL.',
            'image_urls.array' => 'Image list must be an array.',
            'image_urls.*.required' => 'Each image must have a valid URL.',
            'image_urls.*.url' => 'Each image must be a valid URL.',
            'color_ids.array' => 'Color list must be an array.',
            'color_ids.*.integer' => 'Each color ID must be an integer.',
            'color_ids.*.exists' => 'Invalid color ID.',
            'material_ids.array' => 'Material list must be an array.',
            'material_ids.*.integer' => 'Each material ID must be an integer.',
            'material_ids.*.exists' => 'Invalid material ID.',
            'tag_ids.array' => 'Tag list must be an array.',
            'tag_ids.*.integer' => 'Each tag ID must be an integer.',
            'tag_ids.*.exists' => 'Invalid tag ID.',
        ];
    }
}
