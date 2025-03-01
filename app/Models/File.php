<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin  Builder
 */
class File extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'file_name', 'file_path'];

    public static string $MODEL_FILE_PATH = '/models';
    public static string $IMAGE_FILE_PATH = '/images';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function files()
    {
        return $this->hasMany(ProductFiles::class);
    }

}
