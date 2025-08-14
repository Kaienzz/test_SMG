<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\ActiveBattle;
use App\Models\Player;
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
            'admin_permissions' => 'array',
            'admin_activated_at' => 'datetime',
            'admin_last_login_at' => 'datetime',
        ];
    }

    // 管理者権限関連メソッド
    public function getIsAdminAttribute(): bool
    {
        return !empty($this->admin_level);
    }

    public function hasAdminLevel(string $level): bool
    {
        if (!$this->is_admin) {
            return false;
        }

        $levels = ['basic', 'admin', 'super'];
        $userLevel = array_search($this->admin_level, $levels);
        $requiredLevel = array_search($level, $levels);

        return $userLevel !== false && $requiredLevel !== false && $userLevel >= $requiredLevel;
    }

    public function isSuperAdmin(): bool
    {
        return $this->admin_level === 'super';
    }

    // リレーション
    public function player(): HasOne
    {
        return $this->hasOne(Player::class);
    }
    
    // 下位互換性のためのCharacterリレーション
    public function character(): HasOne
    {
        return $this->hasOne(Player::class); // Playerテーブルを直接参照
    }

    public function battleLogs(): HasMany
    {
        return $this->hasMany(BattleLog::class);
    }

    // ゲーム関連のメソッド
    public function getOrCreatePlayer(): Player
    {
        return Player::getOrCreateForUser($this->id);
    }
    
    // 下位互換性のためのCharacterメソッド
    public function getOrCreateCharacter(): Player
    {
        return $this->getOrCreatePlayer();
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
        $player = $this->getOrCreatePlayer();
        
        return [
            'player' => [
                'name' => $player->name,
                'level' => $player->level,
                'location' => [
                    'type' => $player->location_type,
                    'id' => $player->location_id,
                    'position' => $player->game_position,
                ],
                'resources' => [
                    'hp' => $player->hp,
                    'max_hp' => $player->max_hp,
                    'sp' => $player->sp,
                    'max_sp' => $player->max_sp,
                    'mp' => $player->mp,
                    'max_mp' => $player->max_mp,
                    'gold' => $player->gold,
                ],
            ],
            'character' => [
                'name' => $player->name,
                'level' => $player->level,
                'location' => [
                    'type' => $player->location_type,
                    'id' => $player->location_id,
                    'position' => $player->game_position,
                ],
                'resources' => [
                    'hp' => $player->hp,
                    'max_hp' => $player->max_hp,
                    'sp' => $player->sp,
                    'max_sp' => $player->max_sp,
                    'mp' => $player->mp,
                    'max_mp' => $player->max_mp,
                    'gold' => $player->gold,
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
