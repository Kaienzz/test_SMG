<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\ActiveBattle;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_active_at',
        'last_device_type',
        'last_ip_address',
        'session_data',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_active_at' => 'datetime',
            'session_data' => 'array',
        ];
    }

    // リレーション
    public function character(): HasOne
    {
        return $this->hasOne(Character::class);
    }

    public function battleLogs(): HasMany
    {
        return $this->hasMany(BattleLog::class);
    }

    // ゲーム関連のメソッド
    public function getOrCreateCharacter(): Character
    {
        return Character::getOrCreateForUser($this->id);
    }

    public function getBattleStats(): array
    {
        return BattleLog::getUserStats($this->id);
    }

    // Multi-device support methods
    public function updateDeviceActivity(string $deviceType = null, string $ipAddress = null, array $sessionData = []): void
    {
        $this->update([
            'last_active_at' => now(),
            'last_device_type' => $deviceType ?? $this->detectDeviceType(),
            'last_ip_address' => $ipAddress ?? request()->ip(),
            'session_data' => array_merge($this->session_data ?? [], $sessionData),
        ]);
    }

    public function isActiveOnMultipleDevices(): bool
    {
        // Check if user has been active on different devices within last hour
        return $this->last_active_at && 
               $this->last_active_at->diffInMinutes(now()) < 60 &&
               !empty($this->session_data);
    }

    public function syncGameStateAcrossDevices(): array
    {
        $character = $this->getOrCreateCharacter();
        
        return [
            'character' => [
                'name' => $character->name,
                'level' => $character->level,
                'location' => [
                    'type' => $character->location_type,
                    'id' => $character->location_id,
                    'position' => $character->game_position,
                ],
                'resources' => [
                    'hp' => $character->hp,
                    'max_hp' => $character->max_hp,
                    'sp' => $character->sp,
                    'max_sp' => $character->max_sp,
                    'mp' => $character->mp,
                    'max_mp' => $character->max_mp,
                    'gold' => $character->gold,
                ],
            ],
            'active_battle' => ActiveBattle::getUserActiveBattle($this->id)?->battle_id,
            'last_sync' => now()->toISOString(),
            'device_info' => [
                'type' => $this->last_device_type,
                'last_active' => $this->last_active_at?->toISOString(),
            ],
        ];
    }

    private function detectDeviceType(): string
    {
        $userAgent = request()->userAgent();
        
        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) {
            return 'mobile';
        } elseif (str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad')) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}
