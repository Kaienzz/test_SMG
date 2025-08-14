@extends('admin.layouts.app')

@section('title', 'ショップ管理')
@section('subtitle', 'ゲーム内ショップの商品・価格管理')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">ショップ管理</h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-alert admin-alert-info">
            <strong>開発中</strong><br>
            ショップ管理機能は現在開発中です。この機能では以下の管理が可能になります：
            <ul style="margin: 0.5rem 0 0 1rem;">
                <li>ショップ商品ラインナップの管理</li>
                <li>商品価格・在庫の設定</li>
                <li>期間限定商品・セール管理</li>
                <li>売上・購入履歴の分析</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h4>実装予定機能</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">商品管理</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        商品の追加・編集・削除、カテゴリ別管理
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">価格・在庫管理</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        動的価格設定、在庫補充、一括価格調整
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">売上分析</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        売上統計、人気商品分析、収益最適化
                    </p>
                </div>
                <div style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 6px;">
                    <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-primary);">イベント・セール</h5>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--admin-secondary);">
                        期間限定商品、割引キャンペーンの設定・管理
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <div class="admin-alert admin-alert-warning">
                <strong>注意:</strong> ショップ管理機能の実装は Phase 3 で予定されています。
                アイテム管理機能との連携により、ゲーム内経済バランスの調整が可能になります。
            </div>
        </div>
    </div>
</div>
@endsection