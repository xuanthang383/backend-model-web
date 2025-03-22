<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HideProduct extends Model
{
    use HasFactory;

    protected $table = 'hide_products'; // Tên bảng

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public $timestamps = true;

    /**
     * Quan hệ với bảng User (Mỗi sản phẩm ẩn đi của một user)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với bảng Product (Mỗi sản ẩn đi của một product)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
