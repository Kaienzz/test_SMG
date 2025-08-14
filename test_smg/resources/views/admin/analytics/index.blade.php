@extends('admin.layouts.app')

@section('title', '分析ダッシュボード')
@section('subtitle', 'ゲーム統計・プレイヤー行動分析')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">分析ダッシュボード</h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-alert admin-alert-info">
            <strong>開発中</strong><br>
            分析ダッシュボードは現在開発中です。この機能では以下の分析が可能になります：
            <ul style="margin: 0.5rem 0 0 1rem;">
                <li>プレイヤー行動パターンの詳細分析</li>
                <li>ゲーム内経済データの可視化</li>
                <li>コンテンツ利用状況の統計</li>
                <li>パフォーマンス指標の監視</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>実装予定の分析機能</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">プレイヤー分析</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        アクティブユーザー数、継続率、チュートリアル完了率
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">コンテンツ分析</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        エリア利用状況、バトル難易度、ドロップ率効果
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">経済分析</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        アイテム流通、価格動向、プレイヤー所持金分布
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">システム分析</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        レスポンス時間、エラー率、サーバー負荷状況
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>基本統計（現在利用可能）</h4>
            <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                <p style="margin: 0; color: var(--admin-secondary);">
                    基本的なシステム統計は<a href="{{ route('admin.dashboard') }}" style="color: var(--admin-primary);">メインダッシュボード</a>で確認できます。
                    より詳細な分析機能は順次実装予定です。
                </p>
            </div>
        </div>
    </div>
</div>
@endsection