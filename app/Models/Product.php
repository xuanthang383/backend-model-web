<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'public'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    public function render()
    {
        return $this->belongsTo(Render::class, 'render_id');
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class, 'product_colors');
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'product_materials');
    }

    public function files()
    {
        return $this->belongsToMany(File::class, 'product_files', 'product_id', 'file_id')
            ->withPivot('is_thumbnail'); // Thêm trường từ bảng trung gian
    }



    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function libraries()
    {
        return $this->belongsToMany(Library::class, 'library_product', 'product_id', 'library_id');
    }

    public function thumbnail()
    {
        return $this->belongsToMany(File::class, 'product_files', 'product_id', 'file_id')
            ->wherePivot('is_thumbnail', true)
            ->limit(1);
    }
}
