@extends('admin.layouts.app')

@section('title', '町施設管理')
@section('subtitle', '各町の施設を管理します')

@section('content')
<div class="admin-content-container">
    
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <span class="me-2">🏢</span>
                町施設管理
            </h1>
            <p class="mb-0 text-muted">各町の施設を管理・監視します</p>
        </div>
        
        <div class="btn-group" role="group">
            @if($canManageFacilities ?? false)
                <a href="{{ route('admin.town-facilities.create') }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus me-1"></i> 新規施設作成
                </a>
            @endif
            <a href="{{ route('admin.locations.index') }}" class="admin-btn admin-btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> ロケーション管理
            </a>
        </div>
    </div>

    <!-- 統計カード -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- 総施設数 -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ $facilities->total() }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">総施設数</div>
                <div style="margin-top: 0.75rem;">
                    <i class="fas fa-building fa-2x" style="color: var(--admin-primary); opacity: 0.7;"></i>
                </div>
            </div>
        </div>
        
        <!-- 町数 -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ $facilitiesByLocation->count() }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">町数</div>
                <div style="margin-top: 0.75rem;">
                    <i class="fas fa-city fa-2x" style="color: var(--admin-success); opacity: 0.7;"></i>
                </div>
            </div>
        </div>

        <!-- 施設タイプ数 -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ $facilitiesByType->count() }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">施設タイプ数</div>
                <div style="margin-top: 0.75rem;">
                    <i class="fas fa-tags fa-2x" style="color: var(--admin-info); opacity: 0.7;"></i>
                </div>
            </div>
        </div>

        <!-- アクティブ施設 -->
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ $locationStats->sum('active_count') }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">稼働中施設</div>
                <div style="margin-top: 0.75rem;">
                    <i class="fas fa-check-circle fa-2x" style="color: var(--admin-warning); opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- フィルター・検索 -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-filter me-2"></i>
                検索・フィルター
            </h3>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.town-facilities.index') }}" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <!-- 町で絞り込み -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">町で絞り込み</label>
                        <select name="location_id" class="admin-select">
                            <option value="">すべての町</option>
                            @foreach($facilitiesByLocation as $locationId => $locationFacilities)
                                <option value="{{ $locationId }}" 
                                    {{ ($filters['location_id'] ?? '') === $locationId ? 'selected' : '' }}>
                                    {{ $locationId }} ({{ $locationFacilities->count() }}施設)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- 施設タイプ -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">施設タイプ</label>
                        <select name="facility_type" class="admin-select">
                            <option value="">すべてのタイプ</option>
                            @foreach($facilityTypes as $facilityType)
                                <option value="{{ $facilityType->value }}" 
                                    {{ ($filters['facility_type'] ?? '') === $facilityType->value ? 'selected' : '' }}>
                                    {{ $facilityType->getIcon() }} {{ $facilityType->getDisplayName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- 名前で検索 -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">施設名で検索</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                               placeholder="施設名を入力..." class="admin-input">
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-search me-1"></i> 検索
                    </button>
                    <a href="{{ route('admin.town-facilities.index') }}" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-undo me-1"></i> リセット
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 町別施設一覧（アコーディオン形式） -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-list-ul me-2"></i>
                町別施設一覧
            </h3>
            <div class="text-muted small">
                クリックして展開/折りたたみ
            </div>
        </div>
        <div class="admin-card-body p-0">
            @if($facilitiesByLocation->count() > 0)
                <div class="town-facilities-accordion" id="townFacilitiesAccordion">
                    @foreach($facilitiesByLocation as $locationId => $locationFacilities)
                        @php
                            $locationStats = $locationStats->where('location_id', $locationId)->first();
                            $activeCount = $locationStats ? $locationStats->active_count : $locationFacilities->where('is_active', true)->count();
                            $totalCount = $locationFacilities->count();
                        @endphp
                        <div class="accordion-item" data-town-id="{{ $locationId }}">
                            <div class="accordion-header">
                                <button class="accordion-toggle {{ $loop->first ? 'active' : '' }}" type="button" 
                                        onclick="toggleAccordion(this)"
                                        data-target="collapse{{ $loop->index }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                    <div class="accordion-header-content">
                                        <div class="location-info">
                                            <i class="fas fa-map-marker-alt accordion-icon"></i>
                                            <div class="location-details">
                                                <strong class="location-name">{{ $locationId }}</strong>
                                                <div class="location-stats-text">
                                                    {{ $totalCount }}施設 | {{ $activeCount }}稼働中
                                                </div>
                                            </div>
                                        </div>
                                        <div class="location-badges">
                                            <span class="admin-badge admin-badge-primary">{{ $totalCount }}施設</span>
                                            <span class="admin-badge admin-badge-success">{{ $activeCount }}稼働中</span>
                                            <i class="fas fa-chevron-down toggle-arrow"></i>
                                        </div>
                                    </div>
                                </button>
                            </div>
                            <div id="collapse{{ $loop->index }}" class="accordion-collapse {{ $loop->first ? 'show' : '' }}">
                                <div class="accordion-body">
                                    @if($locationFacilities->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="padding: 0.75rem 1.5rem; border-top: none;">施設名</th>
                                                        <th style="padding: 0.75rem 1rem; border-top: none;">タイプ</th>
                                                        <th style="padding: 0.75rem 1rem; border-top: none;">状態</th>
                                                        <th style="padding: 0.75rem 1rem; border-top: none;">説明</th>
                                                        <th style="padding: 0.75rem 1.5rem; border-top: none;">操作</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($locationFacilities as $facility)
                                                        @php
                                                            $facilityType = \App\Enums\FacilityType::from($facility->facility_type);
                                                        @endphp
                                                        <tr style="{{ !$facility->is_active ? 'opacity: 0.7;' : '' }}">
                                                            <td style="padding: 1rem 1.5rem;">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="facility-icon me-3" style="font-size: 1.5rem;">
                                                                        {{ $facilityType->getIcon() }}
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-semibold">{{ $facility->name }}</div>
                                                                        <div class="text-muted small">ID: {{ $facility->id }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td style="padding: 1rem;">
                                                                <span class="admin-badge admin-badge-info">
                                                                    {{ $facilityType->getDisplayName() }}
                                                                </span>
                                                            </td>
                                                            <td style="padding: 1rem;">
                                                                @if($facility->is_active)
                                                                    <span class="admin-badge admin-badge-success">
                                                                        <i class="fas fa-check-circle me-1"></i>稼働中
                                                                    </span>
                                                                @else
                                                                    <span class="admin-badge admin-badge-warning">
                                                                        <i class="fas fa-pause-circle me-1"></i>停止中
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td style="padding: 1rem;">
                                                                @if($facility->description)
                                                                    <span class="text-muted small" title="{{ $facility->description }}">
                                                                        {{ Str::limit($facility->description, 40) }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-muted">説明なし</span>
                                                                @endif
                                                            </td>
                                                            <td style="padding: 1rem 1.5rem;">
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <a href="{{ route('admin.town-facilities.show', $facility) }}" 
                                                                       class="admin-btn admin-btn-sm admin-btn-info" title="詳細">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    @if($canManageFacilities ?? false)
                                                                        <a href="{{ route('admin.town-facilities.edit', $facility) }}" 
                                                                           class="admin-btn admin-btn-sm admin-btn-warning" title="編集">
                                                                            <i class="fas fa-edit"></i>
                                                                        </a>
                                                                        <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" 
                                                                                onclick="confirmDelete('{{ $facility->name }}', '{{ route('admin.town-facilities.destroy', $facility) }}')" 
                                                                                title="削除">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-info-circle fa-3x mb-3" style="color: #6c757d; opacity: 0.5;"></i>
                                            <p class="mb-0 text-muted">この町には施設がありません</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x mb-3" style="color: #6c757d; opacity: 0.5;"></i>
                    <h5 class="text-muted">施設が見つかりません</h5>
                    <p class="text-muted mb-3">検索条件を変更するか、新しい施設を作成してください。</p>
                    @if($canManageFacilities ?? false)
                        <a href="{{ route('admin.town-facilities.create') }}" class="admin-btn admin-btn-primary">
                            <i class="fas fa-plus me-1"></i> 新規施設作成
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- 施設タイプ別統計 -->
    <div class="admin-card" style="margin-top: 2rem;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-chart-pie me-2"></i>
                施設タイプ別統計
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                @foreach($facilitiesByType as $typeStats)
                    @php
                        $facilityType = \App\Enums\FacilityType::from($typeStats->facility_type);
                    @endphp
                    <div class="text-center p-3 border rounded" style="border-color: #dee2e6 !important;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">
                            {{ $facilityType->getIcon() }}
                        </div>
                        <div class="fw-semibold">{{ $facilityType->getDisplayName() }}</div>
                        <div class="text-muted small mt-1">
                            総数: {{ $typeStats->count }} | 稼働: {{ $typeStats->active_count }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ページネーション -->
    @if($facilities->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $facilities->withQueryString()->links() }}
        </div>
    @endif

</div>

<!-- 削除確認モーダル -->
<div class="modal" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">施設削除確認</h5>
                <button type="button" class="btn-close" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <p>施設「<span id="facilityName"></span>」を削除しますか？</p>
                <p class="text-danger"><strong>この操作は取り消せません。</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除実行</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* カスタムアコーディオン */
.town-facilities-accordion .accordion-item {
    border: none;
    border-bottom: 1px solid #dee2e6;
}

.accordion-toggle {
    width: 100%;
    border: none;
    background: #f8f9fa;
    padding: 1.25rem 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.accordion-toggle:hover {
    background: #e9ecef;
}

.accordion-toggle.active {
    background-color: var(--admin-primary);
    color: white;
}

.accordion-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.location-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.accordion-icon {
    color: var(--admin-primary);
    font-size: 1.2rem;
}

.accordion-toggle.active .accordion-icon {
    color: white;
}

.location-details .location-name {
    font-size: 1.1rem;
}

.location-stats-text {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.accordion-toggle.active .location-stats-text {
    color: rgba(255, 255, 255, 0.8);
}

.location-badges {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toggle-arrow {
    font-size: 1rem;
    transition: transform 0.2s ease;
    color: #6c757d;
}

.accordion-toggle.active .toggle-arrow {
    transform: rotate(180deg);
    color: white;
}

.accordion-collapse {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-in-out;
}

.accordion-collapse.show {
    max-height: 2000px; /* 十分大きな値に設定 */
}

.accordion-body {
    padding: 0;
}

.admin-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}

.admin-badge-primary { background-color: var(--admin-primary); color: white; }
.admin-badge-success { background-color: var(--admin-success); color: white; }
.admin-badge-info { background-color: var(--admin-info); color: white; }
.admin-badge-warning { background-color: var(--admin-warning); color: white; }
.admin-badge-danger { background-color: var(--admin-danger); color: white; }

/* 統計カードのホバー効果 */
.admin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

/* テーブルの行ホバー効果 */
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.1);
}

/* カスタムモーダル */
.modal {
    display: none;
    position: fixed;
    z-index: 1050;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-dialog {
    max-width: 500px;
    width: 90%;
    margin: auto;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.modal-header {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 1.5rem;
    height: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* レスポンシブ調整 */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 1rem !important;
    }
    
    .accordion-toggle {
        padding: 1rem !important;
        font-size: 0.9rem;
    }
    
    .location-badges {
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-end;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
    }
    
    .accordion-header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .location-badges {
        align-self: flex-end;
    }
}
</style>
@endpush

@push('scripts')
<script>
// 削除確認ダイアログ表示
function confirmDelete(facilityName, deleteUrl) {
    document.getElementById('facilityName').textContent = facilityName;
    document.getElementById('deleteForm').action = deleteUrl;
    
    // カスタムモーダル表示
    showModal('deleteModal');
}

// カスタムモーダル機能
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'block';
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // 背景クリックで閉じる
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal(modalId);
        }
    });
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
}

// アコーディオンのトグル機能
function toggleAccordion(button) {
    const targetId = button.getAttribute('data-target');
    const targetElement = document.getElementById(targetId);
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    
    // 他のアコーディオンを閉じる（オプション：同時に複数開いても良い場合は削除）
    const allToggles = document.querySelectorAll('.accordion-toggle');
    const allCollapses = document.querySelectorAll('.accordion-collapse');
    
    allToggles.forEach(toggle => {
        if (toggle !== button) {
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
    
    allCollapses.forEach(collapse => {
        if (collapse !== targetElement) {
            collapse.classList.remove('show');
        }
    });
    
    // 現在のアコーディオンをトグル
    if (isExpanded) {
        button.classList.remove('active');
        button.setAttribute('aria-expanded', 'false');
        targetElement.classList.remove('show');
    } else {
        button.classList.add('active');
        button.setAttribute('aria-expanded', 'true');
        targetElement.classList.add('show');
    }
    
    // 状態を保存
    const townId = button.closest('.accordion-item').getAttribute('data-town-id');
    localStorage.setItem('townFacility_' + townId, !isExpanded);
}

// ページロード時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // 保存された状態を復元
    const accordionItems = document.querySelectorAll('.accordion-item');
    
    accordionItems.forEach(item => {
        const townId = item.getAttribute('data-town-id');
        const savedState = localStorage.getItem('townFacility_' + townId);
        const toggle = item.querySelector('.accordion-toggle');
        const collapse = item.querySelector('.accordion-collapse');
        
        if (savedState === 'true') {
            toggle.classList.add('active');
            toggle.setAttribute('aria-expanded', 'true');
            collapse.classList.add('show');
        } else if (savedState === 'false') {
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
            collapse.classList.remove('show');
        }
        // savedStateがnullの場合はデフォルト状態（最初のもののみ開く）を維持
    });
    
    // モーダルの閉じるボタン設定
    document.querySelectorAll('.btn-close, .btn-secondary').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                hideModal(modal.id);
            }
        });
    });
});
</script>
@endpush