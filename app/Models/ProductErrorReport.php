<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductErrorReport extends Model
{
    protected $fillable = [
        'product_id',
        'reason',
        'message',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
