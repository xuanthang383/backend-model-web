<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 * @property int $id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property-read Library|null $parent
 * @property-read Collection|Library[] $children
 * @property-read int|null $children_count
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 */
class Library extends Model
{
    protected $table = 'libraries';

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'description',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'library_product', 'library_id', 'product_id');
    }

    // Nếu muốn định nghĩa quan hệ với User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Quan hệ với chính nó để xác định thư viện cha (nếu có)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Library::class, 'parent_id');
    }

    // Quan hệ để lấy danh sách thư viện con (nếu có)
    public function children(): HasMany
    {
        return $this->hasMany(Library::class, 'parent_id');
    }
}
