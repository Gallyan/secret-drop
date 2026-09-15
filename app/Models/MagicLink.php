<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * @property string $id
 * @property string $email_hash
 * @property string $token_hash
 * @property Carbon $expire_at
 * @property Carbon|null $used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MagicLink extends Model
{
    /** @use HasFactory<\Database\Factories\MagicLinkFactory> */
    use HasFactory;
    use HasUuids;

    public const SUPER_ADMIN_EMAIL_HASH = 'superadmin';

    protected $fillable = [
        'email_hash',
        'token_hash',
        'expire_at',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expire_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expire_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->email_hash === self::SUPER_ADMIN_EMAIL_HASH;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }

    /**
     * Consumes the link with a conditional update, so that only one of several
     * concurrent requests holding the same unused link can win.
     */
    public function markAsUsed(): bool
    {
        $usedAt = now();

        $consumed = self::query()
            ->whereKey($this->getKey())
            ->whereNull('used_at')
            ->where('expire_at', '>=', $usedAt)
            ->update(['used_at' => $usedAt]);

        if ($consumed === 0) {
            return false;
        }

        $this->used_at = $usedAt;
        $this->syncOriginalAttribute('used_at');

        return true;
    }

    public static function findByToken(#[\SensitiveParameter] string $token): ?self
    {
        return self::where('token_hash', hash('sha256', $token))->first();
    }

    public static function hashEmail(#[\SensitiveParameter] string $email): string
    {
        return hash_hmac(
            'sha256',
            strtolower(trim($email)),
            Config::string('secrets.email_hash_pepper')
        );
    }
}
