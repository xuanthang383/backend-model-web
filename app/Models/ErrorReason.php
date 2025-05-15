<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorReason extends Model
{
    public const ERROR_NAME = "err_name";

    protected $fillable = ['name', 'is_active'];

}
