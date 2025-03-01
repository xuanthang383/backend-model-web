<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin  Builder
 * @property int $id
 * @property string $name
 * @property int $category_id
 * @property int|null $platform_id
 * @property int|null $render_id
 * @property int $user_id
 * @property string $status
 * @property-read Category $category
 * @property-read Platform|null $platform
 * @property-read Render|null $render
 * @property-read User $user
 * @property-read Collection|Color[] $colors
 * @property-read int|null $colors_count
 * @property-read Collection|Material[] $materials
 * @property-read int|null $materials_count
 * @property-read Collection|File[] $files
 * @property-read int|null $files_count
 * @property-read Collection|Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read Collection|Library[] $libraries
 * @property-read int|null $libraries_count
 * @property-read Collection|ProductFiles[] $productFiles
 * @property-read int|null $product_files_count
 */
class Product extends Model
{
    use HasFactory;

    /**
     * @var \Illuminate\Support\HigherOrderCollectionProxy|mixed
     */
    protected $fillable = [
        'name',
        'user_id',
        'category_id',
        'platform_id',
        'render_id',
        'color_id',
        'material_id',
        'public',
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

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'product_files', 'product_id', 'file_id')
            ->withPivot('is_thumbnail'); // Thêm trường từ bảng trung gian
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function libraries(): BelongsToMany
    {
        return $this->belongsToMany(Library::class, 'library_product', 'product_id', 'library_id');
    }

    public function thumbnail(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'product_files', 'product_id', 'file_id')
            ->wherePivot('is_thumbnail', true)
            ->limit(1);
    }
}
