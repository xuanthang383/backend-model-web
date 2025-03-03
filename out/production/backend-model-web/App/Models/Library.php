<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Library extends Model
{
    protected $table = 'libraries';

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'description',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'library_product', 'library_id', 'product_id');
    }


    // Nếu muốn định nghĩa quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Quan hệ với chính nó để xác định thư viện cha (nếu có)
    public function parent()
    {
        return $this->belongsTo(Library::class, 'parent_id');
    }

    // Quan hệ để lấy danh sách thư viện con (nếu có)
    public function children()
    {
        return $this->hasMany(Library::class, 'parent_id');
    }
}
