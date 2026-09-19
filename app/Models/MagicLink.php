<?php

namespace App\Models;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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

    /** Must outlast the mail delivery run while the lock is held. */
    private const int ISSUE_LOCK_SECONDS = 30;

    private const int ISSUE_LOCK_WAIT_SECONDS = 5;

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

    /**
     * Burns the other pending links of the recipient, so that only this link
     * can open a session.
     */
    public function invalidateOtherPendingLinks(): int
    {
        $now = now();

        return self::query()
            ->where('email_hash', $this->email_hash)
            ->whereKeyNot($this->getKey())
            ->whereNull('used_at')
            ->where('expire_at', '>=', $now)
            ->update(['used_at' => $now]);
    }

    /**
     * Creates a link, delivers it, then burns the recipient's other pending
     * links, all under a per-recipient lock: concurrent requests cannot burn
     * each other's fresh link, so exactly the latest delivered link stays
     * valid. A failed delivery throws before the burn and leaves the previous
     * link usable. Returns false, without issuing anything, when the lock
     * stays held by another request.
     *
     * @param Closure(): void $deliver
     */
    public static function issueExclusively(
        string $emailHash,
        #[\SensitiveParameter] string $tokenHash,
        Closure $deliver,
    ): bool {
        $issued = false;

        try {
            Cache::lock("magic-link-issue:{$emailHash}", self::ISSUE_LOCK_SECONDS)
                ->block(self::ISSUE_LOCK_WAIT_SECONDS, function () use ($emailHash, $tokenHash, $deliver, &$issued): void {
                    $magicLink = self::create([
                        'email_hash' => $emailHash,
                        'token_hash' => $tokenHash,
                        'expire_at' => now()->addMinutes(Config::integer('secrets.magic_link_ttl')),
                    ]);

                    $deliver();

                    $magicLink->invalidateOtherPendingLinks();

                    $issued = true;
                });
        } catch (LockTimeoutException) {
            return false;
        }

        return $issued;
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
