<?php

namespace App\DTO\Category;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateDTO extends DataTransferObject
{
    public string $name = "";
    public ?int $parent_id = null;
}
