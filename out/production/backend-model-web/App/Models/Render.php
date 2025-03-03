<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Render extends Model
{
    use HasFactory;
    public function products()
    {
        return $this->hasMany(Product::class, 'render_id');
    }
    
    protected $fillable = ['name'];
}
