<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Class AppConfig
 *
 * @mixin Builder
 * @property int $id
 * @property string $config_key
 * @property string|null $config_value
 * @property string|null $description
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AppConfig extends Model
{
    protected $table = 'app_configs';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'config_key',
        'config_value',
        'description',
        'type',
    ];

    protected $casts = [
        'id' => 'integer',
        'config_value' => 'string',
        'description' => 'string',
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

