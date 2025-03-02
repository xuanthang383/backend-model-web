<?php

namespace App\DTO\Product;

use Spatie\DataTransferObject\DataTransferObject;

class CreateMultipleDTO extends DataTransferObject
{
    public string $name = "";
    public int $category = 0;
    public ?int $platform = null;
    public ?int $render = null;
    public string $file_url = "";
    public ?array $image_urls = null;
    public ?array $colors = null;
    public ?array $materials = null;
    public ?array $tags = null;
}
