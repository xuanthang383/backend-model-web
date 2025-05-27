<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * @mixin Builder
 * @property int $id
 * @property string $function
 * @property string $function_name
 * @property string $action
 * @property string $key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|Role[] $roles
 * @property-read int|null $roles_count
 */
class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['function', 'function_name', 'action', 'key'];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
