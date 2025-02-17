<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'file_name', 'file_path'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function files()
    {
        return $this->hasMany(ProductFiles::class);
    }

}
