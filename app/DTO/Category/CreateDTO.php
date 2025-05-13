<?php

namespace App\DTO\Category;

use Spatie\DataTransferObject\DataTransferObject;

class CreateDTO extends DataTransferObject
{
    public string $name = "";
    public ?int $parent_id = null;
}
