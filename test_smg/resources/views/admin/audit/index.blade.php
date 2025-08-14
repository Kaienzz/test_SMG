@extends('admin.layouts.app')

@section('title', '監査ログ')
@section('subtitle', '管理者操作・システムイベントの記録')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">監査ログ</h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-alert admin-alert-info">
            <strong>開発中</strong><br>
            監査ログ機能は現在開発中です。この機能では以下の記録・監視が可能になります：
            <ul style="margin: 0.5rem 0 0 1rem;">
                <li>管理者による全ての操作ログ</li>
                <li>システムの重要なイベント記録</li>
                <li>セキュリティ関連の活動監視</li>
                <li>データ変更履歴の追跡</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>実装予定の監査機能</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">操作ログ</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        管理者によるユーザー・データ変更の詳細記録
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">セキュリティログ</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        ログイン失敗、不正アクセス試行の記録
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">システムイベント</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        エラー、例外、重要なシステム状態変化
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">検索・フィルタ</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        日時、操作者、操作種別による絞り込み検索
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>監査ログシステムの現状</h4>
            <div style="background: #f0fdf4; border: 1px solid #10b981; border-radius: 6px; padding: 1rem; margin-top: 1rem;">
                <h5 style="margin: 0 0 0.5rem 0; color: #065f46;">基盤システム構築済み</h5>
                <p style="margin: 0; color: #065f46; font-size: 0.875rem;">
                    監査ログのデータベーステーブル（admin_audit_logs）とサービス層（AdminAuditService）は実装済みです。
                    現在、管理者の操作は自動的に記録されています。
                </p>
            </div>
        </div>
        
        <div style="margin-top: 1rem;">
            <div class="admin-alert admin-alert-warning">
                <strong>実装待ち:</strong> ログの表示・検索インターフェースは現在開発中です。
                データベース内の監査ログは既に蓄積されており、インターフェース完成後に過去のログも確認可能になります。
            </div>
        </div>
    </div>
</div>
@endsection