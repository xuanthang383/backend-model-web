<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteProduct extends Model
{
    use HasFactory;

    protected $table = 'favorite_products'; // Tên bảng

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public $timestamps = true;

    /**
     * Quan hệ với bảng User (Mỗi sản phẩm yêu thích thuộc về một user)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với bảng Product (Mỗi sản phẩm yêu thích thuộc về một product)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
