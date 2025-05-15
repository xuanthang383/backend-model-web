<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin Builder
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $verification_token
 * @property string|null $email_verified_at
 * @property string|null $function
 * @property string|null $role_id
 * @property string|null $avatar
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, FavoriteProduct> $favoriteProducts
 * @property-read int|null $favorite_products_count
 * @property-read Collection<int, Role> $role
 * @property-read int|null $role_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 */
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
        'role_id',
        'avatar'
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

    public function favoriteProducts(): HasMany|Builder|User
    {
        return $this->hasMany(FavoriteProduct::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function getPermissionsJson(): array
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

    /**
     * Mutator để tự động chuyển đổi avatar thành URL đầy đủ khi lưu vào database
     *
     * @param string|null $value
     * @return void
     */
    public function setAvatarAttribute($value)
    {
        if (!$value) {
            $this->attributes['avatar'] = null;
            return;
        }

        // Nếu giá trị đã là URL đầy đủ, không cần xử lý thêm
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $this->attributes['avatar'] = $value;
            return;
        }

        // Nếu là tên file, chuyển đổi thành URL đầy đủ
        try {
            $baseUrl = config('app.file_path');

            // Đảm bảo URL kết thúc bằng dấu /
            if (!str_ends_with($baseUrl, '/')) {
                $baseUrl .= '/';
            }

            // Kiểm tra xem giá trị đã có đường dẫn thư mục chưa
            if (strpos($value, '/') === false) {
                $this->attributes['avatar'] = $baseUrl . "avatars/{$value}";
            } else {
                $this->attributes['avatar'] = $baseUrl . $value;
            }
        } catch (\Exception $e) {
            \Log::error('Error setting avatar URL: ' . $e->getMessage(), [
                'user_id' => $this->id ?? 'new_user',
                'avatar_value' => $value
            ]);
            $this->attributes['avatar'] = $value; // Lưu giá trị gốc nếu có lỗi
        }
    }

}
