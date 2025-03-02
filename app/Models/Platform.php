<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin  Builder
 * @property int $id
 * @property string $name
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 */
class Platform extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function products(): Platform|Builder|HasMany
    {
        return $this->hasMany(Product::class, 'platform_id');
    }
}
