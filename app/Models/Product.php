<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category_id', 'description'];

    public function file()
    {
        return $this->hasOne(File::class);
    }

    public function images()
    {
        return $this->hasMany(ProductFile::class);
    }
}
