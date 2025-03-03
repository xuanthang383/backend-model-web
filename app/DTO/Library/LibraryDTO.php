<?php

namespace App\DTO\Library;

use Spatie\DataTransferObject\DataTransferObject;

class LibraryDTO extends DataTransferObject
{
    public string|null $parent_id = null;
}
