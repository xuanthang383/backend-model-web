<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 * @property int $library_id
 * @property int $product_id
 * @property-read Library|null $library
 * @property-read Product|null $product
 */
class LibraryProduct extends Model
{
    use HasFactory;

    protected $table = 'library_product'; // Tên bảng

    protected $fillable = [
        'library_id',
        'product_id',
    ];

    public $timestamps = false;

    /**
     * Quan hệ với bảng Library (Mỗi sản phẩm thuộc về một thư viện)
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    /**
     * Quan hệ với bảng Product (Mỗi sản phẩm thuộc về một sản phẩm)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
