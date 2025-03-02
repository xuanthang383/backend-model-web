<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Collection\Collection;

/**
 * @mixin Builder
 * @property int $product_id
 * @property int $file_id
 * @property bool $is_model
 * @property bool $is_thumbnail
 * @property-read File $file
 * @property-read Product $product
 */
class ProductFiles extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'file_id', 'is_thumbnail', 'is_model'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
