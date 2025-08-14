@extends('admin.layouts.app')

@section('title', 'プレイヤー管理')
@section('subtitle', 'ゲーム内プレイヤーキャラクターの管理')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">プレイヤー一覧</h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-alert admin-alert-info">
            <strong>開発中</strong><br>
            プレイヤー管理機能は現在開発中です。この機能では以下の管理が可能になります：
            <ul style="margin: 0.5rem 0 0 1rem;">
                <li>プレイヤーキャラクターの詳細情報表示</li>
                <li>レベル・ステータス・アイテム所持状況の確認</li>
                <li>キャラクター進行状況の分析</li>
                <li>異常なプレイヤー活動の検出</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>実装予定機能</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">プレイヤー検索・フィルタ</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        レベル、職業、アクティブ状況などによる絞り込み
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">進行状況管理</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        クエスト進行、スキル習得、アイテム収集状況
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">統計・分析</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        プレイヤー行動パターンの分析とバランス調整
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection