# セキュリティ設計書

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgプロジェクトにおける包括的なセキュリティ対策を定義し、ユーザーデータとシステムの安全性を確保する。

## 目次

1. [セキュリティ方針](#セキュリティ方針)
2. [脅威分析](#脅威分析)
3. [認証・認可](#認証認可)
4. [データ保護](#データ保護)
5. [API セキュリティ](#api-セキュリティ)
6. [フロントエンドセキュリティ](#フロントエンドセキュリティ)
7. [ゲーム固有セキュリティ](#ゲーム固有セキュリティ)
8. [インフラセキュリティ](#インフラセキュリティ)
9. [監査・ログ](#監査ログ)
10. [インシデント対応](#インシデント対応)
11. [コンプライアンス](#コンプライアンス)

## セキュリティ方針

### 1. 基本原則
```
┌─────────────────────────────────┐
│        セキュリティ原則        │
├─────────────────────────────────┤
│ ・最小権限の原則               │
│ ・多層防御                     │
│ ・データ最小化                 │
│ ・透明性とアカウンタビリティ   │
│ ・継続的改善                   │
└─────────────────────────────────┘
```

### 2. セキュリティ目標
- **機密性**: 認可されたユーザーのみデータにアクセス可能
- **完全性**: データの改ざん・破損を防止
- **可用性**: サービスの継続的な提供
- **真正性**: ユーザーとデータの身元確認
- **否認防止**: 行為の否認を防止

### 3. リスク評価マトリックス
```
影響度 ＼ 発生確率 │ 低    │ 中    │ 高    │
─────────────────┼───────┼───────┼───────┤
高               │ 中    │ 高    │ 極高  │
中               │ 低    │ 中    │ 高    │
低               │ 極低  │ 低    │ 中    │
```

## 脅威分析

### 1. OWASP Top 10 対策
```php
<?php

// A01: アクセス制御の不備
class GameController extends Controller
{
    public function rollDice(Request $request)
    {
        // 認証確認
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // 認可確認（ユーザー自身のキャラクターのみ操作可能）
        $character = $user->character;
        if (!$character) {
            return response()->json(['error' => 'Character not found'], 404);
        }
        
        // ビジネスロジック
        return $this->gameService->rollDice($character);
    }
}

// A02: 暗号化の不備
class UserService
{
    public function hashPassword(string $password): string
    {
        // bcrypt使用（Laravel標準）
        return Hash::make($password);
    }
    
    public function encryptSensitiveData(string $data): string
    {
        // Laravel暗号化
        return Crypt::encryptString($data);
    }
}

// A03: インジェクション対策
class CharacterRepository
{
    public function findByUserId(int $userId): ?Character
    {
        // Eloquent ORM使用でSQLインジェクション対策
        return Character::where('user_id', $userId)->first();
    }
    
    public function updateSkills(Character $character, array $skills): void
    {
        // パラメータバインディング
        $character->update(['skills' => $skills]);
    }
}
```

### 2. ゲーム固有の脅威
```
┌──────────────────────────────────┐
│        ゲーム脅威分析           │
├──────────────────────────────────┤
│ ・チート/ハッキング             │
│ ・アカウント乗っ取り            │
│ ・データ改ざん                  │
│ ・不正プレイ                    │
│ ・リソース悪用                  │
│ ・ソーシャルエンジニアリング    │
└──────────────────────────────────┘
```

### 3. 脅威モデリング
```php
<?php

namespace App\Security;

class ThreatModel
{
    public const THREATS = [
        'DATA_BREACH' => [
            'severity' => 'HIGH',
            'likelihood' => 'MEDIUM',
            'impact' => 'HIGH',
            'controls' => ['encryption', 'access_control', 'monitoring']
        ],
        'ACCOUNT_TAKEOVER' => [
            'severity' => 'HIGH',
            'likelihood' => 'MEDIUM',
            'impact' => 'HIGH',
            'controls' => ['mfa', 'rate_limiting', 'session_management']
        ],
        'CHEATING' => [
            'severity' => 'MEDIUM',
            'likelihood' => 'HIGH',
            'impact' => 'MEDIUM',
            'controls' => ['server_validation', 'anti_cheat', 'monitoring']
        ],
        'DDOS_ATTACK' => [
            'severity' => 'MEDIUM',
            'likelihood' => 'MEDIUM',
            'impact' => 'HIGH',
            'controls' => ['rate_limiting', 'cdn', 'load_balancing']
        ]
    ];
    
    public static function assessRisk(string $threat): array
    {
        return self::THREATS[$threat] ?? [];
    }
}
```

## 認証・認可

### 1. Laravel Breeze 強化
```php
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Events\LoginAttempted;
use App\Events\LoginFailed;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request)
    {
        // レート制限
        $key = $this->throttleKey($request);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds."
            ]);
        }
        
        // ログイン試行をログ
        event(new LoginAttempted($request->email, $request->ip()));
        
        try {
            $request->authenticate();
            
            // セッション再生成（セッション固定化攻撃対策）
            $request->session()->regenerate();
            
            // レート制限をクリア
            RateLimiter::clear($key);
            
        } catch (ValidationException $e) {
            // 失敗をログ
            event(new LoginFailed($request->email, $request->ip()));
            
            // レート制限カウンター増加
            RateLimiter::hit($key, 300); // 5分間
            
            throw $e;
        }
        
        return redirect()->intended(RouteServiceProvider::HOME);
    }
    
    private function throttleKey(Request $request): string
    {
        return 'login:' . $request->ip() . ':' . strtolower($request->email);
    }
}
```

### 2. セッション管理
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecureSession
{
    public function handle(Request $request, Closure $next)
    {
        // セッション設定の強化
        if (!$request->session()->has('_created_at')) {
            $request->session()->put('_created_at', time());
        }
        
        // セッションタイムアウト（2時間）
        if (time() - $request->session()->get('_created_at') > 7200) {
            $request->session()->flush();
            return redirect()->route('login')
                ->with('message', 'Session expired. Please login again.');
        }
        
        // アクティビティタイムアウト（30分）
        if ($request->session()->has('_last_activity')) {
            if (time() - $request->session()->get('_last_activity') > 1800) {
                $request->session()->flush();
                return redirect()->route('login')
                    ->with('message', 'Session timed out due to inactivity.');
            }
        }
        
        $request->session()->put('_last_activity', time());
        
        return $next($request);
    }
}
```

### 3. 権限管理システム
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public const GAME_PLAY = 'game.play';
    public const GAME_ADMIN = 'game.admin';
    public const CHARACTER_MANAGE = 'character.manage';
    public const INVENTORY_MANAGE = 'inventory.manage';
    
    protected $fillable = ['name', 'description'];
    
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}

class Role extends Model
{
    public const PLAYER = 'player';
    public const MODERATOR = 'moderator';
    public const ADMIN = 'admin';
    
    protected $fillable = ['name', 'description'];
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}

// ユーザーモデル拡張
trait HasRoles
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    
    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }
    
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
```

## データ保護

### 1. 暗号化戦略
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class DataEncryptionService
{
    /**
     * 個人情報の暗号化
     */
    public function encryptPII(string $data): string
    {
        return Crypt::encryptString($data);
    }
    
    public function decryptPII(string $encryptedData): string
    {
        return Crypt::decryptString($encryptedData);
    }
    
    /**
     * パスワードハッシュ化
     */
    public function hashPassword(string $password): string
    {
        return Hash::make($password, [
            'rounds' => 12, // bcryptのコスト
        ]);
    }
    
    /**
     * APIトークン生成
     */
    public function generateApiToken(): string
    {
        return hash('sha256', random_bytes(32));
    }
    
    /**
     * セキュアなランダム文字列生成
     */
    public function generateSecureString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
```

### 2. データベース暗号化
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class Character extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'hp',
        'max_hp',
        'sp',
        'max_sp',
        'game_position',
        'location_type',
        'skills',
        'inventory',
        'personal_notes', // 暗号化対象
    ];
    
    protected $casts = [
        'skills' => 'encrypted:array',
        'inventory' => 'encrypted:array',
    ];
    
    /**
     * 個人メモの暗号化
     */
    protected function personalNotes(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }
}
```

### 3. GDPR 準拠
```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Character;

class DataPrivacyService
{
    /**
     * ユーザーデータエクスポート（GDPR Article 20）
     */
    public function exportUserData(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'character' => $user->character ? [
                'name' => $user->character->name,
                'level' => $user->character->getLevel(),
                'skills' => $user->character->skills,
                'created_at' => $user->character->created_at,
            ] : null,
            'game_logs' => $user->gameLogs()->select([
                'action', 'data', 'created_at'
            ])->get()->toArray(),
        ];
    }
    
    /**
     * ユーザーデータ削除（GDPR Article 17）
     */
    public function deleteUserData(User $user): void
    {
        \DB::transaction(function () use ($user) {
            // 関連データの削除
            $user->character?->delete();
            $user->gameLogs()->delete();
            $user->sessions()->delete();
            
            // ユーザーアカウント削除
            $user->delete();
        });
    }
    
    /**
     * データ匿名化
     */
    public function anonymizeUserData(User $user): void
    {
        $user->update([
            'name' => 'Anonymous User ' . $user->id,
            'email' => 'anonymous' . $user->id . '@deleted.local',
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(32)),
            'remember_token' => null,
        ]);
        
        if ($user->character) {
            $user->character->update([
                'name' => 'Anonymous Character ' . $user->character->id,
                'personal_notes' => null,
            ]);
        }
    }
}
```

## API セキュリティ

### 1. レート制限
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class GameApiRateLimit
{
    public function handle(Request $request, Closure $next, string $limits = '60:1')
    {
        [$maxAttempts, $decayMinutes] = explode(':', $limits);
        
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        // レート制限ヘッダーの追加
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $maxAttempts),
            'X-RateLimit-Reset' => RateLimiter::availableIn($key),
        ]);
    }
    
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->user()?->id . '|' . 
            $request->ip() . '|' . 
            $request->path()
        );
    }
}

// 使用例：routes/api.php
Route::middleware(['auth:sanctum', 'game.rate_limit:10:1'])
    ->group(function () {
        Route::post('/game/roll-dice', [GameController::class, 'rollDice']);
        Route::post('/game/move', [GameController::class, 'move']);
    });
```

### 2. API バリデーション
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->character;
    }
    
    public function rules(): array
    {
        return [
            'direction' => [
                'required',
                'string',
                Rule::in(['left', 'right', 'forward', 'backward'])
            ],
            'steps' => [
                'required',
                'integer',
                'min:1',
                'max:30'
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'direction.in' => 'Invalid direction. Must be left, right, forward, or backward.',
            'steps.max' => 'Cannot move more than 30 steps at once.',
        ];
    }
    
    protected function prepareForValidation(): void
    {
        // 入力サニタイゼーション
        $this->merge([
            'direction' => strtolower(trim($this->direction ?? '')),
            'steps' => (int) $this->steps,
        ]);
    }
}
```

### 3. CORS 設定
```php
<?php

// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('APP_URL', 'http://localhost'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

## フロントエンドセキュリティ

### 1. CSP (Content Security Policy)
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'", // Vite対応
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];
        
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        
        return $response;
    }
}
```

### 2. XSS 対策
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class XssProtection
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // セキュリティヘッダーの設定
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        return $response;
    }
}
```

### 3. JavaScript セキュリティ
```javascript
// resources/js/utils/SecurityUtils.js
export class SecurityUtils {
    /**
     * HTMLエスケープ
     */
    static escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    /**
     * CSRFトークン取得
     */
    static getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }
    
    /**
     * 安全なクッキー設定
     */
    static setSecureCookie(name, value, days = 7) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        
        document.cookie = `${name}=${encodeURIComponent(value)}; ` +
            `expires=${expires.toUTCString()}; ` +
            `path=/; ` +
            `secure; ` +
            `samesite=strict`;
    }
    
    /**
     * 入力値検証
     */
    static validateInput(input, type) {
        const patterns = {
            username: /^[a-zA-Z0-9_]{3,20}$/,
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            gameDirection: /^(left|right|forward|backward)$/,
            gameSteps: /^([1-9]|[12][0-9]|30)$/
        };
        
        return patterns[type] ? patterns[type].test(input) : false;
    }
    
    /**
     * セキュアな API コール
     */
    static async secureApiCall(url, options = {}) {
        const token = this.getCsrfToken();
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token && { 'X-CSRF-TOKEN': token })
            },
            credentials: 'same-origin'
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        finalOptions.headers = { ...defaultOptions.headers, ...options.headers };
        
        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }
}
```

## ゲーム固有セキュリティ

### 1. チート対策
```php
<?php

namespace App\Services;

use App\Models\Character;
use App\Events\SuspiciousActivity;

class AntiCheatService
{
    /**
     * 移動妥当性チェック
     */
    public function validateMovement(Character $character, int $steps): bool
    {
        // 最大移動距離チェック
        if ($steps > 30) {
            event(new SuspiciousActivity($character->user, 'excessive_movement', [
                'steps' => $steps,
                'max_allowed' => 30
            ]));
            return false;
        }
        
        // 連続移動時間チェック
        $lastMove = $character->last_move_time;
        if ($lastMove && now()->diffInSeconds($lastMove) < 1) {
            event(new SuspiciousActivity($character->user, 'rapid_movement', [
                'time_diff' => now()->diffInSeconds($lastMove)
            ]));
            return false;
        }
        
        return true;
    }
    
    /**
     * スキル向上妥当性チェック
     */
    public function validateSkillGain(Character $character, string $skill, int $gain): bool
    {
        // 異常なスキル向上チェック
        if ($gain > 5) {
            event(new SuspiciousActivity($character->user, 'excessive_skill_gain', [
                'skill' => $skill,
                'gain' => $gain
            ]));
            return false;
        }
        
        // スキル上限チェック
        $currentSkill = $character->getSkillSet()->getSkill($skill);
        if ($currentSkill + $gain > 100) {
            return false;
        }
        
        return true;
    }
    
    /**
     * セッション整合性チェック
     */
    public function validateSession(Character $character): bool
    {
        $user = $character->user;
        $session = request()->session();
        
        // IPアドレス変更チェック
        if ($session->has('last_ip') && $session->get('last_ip') !== request()->ip()) {
            event(new SuspiciousActivity($user, 'ip_change', [
                'old_ip' => $session->get('last_ip'),
                'new_ip' => request()->ip()
            ]));
        }
        
        $session->put('last_ip', request()->ip());
        
        return true;
    }
}
```

### 2. サーバーサイド検証
```php
<?php

namespace App\Services;

class GameValidationService
{
    /**
     * サイコロ結果検証
     */
    public function validateDiceRoll(array $diceRolls, int $bonus): bool
    {
        // サイコロの数チェック
        if (count($diceRolls) !== 3) {
            return false;
        }
        
        // 各サイコロの値チェック
        foreach ($diceRolls as $roll) {
            if ($roll < 1 || $roll > 6) {
                return false;
            }
        }
        
        // ボーナス値の妥当性チェック
        if ($bonus < 0 || $bonus > 10) {
            return false;
        }
        
        return true;
    }
    
    /**
     * ゲーム状態整合性チェック
     */
    public function validateGameState(Character $character): array
    {
        $errors = [];
        
        // HPの妥当性
        if ($character->hp > $character->max_hp) {
            $errors[] = 'HP exceeds maximum';
        }
        
        // SPの妥当性
        if ($character->sp > $character->max_sp) {
            $errors[] = 'SP exceeds maximum';
        }
        
        // 位置の妥当性
        if ($character->game_position < 0 || $character->game_position > 100) {
            $errors[] = 'Invalid game position';
        }
        
        // スキルレベルの妥当性
        $skills = $character->getSkillSet();
        foreach ($skills->toArray() as $skill => $level) {
            if ($level < 0 || $level > 100) {
                $errors[] = "Invalid skill level for {$skill}";
            }
        }
        
        return $errors;
    }
}
```

## インフラセキュリティ

### 1. Web サーバー設定
```nginx
# nginx.conf セキュリティ設定
server {
    listen 443 ssl http2;
    server_name example.com;
    
    # SSL設定
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
    
    # セキュリティヘッダー
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # ファイルアップロード制限
    client_max_body_size 10M;
    
    # 不要なファイルへのアクセス拒否
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(env|log)$ {
        deny all;
    }
    
    # レート制限
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri /index.php?$query_string;
    }
}
```

### 2. データベースセキュリティ
```php
<?php

// config/database.php
return [
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ],
        ],
    ],
];

// 暗号化設定
// config/app.php
'cipher' => 'AES-256-CBC',
'key' => env('APP_KEY'),
```

## 監査・ログ

### 1. セキュリティログ
```php
<?php

namespace App\Listeners;

use App\Events\SuspiciousActivity;
use Illuminate\Support\Facades\Log;

class SecurityEventListener
{
    public function handle(SuspiciousActivity $event): void
    {
        Log::channel('security')->warning('Suspicious activity detected', [
            'user_id' => $event->user->id,
            'activity_type' => $event->activityType,
            'data' => $event->data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
        
        // 重要度の高い活動は即座にアラート
        if (in_array($event->activityType, ['multiple_failed_logins', 'privilege_escalation'])) {
            $this->sendImmediateAlert($event);
        }
    }
    
    private function sendImmediateAlert(SuspiciousActivity $event): void
    {
        // Slack、メール、SMSなどでアラート送信
        // 実装は環境に応じて
    }
}
```

### 2. 監査トレイル
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// 監査トレイル用トレイト
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::createAuditLog('created', $model);
        });
        
        static::updated(function ($model) {
            static::createAuditLog('updated', $model);
        });
        
        static::deleted(function ($model) {
            static::createAuditLog('deleted', $model);
        });
    }
    
    protected static function createAuditLog(string $action, Model $model): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $model->getOriginal(),
            'new_values' => $model->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

## インシデント対応

### 1. インシデント対応プロセス
```php
<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Notifications\SecurityIncidentNotification;

class IncidentResponseService
{
    public function reportIncident(string $type, array $data): SecurityIncident
    {
        $incident = SecurityIncident::create([
            'type' => $type,
            'severity' => $this->calculateSeverity($type, $data),
            'description' => $this->generateDescription($type, $data),
            'data' => $data,
            'status' => 'reported',
            'reported_at' => now(),
        ]);
        
        $this->notifySecurityTeam($incident);
        
        if ($incident->severity === 'critical') {
            $this->initiateEmergencyResponse($incident);
        }
        
        return $incident;
    }
    
    private function calculateSeverity(string $type, array $data): string
    {
        $severityMap = [
            'data_breach' => 'critical',
            'unauthorized_access' => 'high',
            'suspicious_activity' => 'medium',
            'failed_login' => 'low',
        ];
        
        return $severityMap[$type] ?? 'medium';
    }
    
    private function initiateEmergencyResponse(SecurityIncident $incident): void
    {
        // 緊急時対応
        // - 関連アカウントの無効化
        // - システムの隔離
        // - 証拠保全
        // - 関係者への通知
    }
}
```

### 2. 自動対応システム
```php
<?php

namespace App\Services;

class AutomatedResponseService
{
    public function handleSuspiciousActivity(string $type, array $data): void
    {
        match ($type) {
            'multiple_failed_logins' => $this->lockAccount($data['user_id']),
            'unusual_api_usage' => $this->rateLimit($data['ip_address']),
            'data_exfiltration' => $this->blockIpAddress($data['ip_address']),
            default => $this->logIncident($type, $data),
        };
    }
    
    private function lockAccount(int $userId): void
    {
        User::find($userId)?->update(['locked_until' => now()->addHours(24)]);
    }
    
    private function rateLimit(string $ipAddress): void
    {
        RateLimiter::hit("suspicious:{$ipAddress}", 3600);
    }
    
    private function blockIpAddress(string $ipAddress): void
    {
        // WAFやファイアウォールへのAPI呼び出し
        // 実装は環境に応じて
    }
}
```

## コンプライアンス

### 1. GDPR 対応
```php
<?php

namespace App\Http\Controllers;

use App\Services\DataPrivacyService;
use Illuminate\Http\Request;

class DataPrivacyController extends Controller
{
    public function __construct(
        private DataPrivacyService $privacyService
    ) {}
    
    /**
     * データポータビリティ（GDPR Article 20）
     */
    public function exportData(Request $request)
    {
        $user = $request->user();
        $data = $this->privacyService->exportUserData($user);
        
        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="user-data.json"');
    }
    
    /**
     * 削除権（GDPR Article 17）
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:DELETE_MY_ACCOUNT'
        ]);
        
        $user = $request->user();
        
        // 法的な保持義務がないことを確認
        if ($this->hasLegalRetentionRequirement($user)) {
            return response()->json([
                'error' => 'Account cannot be deleted due to legal requirements'
            ], 400);
        }
        
        $this->privacyService->deleteUserData($user);
        
        return response()->json(['message' => 'Account deleted successfully']);
    }
    
    private function hasLegalRetentionRequirement(User $user): bool
    {
        // 法的保持要件のチェック
        // 例：税務関連、係争中のケースなど
        return false;
    }
}
```

### 2. セキュリティ監査
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SecurityAuditService;

class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit {--report}';
    protected $description = 'Run security audit checks';
    
    public function handle(SecurityAuditService $auditService): int
    {
        $this->info('Starting security audit...');
        
        $results = $auditService->runAudit([
            'user_permissions',
            'password_policies',
            'session_security',
            'api_security',
            'data_encryption',
        ]);
        
        foreach ($results as $check => $result) {
            $status = $result['passed'] ? 'PASS' : 'FAIL';
            $this->line("{$check}: {$status}");
            
            if (!$result['passed']) {
                $this->error("  Issues: " . implode(', ', $result['issues']));
            }
        }
        
        if ($this->option('report')) {
            $auditService->generateReport($results);
            $this->info('Security audit report generated.');
        }
        
        return Command::SUCCESS;
    }
}
```

## まとめ

### セキュリティチェックリスト
- [ ] 認証・認可システムの実装
- [ ] データ暗号化の設定
- [ ] API セキュリティの確保
- [ ] XSS/CSRF対策の実装
- [ ] レート制限の設定
- [ ] セキュリティヘッダーの設定
- [ ] ログ・監視システムの構築
- [ ] インシデント対応プロセスの確立
- [ ] GDPR コンプライアンスの確保
- [ ] 定期的なセキュリティ監査の実施

### 継続的改善
1. **定期的な脆弱性スキャン**
2. **ペネトレーションテスト**
3. **セキュリティ教育・訓練**
4. **脅威インテリジェンスの活用**
5. **セキュリティ指標の監視**

このセキュリティ設計により、test_smgシステムの安全性と信頼性を確保できます。