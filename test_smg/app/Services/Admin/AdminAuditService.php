<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * 管理者監査ログサービス
 * 全ての管理者操作の詳細記録と分析機能
 */
class AdminAuditService
{
    /**
     * 管理者操作をログに記録
     */
    public function logAction(
        string $action,
        string $description,
        array $options = []
    ): string {
        $user = Auth::user();
        $request = request();

        $logData = [
            'admin_user_id' => $user->id,
            'admin_email' => $user->email,
            'admin_name' => $user->name,
            'action' => $action,
            'action_category' => $options['category'] ?? $this->extractCategory($action),
            'description' => $description,
            'resource_type' => $options['resource_type'] ?? null,
            'resource_id' => $options['resource_id'] ?? null,
            'resource_data' => isset($options['resource_data']) ? json_encode($options['resource_data']) : null,
            'old_values' => isset($options['old_values']) ? json_encode($options['old_values']) : null,
            'new_values' => isset($options['new_values']) ? json_encode($options['new_values']) : null,
            'request_data' => json_encode($this->sanitizeRequestData($request)),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'request_headers' => json_encode($request->headers->all()),
            'status' => $options['status'] ?? 'success',
            'error_message' => $options['error_message'] ?? null,
            'response_data' => isset($options['response_data']) ? json_encode($options['response_data']) : null,
            'severity' => $options['severity'] ?? $this->determineSeverity($action),
            'is_security_event' => $options['is_security_event'] ?? $this->isSecurityEvent($action),
            'requires_review' => $options['requires_review'] ?? $this->requiresReview($action),
            'event_uuid' => Str::uuid(),
            'event_time' => Carbon::now(),
            'tags' => json_encode($options['tags'] ?? $this->generateTags($action)),
            'batch_id' => $options['batch_id'] ?? null,
            'parent_log_id' => $options['parent_log_id'] ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        DB::table('admin_audit_logs')->insert($logData);

        return $logData['event_uuid'];
    }

    /**
     * リソース変更の詳細ログ
     */
    public function logResourceChange(
        string $resourceType,
        int $resourceId,
        array $oldValues,
        array $newValues,
        string $action = 'update'
    ): string {
        $changes = $this->calculateChanges($oldValues, $newValues);
        
        return $this->logAction(
            "{$resourceType}.{$action}",
            "{$resourceType} ID {$resourceId} の{$action}操作",
            [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'resource_data' => [
                    'changes' => $changes,
                    'changed_fields' => array_keys($changes),
                ],
                'severity' => $this->determineSeverityForChanges($changes),
                'tags' => ['resource_change', strtolower($resourceType), $action],
            ]
        );
    }

    /**
     * 一括操作のログ記録
     */
    public function logBatchOperation(
        string $operation,
        array $targets,
        string $description
    ): string {
        $batchId = Str::uuid();
        
        // メイン操作ログ
        $mainLogUuid = $this->logAction(
            "batch.{$operation}",
            $description,
            [
                'category' => 'batch',
                'resource_data' => [
                    'operation' => $operation,
                    'target_count' => count($targets),
                    'targets' => $targets,
                ],
                'batch_id' => $batchId,
                'severity' => 'high',
                'tags' => ['batch_operation', $operation],
            ]
        );

        // 個別操作ログ
        foreach ($targets as $target) {
            $this->logAction(
                "{$operation}.item",
                "{$operation} 個別実行: {$target['type']} ID {$target['id']}",
                [
                    'resource_type' => $target['type'],
                    'resource_id' => $target['id'],
                    'batch_id' => $batchId,
                    'parent_log_id' => $this->getLogIdByUuid($mainLogUuid),
                    'tags' => ['batch_item', $operation],
                ]
            );
        }

        return $batchId;
    }

    /**
     * セキュリティイベントのログ
     */
    public function logSecurityEvent(
        string $event,
        string $description,
        string $severity = 'high',
        array $additionalData = []
    ): string {
        return $this->logAction(
            "security.{$event}",
            $description,
            array_merge($additionalData, [
                'category' => 'security',
                'severity' => $severity,
                'is_security_event' => true,
                'requires_review' => true,
                'tags' => ['security', $event, 'requires_attention'],
            ])
        );
    }

    /**
     * システム設定変更のログ
     */
    public function logSystemConfigChange(
        string $configKey,
        $oldValue,
        $newValue,
        string $description = null
    ): string {
        return $this->logAction(
            'system.config_change',
            $description ?? "システム設定変更: {$configKey}",
            [
                'category' => 'system',
                'resource_type' => 'SystemConfig',
                'resource_data' => ['config_key' => $configKey],
                'old_values' => ['value' => $oldValue],
                'new_values' => ['value' => $newValue],
                'severity' => 'critical',
                'is_security_event' => true,
                'requires_review' => true,
                'tags' => ['system_config', 'critical_change'],
            ]
        );
    }

    /**
     * 監査ログの検索・フィルタリング
     */
    public function searchLogs(array $filters = [], int $limit = 50): array
    {
        $query = DB::table('admin_audit_logs')
            ->orderBy('event_time', 'desc');

        // フィルタ適用
        if (!empty($filters['user_id'])) {
            $query->where('admin_user_id', $filters['user_id']);
        }

        if (!empty($filters['action_category'])) {
            $query->where('action_category', $filters['action_category']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['is_security_event'])) {
            $query->where('is_security_event', $filters['is_security_event']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('event_time', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('event_time', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('action', 'LIKE', "%{$filters['search']}%");
            });
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * 統計データの取得
     */
    public function getAuditStatistics(array $dateRange = []): array
    {
        $query = DB::table('admin_audit_logs');

        if (!empty($dateRange['from'])) {
            $query->where('event_time', '>=', $dateRange['from']);
        }

        if (!empty($dateRange['to'])) {
            $query->where('event_time', '<=', $dateRange['to']);
        }

        return [
            'total_logs' => $query->count(),
            'by_category' => $query->select('action_category', DB::raw('count(*) as count'))
                ->groupBy('action_category')
                ->get()
                ->pluck('count', 'action_category')
                ->toArray(),
            'by_severity' => $query->select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->get()
                ->pluck('count', 'severity')
                ->toArray(),
            'security_events' => $query->where('is_security_event', true)->count(),
            'failed_operations' => $query->where('status', 'failed')->count(),
            'requires_review' => $query->where('requires_review', true)->count(),
            'top_users' => $query->select('admin_email', DB::raw('count(*) as count'))
                ->groupBy('admin_email')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * 古い監査ログのアーカイブ
     */
    public function archiveOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $archivedCount = DB::table('admin_audit_logs')
            ->where('event_time', '<', $cutoffDate)
            ->whereNull('archived_at')
            ->update([
                'archived_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        if ($archivedCount > 0) {
            $this->logAction(
                'system.archive_logs',
                "古い監査ログをアーカイブしました: {$archivedCount}件",
                [
                    'category' => 'system',
                    'resource_data' => [
                        'archived_count' => $archivedCount,
                        'cutoff_date' => $cutoffDate->toDateString(),
                    ],
                    'severity' => 'medium',
                ]
            );
        }

        return $archivedCount;
    }

    /**
     * 変更差分の計算
     */
    private function calculateChanges(array $oldValues, array $newValues): array
    {
        $changes = [];
        
        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * アクションからカテゴリを抽出
     */
    private function extractCategory(string $action): string
    {
        if (str_contains($action, '.')) {
            return explode('.', $action)[0];
        }
        
        return 'general';
    }

    /**
     * 重要度の自動判定
     */
    private function determineSeverity(string $action): string
    {
        $criticalActions = ['delete', 'suspend', 'ban', 'system', 'security'];
        $highActions = ['create', 'update', 'grant', 'revoke'];
        
        foreach ($criticalActions as $critical) {
            if (str_contains($action, $critical)) {
                return 'critical';
            }
        }
        
        foreach ($highActions as $high) {
            if (str_contains($action, $high)) {
                return 'high';
            }
        }
        
        return 'medium';
    }

    /**
     * 変更内容に基づく重要度判定
     */
    private function determineSeverityForChanges(array $changes): string
    {
        $criticalFields = ['password', 'email', 'is_admin', 'admin_level', 'admin_permissions'];
        
        foreach ($criticalFields as $field) {
            if (isset($changes[$field])) {
                return 'critical';
            }
        }
        
        if (count($changes) > 5) {
            return 'high';
        }
        
        return 'medium';
    }

    /**
     * セキュリティイベントかどうかの判定
     */
    private function isSecurityEvent(string $action): bool
    {
        $securityActions = ['login', 'logout', 'permission', 'security', 'unauthorized', 'failed'];
        
        foreach ($securityActions as $securityAction) {
            if (str_contains($action, $securityAction)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * レビューが必要かどうかの判定
     */
    private function requiresReview(string $action): bool
    {
        $reviewActions = ['delete', 'suspend', 'ban', 'security', 'critical', 'system'];
        
        foreach ($reviewActions as $reviewAction) {
            if (str_contains($action, $reviewAction)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * タグの生成
     */
    private function generateTags(string $action): array
    {
        $tags = [strtolower($action)];
        
        if (str_contains($action, '.')) {
            $parts = explode('.', $action);
            $tags = array_merge($tags, $parts);
        }
        
        return array_unique($tags);
    }

    /**
     * リクエストデータのサニタイズ
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();
        
        // パスワードなどの機密データを除去
        $sensitiveFields = ['password', 'password_confirmation', '_token', 'csrf_token'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }

    /**
     * UUIDからログIDを取得
     */
    private function getLogIdByUuid(string $uuid): ?int
    {
        $log = DB::table('admin_audit_logs')
            ->where('event_uuid', $uuid)
            ->first(['id']);
            
        return $log ? $log->id : null;
    }
}