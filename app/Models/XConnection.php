<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\XConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $user_id
 * @property-read string $x_user_id
 * @property-read string $username
 * @property-read string $access_token
 * @property-read string $refresh_token
 * @property-read CarbonInterface $expires_at
 * @property-read CarbonInterface|null $last_synced_at
 * @property-read CarbonInterface|null $last_full_synced_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 */
#[Fillable([
    'x_user_id',
    'username',
    'access_token',
    'refresh_token',
    'expires_at',
    'last_synced_at',
    'last_full_synced_at',
])]
#[Hidden([
    'access_token',
    'refresh_token',
])]
final class XConnection extends Model
{
    /** @use HasFactory<XConnectionFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{username: string, lastSyncedAt: string|null, lastFullSyncedAt: string|null}
     */
    public function toWire(): array
    {
        return [
            'username' => $this->username,
            'lastSyncedAt' => $this->last_synced_at?->toIso8601String(),
            'lastFullSyncedAt' => $this->last_full_synced_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'x_user_id' => 'string',
            'username' => 'string',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'last_full_synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
