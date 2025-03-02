<?php

namespace App\DTO\Product;

use Spatie\DataTransferObject\DataTransferObject;

class ChangeStatusDTO extends DataTransferObject
{
    public string $status = "";
}
