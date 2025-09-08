<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @mixin  Builder | QueryBuilder
 * @property int $id
 * @property string $file_name
 * @property string $file_path
 * @property int $product_id
 * @property bool $is_model_link
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'file_name',
        'file_path',
        'is_model_link',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_model_link' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const MODEL_FILE_NAME = 'models';
    public const MODEL_FILE_PATH = self::MODEL_FILE_NAME . '/';
    public const IMAGE_FILE_NAME = 'images';
    public const IMAGE_FILE_PATH = self::IMAGE_FILE_NAME . '/';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFiles::class);
    }

}
