<?php

namespace App\DTO\Product;

use Spatie\DataTransferObject\DataTransferObject;

class CreateDTO extends DataTransferObject
{
    public string $name = "";
    public int $category_id = 0;
    public ?int $platform_id = null;
    public ?int $render_id = null;
    public string $file_url = "";
    public ?array $image_urls = null;
    public ?array $color_ids = null;
    public ?array $material_ids = null;
    public ?array $tag_ids = null;
    public bool $is_model_link = false;
}
