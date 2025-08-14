<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IsAdmin
{
    /**
     * 管理者認証ミドルウェア
     * セキュアで拡張性のある管理者アクセス制御
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 認証チェック
        if (!Auth::check()) {
            Log::warning('管理者エリアへの未認証アクセス試行', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'timestamp' => now()
            ]);
            
            return redirect()->route('login')->with('error', '管理者エリアにアクセスするにはログインが必要です。');
        }

        $user = Auth::user();

        // 管理者権限チェック
        if (!$user->is_admin) {
            $this->logSecurityEvent($request, $user, 'unauthorized_admin_access', 'critical');
            
            abort(403, 'このエリアにアクセスする権限がありません。');
        }

        // 管理者アカウントの有効性チェック
        if (!$this->isValidAdminAccount($user)) {
            $this->logSecurityEvent($request, $user, 'invalid_admin_account', 'critical');
            
            Auth::logout();
            return redirect()->route('login')->with('error', '管理者アカウントが無効になっています。管理者にお問い合わせください。');
        }

        // IPホワイトリストチェック（設定されている場合）
        if (!$this->checkIpWhitelist($request, $user)) {
            $this->logSecurityEvent($request, $user, 'ip_whitelist_violation', 'critical');
            
            abort(403, '許可されていないIPアドレスからのアクセスです。');
        }

        // セッション管理とアクティビティ追跡
        $this->updateAdminActivity($request, $user);

        // 2段階認証チェック（将来拡張用）
        if ($user->admin_requires_2fa && !$this->check2FA($request, $user)) {
            return redirect()->route('admin.2fa.verify')->with('warning', '2段階認証が必要です。');
        }

        return $next($request);
    }

    /**
     * 管理者アカウントの有効性チェック
     */
    private function isValidAdminAccount($user): bool
    {
        // アクティブ状態チェック
        if (!$user->admin_activated_at) {
            return false;
        }

        // 管理者ロールの確認
        if ($user->admin_role_id) {
            $role = DB::table('admin_roles')
                ->where('id', $user->admin_role_id)
                ->where('is_active', true)
                ->first();
            
            if (!$role) {
                return false;
            }
        }

        // 期限切れチェック（将来拡張用）
        // if ($user->admin_expires_at && $user->admin_expires_at < now()) {
        //     return false;
        // }

        return true;
    }

    /**
     * IPホワイトリストチェック
     */
    private function checkIpWhitelist(Request $request, $user): bool
    {
        if (!$user->admin_ip_whitelist) {
            return true; // ホワイトリストが設定されていない場合は許可
        }

        $allowedIps = json_decode($user->admin_ip_whitelist, true);
        $clientIp = $request->ip();

        if (empty($allowedIps)) {
            return true;
        }

        foreach ($allowedIps as $allowedIp) {
            if ($this->matchIpRange($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * IP範囲マッチング（CIDR記法対応）
     */
    private function matchIpRange(string $ip, string $range): bool
    {
        // 単一IPの場合
        if ($ip === $range) {
            return true;
        }

        // CIDR記法の場合
        if (strpos($range, '/') !== false) {
            list($subnet, $mask) = explode('/', $range);
            $subnet = ip2long($subnet);
            $ip = ip2long($ip);
            $mask = -1 << (32 - $mask);
            
            return ($ip & $mask) === ($subnet & $mask);
        }

        return false;
    }

    /**
     * 管理者アクティビティの更新
     */
    private function updateAdminActivity(Request $request, $user): void
    {
        try {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'admin_last_login_at' => Carbon::now(),
                    'last_active_at' => Carbon::now(),
                    'last_device_type' => $this->detectDeviceType($request),
                    'last_ip_address' => $request->ip(),
                    'updated_at' => Carbon::now(),
                ]);

            // セッションにアクティビティ情報を保存
            session(['admin_last_activity' => time()]);
        } catch (\Exception $e) {
            Log::error('管理者アクティビティ更新エラー', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 2段階認証チェック（将来拡張用）
     */
    private function check2FA(Request $request, $user): bool
    {
        // 2段階認証の実装は将来の拡張で対応
        return session()->has('admin_2fa_verified');
    }

    /**
     * セキュリティイベントのログ記録
     */
    private function logSecurityEvent(Request $request, $user, string $eventType, string $severity = 'medium'): void
    {
        try {
            DB::table('admin_audit_logs')->insert([
                'admin_user_id' => $user->id,
                'admin_email' => $user->email,
                'admin_name' => $user->name,
                'action' => 'security.' . $eventType,
                'action_category' => 'security',
                'description' => $this->getSecurityEventDescription($eventType),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'request_headers' => json_encode($request->headers->all()),
                'status' => 'failed',
                'severity' => $severity,
                'is_security_event' => true,
                'requires_review' => $severity === 'critical',
                'event_uuid' => \Str::uuid(),
                'event_time' => Carbon::now(),
                'tags' => json_encode(['security', 'unauthorized_access']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::critical('セキュリティログ記録失敗', [
                'event_type' => $eventType,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * セキュリティイベントの説明文生成
     */
    private function getSecurityEventDescription(string $eventType): string
    {
        return match($eventType) {
            'unauthorized_admin_access' => '管理者権限を持たないユーザーによる管理者エリアアクセス試行',
            'invalid_admin_account' => '無効な管理者アカウントによるアクセス試行',
            'ip_whitelist_violation' => 'IPホワイトリストに登録されていないIPからのアクセス',
            '2fa_failure' => '2段階認証の失敗',
            default => '不明なセキュリティイベント: ' . $eventType
        };
    }

    /**
     * デバイス種別の検出
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent();
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}
