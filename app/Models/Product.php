<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin  Builder
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'category_id',
        'platform_id',
        'render_id',
        'color_id',
        'material_id',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public const STATUS_DRAFT = "DRAFT";
    public const STATUS_PENDING_APPROVAL = "PENDING_APPROVAL";
    public const STATUS_APPROVED = "APPROVED";
    public const STATUS_REJECT = "REJECT";
    public const STATUS_LIST = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_REJECT
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    public function render(): BelongsTo
    {
        return $this->belongsTo(Render::class, 'render_id');
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'product_colors');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'product_materials');
    }

    public function files(): Builder|HasManyThrough|Product
    {
        return $this->hasManyThrough(File::class, ProductFiles::class, 'product_id', 'id', 'id', 'file_id');
    }


    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function libraries(): BelongsToMany
    {
        return $this->belongsToMany(Library::class, 'library_product', 'product_id', 'library_id');
    }
}
