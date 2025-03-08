<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_token',
        'email_verified_at',
        'function',
        'role_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function favoriteProducts()
    {
        return $this->hasMany(FavoriteProduct::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function getPermissionsJson()
    {
        if (!$this->role) {
            return []; // Trả về danh sách trống nếu user không có role
        }

        $permissions = $this->role->permissions->groupBy('function');
        $formattedPermissions = [];

        foreach ($permissions as $function => $actions) {
            $formattedPermissions[$function] = [];
            foreach ($actions as $action) {
                $formattedPermissions[$function][$action->action] = true;
            }
        }
        return $formattedPermissions;
    }

}
