<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin  Builder
 * @property int $id
 * @property int $product_id
 * @property string $reason
 * @property string $message
 * @property string $status
 * @property Product $product
 */
class ProductErrorReport extends Model
{
    protected $fillable = [
        'product_id',
        'reason',
        'message',
        'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
