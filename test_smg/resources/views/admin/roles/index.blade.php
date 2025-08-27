@extends('admin.layouts.app')

@section('title', 'ロール・権限管理')
@section('subtitle', '管理者ロールと権限の設定・管理')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">ロール・権限管理</h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-alert admin-alert-info">
            <strong>開発中</strong><br>
            ロール・権限管理機能は現在開発中です。この機能では以下の管理が可能になります：
            <ul style="margin: 0.5rem 0 0 1rem;">
                <li>管理者ロールの作成・編集・削除</li>
                <li>権限の詳細設定と組み合わせ</li>
                <li>ユーザーへのロール割り当て</li>
                <li>権限継承とセキュリティ制御</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>現在のロール構成</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #fef2f2;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #dc2626;">Super Admin</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: #7f1d1d;">
                        全権限を持つ最高管理者。システム全体の完全な制御が可能。
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #fef3c7;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #d97706;">Admin</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: #92400e;">
                        ユーザー・データ管理権限。日常的な管理業務を担当。
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #dbeafe;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #2563eb;">Moderator</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: #1e40af;">
                        コンテンツ・ユーザー監視。制限的な管理権限。
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #f0fdf4;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #16a34a;">Game Designer</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: #15803d;">
                        ゲームデータ・バランス調整専用。アイテム・モンスター管理。
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px; background: #f5f3ff;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #7c3aed;">Analyst</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: #6b21a8;">
                        分析・監視専用。データアクセスは読み取り権限のみ。
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>権限カテゴリ</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">ユーザー管理</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        users.view, users.edit, users.suspend
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">ゲームデータ</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        items.*, monsters.*, town_facilities.*
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">分析・監視</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        analytics.view, audit.view
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">システム管理</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        system.*, admin.roles
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <div style="background: #f0fdf4; border: 1px solid #10b981; border-radius: 6px; padding: 1rem;">
                <h5 style="margin: 0 0 0.5rem 0; color: #065f46;">権限システム構築済み</h5>
                <p style="margin: 0; color: #065f46; font-size: 0.875rem;">
                    権限管理の基盤システム（AdminPermissionService, AdminAuditService）は実装済みです。
                    現在、26個の詳細権限と階層ロールが運用中です。
                </p>
            </div>
        </div>
        
        <div style="margin-top: 1rem;">
            <div class="admin-alert admin-alert-warning">
                <strong>実装待ち:</strong> ロール・権限の管理インターフェースは現在開発中です。
                現在の権限設定はデータベース内で管理されており、AdminSystemSeeder で初期設定済みです。
            </div>
        </div>
    </div>
</div>
@endsection