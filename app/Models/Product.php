<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'category_id', 
        'platform_id', 
        'render_id', 
        'description', 
        'file_path', 
        'image_path',
        'is_ads', 
        'is_favorite', 
        'material_id'
    ];

    /**
     * Quan hệ với bảng Categories
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Quan hệ với bảng Platforms
     */
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Quan hệ với bảng Renders
     */
    public function render()
    {
        return $this->belongsTo(Render::class);
    }

    /**
     * Quan hệ với bảng Files (chứa file model 3D)
     */
    public function file()
    {
        return $this->hasOne(File::class);
    }

    /**
     * Quan hệ với bảng ProductFiles (chứa ảnh preview sản phẩm)
     */
    public function images()
    {
        return $this->hasMany(ProductFile::class);
    }

    /**
     * Quan hệ với bảng Materials
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Quan hệ với bảng Colors (nếu mỗi sản phẩm có một màu duy nhất)
     */
    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * Quan hệ nhiều-nhiều với bảng Colors (nếu sản phẩm có nhiều màu)
     */
    public function colors()
    {
        return $this->belongsToMany(Color::class, 'product_colors');
    }

    /**
     * Quan hệ nhiều-nhiều với bảng Tags
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }
}
