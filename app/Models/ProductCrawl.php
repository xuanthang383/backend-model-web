<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductCrawl
 *
 * @mixin Builder
 * @property int $id
 * @property string $app_config
 * @property string $url
 * @property string|null $title
 * @property string|null $images
 * @property string|null $description
 * @property string|null $category
 * @property string|null $platform
 * @property string|null $renders
 * @property string|null $materials
 * @property string|null $colors
 * @property string|null $tags
 * @property bool $is_sync
 * @property Carbon $created_at
 * @property string|null $note
 *
 */
class ProductCrawl extends Model
{
    protected $table = 'product_crawl';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'app_config',
        'url',
        'title',
        'images',
        'description',
        'category',
        'platform',
        'renders',
        'materials',
        'colors',
        'tags',
        'is_sync',
        'created_at',
        'note',
    ];

    protected $casts = [
        'is_sync' => 'boolean',
        'created_at' => 'datetime',
    ];
}
