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

    public function favoriteProducts()
    {
        return $this->hasMany(FavoriteProduct::class);
    }

    public function role()
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
