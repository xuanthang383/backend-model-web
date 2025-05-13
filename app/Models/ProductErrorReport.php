<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductErrorReport extends Model
{
    protected $fillable = [
        'product_id',
        'reason_id',
        'message',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reason()
    {
        return $this->belongsTo(ErrorReason::class, 'reason_id');
    }
}
