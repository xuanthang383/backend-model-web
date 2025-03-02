<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin Builder
 * @property int $id
 * @property string $name
 * @property string $hex_code
 */
class Color extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'hex_code'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_colors');
    }
}
