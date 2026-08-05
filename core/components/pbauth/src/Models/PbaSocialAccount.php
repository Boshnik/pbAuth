<?php

namespace Boshnik\PbAuth\Models;

use Boshnik\PageBlocks\Models\BaseModel;

/**
 * Привязка аккаунта соцсети к пользователю сайта.
 *
 * Без мягкого удаления: отвязка должна освобождать пару (провайдер, id), иначе
 * уникальный индекс не даст привязать тот же аккаунт заново.
 */
class PbaSocialAccount extends BaseModel
{
    protected $table = 'pba_social_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'email',
        'nickname',
        'avatar',
        'properties',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function findAccount(string $provider, string $providerId): ?self
    {
        return static::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function forUser(int $userId)
    {
        return static::query()->where('user_id', $userId)->get();
    }
}
