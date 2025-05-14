<?php
// app/Models/ProductNameChangeRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductNameChangeRequest extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'current_name',
        'suggested_name',
        'reason',
        'status',
        'admin_note'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
