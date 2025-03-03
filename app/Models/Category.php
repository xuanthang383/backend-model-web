<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin  Builder
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property-read Category|null $parent
 * @property-read Collection|Category[] $children
 * @property-read int|null $children_count
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id'];

    // Danh mục con
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Danh mục cha
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Danh sách sản phẩm thuộc danh mục
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
