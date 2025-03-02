<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin  Builder
 * @property int $id
 * @property string $file_name
 * @property string $file_path
 * @property int $product_id
 */
class File extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'file_name', 'file_path'];

    public const MODEL_FILE_NAME = 'models';
    public const MODEL_FILE_PATH = self::MODEL_FILE_NAME . '/';
    public const IMAGE_FILE_NAME = 'images';
    public const IMAGE_FILE_PATH = self::IMAGE_FILE_NAME . '/';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function files(): Builder|HasMany|File
    {
        return $this->hasMany(ProductFiles::class);
    }

}
