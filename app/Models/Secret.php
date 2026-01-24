<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $token
 * @property string $admin_token
 * @property string $type
 * @property array<string, mixed> $cipher_meta
 * @property string|null $ciphertext
 * @property string|null $file_path
 * @property string|null $filename
 * @property string|null $mime
 * @property int|null $size
 * @property bool $usage_unique
 * @property int|null $max_views
 * @property int $read_count
 * @property Carbon|null $first_read_at
 * @property Carbon|null $last_read_at
 * @property Carbon|null $expire_at
 * @property Carbon|null $revoked_at
 * @property string|null $creator_email
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Secret extends Model
{
    use HasUuids;

    protected $fillable = [
        'token',
        'admin_token',
        'type',
        'cipher_meta',
        'ciphertext',
        'file_path',
        'filename',
        'mime',
        'size',
        'usage_unique',
        'max_views',
        'read_count',
        'expire_at',
        'revoked_at',
        'creator_email',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cipher_meta' => 'array',
            'usage_unique' => 'boolean',
            'first_read_at' => 'datetime',
            'last_read_at' => 'datetime',
            'expire_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expire_at !== null && $this->expire_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasReachedMaxViews(): bool
    {
        return $this->max_views !== null && $this->read_count >= $this->max_views;
    }

    public function isAccessible(): bool
    {
        return ! $this->isExpired()
            && ! $this->isRevoked()
            && ! $this->hasReachedMaxViews();
    }

    public function incrementReadCount(): void
    {
        $now = now();

        $this->read_count++;
        $this->last_read_at = $now;

        if ($this->first_read_at === null) {
            $this->first_read_at = $now;
        }

        $this->save();
    }
}
