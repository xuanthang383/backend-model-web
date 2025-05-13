<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorReason extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function reports()
    {
        return $this->hasMany(ProductErrorReport::class, 'reason_id');
    }
}
