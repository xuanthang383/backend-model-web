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
        'user_id',
        'reason_id',
        'value',
        'message',
        'status',
        'admin_note'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function errorReason()
    {
        return $this->belongsTo(ErrorReason::class, 'reason_id');
    }
}
