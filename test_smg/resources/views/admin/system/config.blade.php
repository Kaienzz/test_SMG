@extends('admin.layouts.app')

@section('title', 'システム設定')
@section('subtitle', 'ゲーム全体の設定・パラメータ管理')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">システム設定</h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-alert admin-alert-info">
            <strong>開発中</strong><br>
            システム設定機能は現在開発中です。この機能では以下の設定が可能になります：
            <ul style="margin: 0.5rem 0 0 1rem;">
                <li>ゲーム全体のパラメータ調整</li>
                <li>経験値・ドロップ率の設定</li>
                <li>メンテナンスモードの管理</li>
                <li>システムメッセージの管理</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>実装予定の設定項目</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">ゲームバランス</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        経験値倍率、ドロップ率、戦闘難易度の調整
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">システム制御</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        メンテナンスモード、新規登録停止、機能制限
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">メッセージ管理</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        お知らせ、警告メッセージ、システム通知
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">セキュリティ設定</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        IPアクセス制限、セッション管理、監査設定
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>現在のシステム状態</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; background: #f0fdf4; border: 1px solid #10b981; border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #065f46;">運用状態</h5>
                    <p style="margin: 0; color: #065f46; font-weight: 600;">正常稼働中</p>
                </div>
                <div style="padding: 1rem; background: #eff6ff; border: 1px solid #2563eb; border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #1e40af;">Laravel バージョン</h5>
                    <p style="margin: 0; color: #1e40af; font-weight: 600;">{{ app()->version() }}</p>
                </div>
                <div style="padding: 1rem; background: #fefce8; border: 1px solid #eab308; border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #a16207;">環境</h5>
                    <p style="margin: 0; color: #a16207; font-weight: 600;">{{ app()->environment() }}</p>
                </div>
                <div style="padding: 1rem; background: #f3f4f6; border: 1px solid #6b7280; border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #374151;">デバッグモード</h5>
                    <p style="margin: 0; color: #374151; font-weight: 600;">{{ config('app.debug') ? '有効' : '無効' }}</p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <div class="admin-alert admin-alert-warning">
                <strong>重要:</strong> システム設定の変更は慎重に行ってください。
                設定変更前には必ずバックアップを取得し、変更内容を記録することを推奨します。
            </div>
        </div>
    </div>
</div>
@endsection