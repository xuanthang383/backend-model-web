<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin  Builder
 * @property int $id
 * @property int $file_id
 * @property string $token
 * @property bool $used
 * @property int $delay_until
 * @property string $request_ip
 * @property-read File $file
 */
class FileDownload extends Model
{
    use HasFactory;

    protected $fillable = ['file_id', 'token', 'used', 'delay_until', 'request_ip'];
    protected $casts = ['used' => 'boolean'];

    /**
     * Quan hệ với bảng File (Mỗi file download thuộc về một file)
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
