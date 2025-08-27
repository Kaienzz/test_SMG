@extends('admin.layouts.app')

@section('title', '採集設定作成')
@section('subtitle', '新しい採集設定を作成します')

@section('content')
<div class="admin-content">
    {{-- ページヘッダー --}}
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <span class="page-icon">🌿</span>
                新しい採集設定
            </h1>
            <p class="page-description">ルートとアイテムの採集可能設定を作成します</p>
        </div>
        
        <div class="page-actions">
            <a href="{{ route('admin.gathering.index') }}" class="admin-btn admin-btn-secondary">
                <span class="btn-icon">↩️</span>
                採集管理に戻る
            </a>
        </div>
    </div>

    {{-- 作成フォーム --}}
    <div class="content-card">
        <div class="content-card-header">
            <h3>採集設定情報</h3>
            <div class="content-card-meta">
                必須項目は<span class="text-required">*</span>で表示されています
            </div>
        </div>
        <div class="content-card-body">
            <form method="POST" action="{{ route('admin.gathering.store') }}" class="admin-form">
                @csrf
                
                <div class="form-grid form-grid-2">
                    {{-- ルート選択 --}}
                    <div class="form-group">
                        <label for="route_id" class="form-label required">対象ルート *</label>
                        <select name="route_id" id="route_id" class="form-control @error('route_id') is-invalid @enderror" required>
                            <option value="">ルートを選択してください</option>
                            @foreach($routes as $route)
                            <option value="{{ $route->id }}" {{ old('route_id') === $route->id ? 'selected' : '' }}
                                    data-category="{{ $route->category }}"
                                    data-min-level="{{ $route->min_level }}"
                                    data-max-level="{{ $route->max_level }}">
                                [{{ $route->category === 'road' ? '道路' : 'ダンジョン' }}] {{ $route->name }}
                                @if($route->min_level) (Lv.{{ $route->min_level }}-{{ $route->max_level ?? '∞' }}) @endif
                            </option>
                            @endforeach
                        </select>
                        @error('route_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-help">採集を行うルート（道路またはダンジョン）を選択します</small>
                    </div>

                    {{-- アイテム選択 --}}
                    <div class="form-group">
                        <label for="item_id" class="form-label required">採集アイテム *</label>
                        <select name="item_id" id="item_id" class="form-control @error('item_id') is-invalid @enderror" required>
                            <option value="">アイテムを選択してください</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ old('item_id') == $item->id ? 'selected' : '' }}
                                    data-category="{{ $item->getCategoryName() }}">
                                {{ $item->name }} ({{ $item->getCategoryName() }})
                            </option>
                            @endforeach
                        </select>
                        @error('item_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-help">採集できるアイテムを選択します</small>
                    </div>
                </div>

                {{-- 選択されたルート情報表示 --}}
                <div id="route-info" class="alert alert-info" style="display: none;">
                    <h5>選択ルート情報</h5>
                    <div id="route-details"></div>
                </div>

                <div class="form-grid form-grid-2">
                    {{-- 必要スキルレベル --}}
                    <div class="form-group">
                        <label for="required_skill_level" class="form-label required">必要スキルレベル *</label>
                        <input type="number" name="required_skill_level" id="required_skill_level" 
                               class="form-control @error('required_skill_level') is-invalid @enderror" 
                               value="{{ old('required_skill_level', 1) }}" min="1" max="100" required>
                        @error('required_skill_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-help">採集に必要なスキルレベル（1-100）</small>
                    </div>

                    {{-- 基本成功率 --}}
                    <div class="form-group">
                        <label for="success_rate" class="form-label required">基本成功率 *</label>
                        <div class="input-group">
                            <input type="number" name="success_rate" id="success_rate" 
                                   class="form-control @error('success_rate') is-invalid @enderror" 
                                   value="{{ old('success_rate', 70) }}" min="1" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                        @error('success_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-help">スキルボーナス適用前の基本成功率（1-100%）</small>
                        <div class="success-rate-preview">
                            <div class="rate-bar">
                                <div class="rate-fill" id="rate-preview" style="width: 70%"></div>
                            </div>
                            <span id="rate-text">70%</span>
                        </div>
                    </div>
                </div>

                <div class="form-grid form-grid-2">
                    {{-- 最小数量 --}}
                    <div class="form-group">
                        <label for="quantity_min" class="form-label required">最小数量 *</label>
                        <input type="number" name="quantity_min" id="quantity_min" 
                               class="form-control @error('quantity_min') is-invalid @enderror" 
                               value="{{ old('quantity_min', 1) }}" min="1" required>
                        @error('quantity_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-help">採集成功時の最小獲得数量</small>
                    </div>

                    {{-- 最大数量 --}}
                    <div class="form-group">
                        <label for="quantity_max" class="form-label required">最大数量 *</label>
                        <input type="number" name="quantity_max" id="quantity_max" 
                               class="form-control @error('quantity_max') is-invalid @enderror" 
                               value="{{ old('quantity_max', 2) }}" min="1" required>
                        @error('quantity_max')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-help">採集成功時の最大獲得数量</small>
                    </div>
                </div>

                <div class="form-grid form-grid-1">
                    {{-- アクティブ状態 --}}
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" 
                                   value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label for="is_active" class="form-check-label">
                                アクティブ状態で作成
                            </label>
                        </div>
                        <small class="form-help">チェックを外すと非アクティブ状態で作成されます</small>
                    </div>
                </div>

                {{-- フォームアクション --}}
                <div class="form-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <span class="btn-icon">💾</span>
                        採集設定を作成
                    </button>
                    <a href="{{ route('admin.gathering.index') }}" class="admin-btn admin-btn-secondary">
                        <span class="btn-icon">❌</span>
                        キャンセル
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ヘルプセクション --}}
    <div class="content-card">
        <div class="content-card-header">
            <h3>設定ガイド</h3>
        </div>
        <div class="content-card-body">
            <div class="help-grid">
                <div class="help-item">
                    <h5>🗺️ ルート選択について</h5>
                    <ul>
                        <li><strong>道路</strong>: 通常の移動ルートでの採集</li>
                        <li><strong>ダンジョン</strong>: ダンジョン内での採集（レベル制限あり）</li>
                        <li>同じルートに同じアイテムは設定できません</li>
                    </ul>
                </div>
                <div class="help-item">
                    <h5>📊 成功率・スキル計算</h5>
                    <ul>
                        <li>実際の成功率 = 基本成功率 + スキルボーナス</li>
                        <li>スキルボーナス = (プレイヤースキルLv - 必要スキルLv) × 5%</li>
                        <li>最大成功率は100%まで</li>
                    </ul>
                </div>
                <div class="help-item">
                    <h5>📦 数量設定について</h5>
                    <ul>
                        <li>最小数量 ≤ 最大数量 である必要があります</li>
                        <li>実際の獲得数量はこの範囲でランダム決定</li>
                        <li>同じ値にすると固定数量になります</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.form-grid-1 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: bold;
    color: #333;
}

.form-label.required::after,
.required {
    color: #dc3545;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 0.375rem;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 13px;
    margin-top: 0.25rem;
}

.form-help {
    color: #666;
    font-size: 12px;
}

.input-group {
    display: flex;
}

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-left: none;
    padding: 0.75rem;
    border-radius: 0 0.375rem 0.375rem 0;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-check-input {
    margin: 0;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid #e0e0e0;
    margin-top: 2rem;
}

.success-rate-preview {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.rate-bar {
    flex: 1;
    height: 6px;
    background-color: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
}

.rate-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff4444 0%, #ffaa00 50%, #44ff44 100%);
    transition: width 0.3s ease;
}

#rate-text {
    font-weight: bold;
    min-width: 40px;
}

.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.help-item {
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 0.375rem;
    background-color: #f8f9fa;
}

.help-item h5 {
    margin-bottom: 0.75rem;
    color: #495057;
}

.help-item ul {
    margin: 0;
    padding-left: 1.25rem;
}

.help-item li {
    margin-bottom: 0.25rem;
    color: #666;
    font-size: 13px;
}

.text-required {
    color: #dc3545;
    font-weight: bold;
}

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    border: 1px solid transparent;
    margin: 1rem 0;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 成功率プレビュー更新
    const successRateInput = document.getElementById('success_rate');
    const ratePreview = document.getElementById('rate-preview');
    const rateText = document.getElementById('rate-text');
    
    function updateRatePreview() {
        const rate = parseInt(successRateInput.value) || 0;
        ratePreview.style.width = rate + '%';
        rateText.textContent = rate + '%';
    }
    
    successRateInput.addEventListener('input', updateRatePreview);
    
    // ルート選択時の情報表示
    const routeSelect = document.getElementById('route_id');
    const routeInfo = document.getElementById('route-info');
    const routeDetails = document.getElementById('route-details');
    
    routeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const category = selectedOption.dataset.category;
            const minLevel = selectedOption.dataset.minLevel;
            const maxLevel = selectedOption.dataset.maxLevel;
            
            let detailsHtml = `
                <p><strong>環境:</strong> ${category === 'road' ? '道路' : 'ダンジョン'}</p>
            `;
            
            if (minLevel) {
                detailsHtml += `
                    <p><strong>推奨レベル:</strong> Lv.${minLevel}-${maxLevel || '∞'}</p>
                `;
                
                if (category === 'dungeon') {
                    detailsHtml += `
                        <div class="alert alert-warning" style="margin-top: 0.5rem; padding: 0.5rem;">
                            <strong>⚠️ ダンジョン注意:</strong> プレイヤーレベルが推奨レベル未満の場合は採集できません
                        </div>
                    `;
                }
            }
            
            routeDetails.innerHTML = detailsHtml;
            routeInfo.style.display = 'block';
        } else {
            routeInfo.style.display = 'none';
        }
    });
    
    // 数量の妥当性チェック
    const quantityMinInput = document.getElementById('quantity_min');
    const quantityMaxInput = document.getElementById('quantity_max');
    
    function validateQuantities() {
        const min = parseInt(quantityMinInput.value) || 1;
        const max = parseInt(quantityMaxInput.value) || 1;
        
        if (min > max) {
            quantityMaxInput.setCustomValidity('最大数量は最小数量以上である必要があります');
        } else {
            quantityMaxInput.setCustomValidity('');
        }
    }
    
    quantityMinInput.addEventListener('input', validateQuantities);
    quantityMaxInput.addEventListener('input', validateQuantities);
    
    // フォーム送信前の最終チェック
    const form = document.querySelector('.admin-form');
    form.addEventListener('submit', function(e) {
        validateQuantities();
        
        // ブラウザのデフォルトバリデーションに任せる
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
});
</script>
@endsection