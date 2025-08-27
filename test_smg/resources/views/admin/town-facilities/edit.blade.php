@extends('admin.layouts.app')

@section('title', $facility->name . ' - 編集')

@section('content')
<div class="container-fluid">
    
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <span class="me-2">{{ App\Enums\FacilityType::from($facility->facility_type)->getIcon() }}</span>
                {{ $facility->name }} - 編集
            </h1>
            <p class="mb-0 text-muted">{{ App\Enums\FacilityType::from($facility->facility_type)->getDescription() }}</p>
        </div>
        
        <div class="btn-group" role="group">
            <a href="{{ route('admin.town-facilities.show', $facility) }}" class="btn btn-outline-primary">
                <i class="fas fa-eye me-1"></i> 詳細表示
            </a>
            <a href="{{ route('admin.town-facilities.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> 施設一覧
            </a>
        </div>
    </div>

    <!-- エラー・成功メッセージ -->
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <div class="d-flex">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>
                    <strong>入力エラーがあります。</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <!-- 左カラム: 基本情報編集 -->
        <div class="col-lg-8">
            <!-- 基本情報セクション -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        基本情報
                    </h3>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="{{ route('admin.town-facilities.update', $facility) }}" id="facilityEditForm">
                        @csrf
                        @method('PATCH')
                        
                        <div class="row">
                            <!-- 左側 -->
                            <div class="col-md-6">
                                <!-- 町・施設タイプ（読み取り専用） -->
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        設置場所・タイプ
                                    </label>
                                    <div class="admin-readonly-field">
                                        <div class="d-flex align-items-center">
                                            <span class="badge badge-secondary me-2">{{ $facility->location_id }}</span>
                                            <span class="me-3">{{ App\Enums\FacilityType::from($facility->facility_type)->getDisplayName() }}</span>
                                            <span class="text-muted">（変更不可）</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 施設名 -->
                                <div class="form-group mb-3">
                                    <label for="name" class="form-label required">
                                        <i class="fas fa-tag me-1"></i>
                                        施設名
                                    </label>
                                    <input type="text" name="name" id="name" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name', $facility->name) }}"
                                           maxlength="255" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- 稼働状態 -->
                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" id="is_active" 
                                               class="form-check-input @error('is_active') is-invalid @enderror" 
                                               value="1"
                                               {{ old('is_active', $facility->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            <i class="fas fa-power-off me-1"></i>
                                            施設を稼働状態にする
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- 右側 -->
                            <div class="col-md-6">
                                <!-- 施設の説明 -->
                                <div class="form-group mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-info-circle me-1"></i>
                                        施設の説明
                                    </label>
                                    <textarea name="description" id="description" 
                                              class="admin-textarea @error('description') is-invalid @enderror" 
                                              rows="4" 
                                              maxlength="1000">{{ old('description', $facility->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- 隠しフィールド -->
                        <input type="hidden" name="facility_type" value="{{ $facility->facility_type }}">
                        <input type="hidden" name="location_id" value="{{ $facility->location_id }}">
                        <input type="hidden" name="location_type" value="{{ $facility->location_type }}">

                        <!-- 保存ボタン -->
                        <div class="d-flex justify-content-end pt-3" style="border-top: 1px solid var(--admin-border);">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> 基本情報を保存
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 販売アイテム管理セクション（商品系施設のみ） -->
            @if (in_array($facility->facility_type, ['item_shop', 'weapon_shop', 'armor_shop', 'magic_shop']))
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="admin-card-title">
                                <i class="fas fa-shopping-bag me-2"></i>
                                販売アイテム管理
                            </h3>
                            <button type="button" class="btn btn-success btn-sm" 
                                    onclick="showAddItemModal()">
                                <i class="fas fa-plus me-1"></i> アイテム追加
                            </button>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <!-- 現在の販売アイテム一覧 -->
                        <div id="facilityItemsList">
                            @if ($facility->facilityItems->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>アイテム</th>
                                                <th>販売価格</th>
                                                <th>在庫</th>
                                                <th>状態</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($facility->facilityItems as $facilityItem)
                                                <tr id="item-row-{{ $facilityItem->id }}">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <!-- アイテムアイコンがあれば表示 -->
                                                            <span class="me-2">📦</span>
                                                            <div>
                                                                <div class="fw-bold">{{ $facilityItem->item_name ?? '不明なアイテム' }}</div>
                                                                <small class="text-muted">ID: {{ $facilityItem->item_id }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold">{{ number_format($facilityItem->price) }}G</span>
                                                    </td>
                                                    <td>
                                                        @if ($facilityItem->stock === -1)
                                                            <span class="badge badge-success">無限</span>
                                                        @else
                                                            <span class="badge badge-info">{{ number_format($facilityItem->stock) }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $facilityItem->is_available ? 'badge-success' : 'badge-secondary' }}">
                                                            {{ $facilityItem->is_available ? '販売中' : '停止中' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                    onclick="editFacilityItem({{ $facilityItem->id }})">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm" 
                                                                    onclick="deleteFacilityItem({{ $facilityItem->id }})">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                    <h5>販売アイテムがありません</h5>
                                    <p>「アイテム追加」ボタンから商品を追加してください。</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- サービス系施設設定（鍛冶屋・錬金屋等） -->
            @if (in_array($facility->facility_type, ['blacksmith', 'alchemy_shop']))
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-cogs me-2"></i>
                            サービス設定
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="{{ route('admin.town-facilities.update-config', $facility) }}" id="serviceConfigForm">
                            @csrf
                            @method('PATCH')
                            
                            @if ($facility->facility_type === 'blacksmith')
                                <!-- 鍛冶屋設定 -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="config[services][repair][enabled]" id="repair_enabled" 
                                                   class="form-check-input" value="1"
                                                   {{ ($facility->facility_config['services']['repair']['enabled'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="repair_enabled">
                                                <i class="fas fa-hammer me-1"></i> 修理サービス
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="config[services][enhance][enabled]" id="enhance_enabled" 
                                                   class="form-check-input" value="1"
                                                   {{ ($facility->facility_config['services']['enhance']['enabled'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enhance_enabled">
                                                <i class="fas fa-plus-circle me-1"></i> 強化サービス
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="config[services][dismantle][enabled]" id="dismantle_enabled" 
                                                   class="form-check-input" value="1"
                                                   {{ ($facility->facility_config['services']['dismantle']['enabled'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="dismantle_enabled">
                                                <i class="fas fa-tools me-1"></i> 分解サービス
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($facility->facility_type === 'alchemy_shop')
                                <!-- 錬金屋設定 -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="config[recipes][potion_crafting]" id="potion_crafting" 
                                                   class="form-check-input" value="1"
                                                   {{ ($facility->facility_config['recipes']['potion_crafting'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="potion_crafting">
                                                <i class="fas fa-flask me-1"></i> ポーション作成
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="config[recipes][weapon_enhancement]" id="weapon_enhancement" 
                                                   class="form-check-input" value="1"
                                                   {{ ($facility->facility_config['recipes']['weapon_enhancement'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="weapon_enhancement">
                                                <i class="fas fa-magic me-1"></i> 武器強化
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="config[recipes][material_synthesis]" id="material_synthesis" 
                                                   class="form-check-input" value="1"
                                                   {{ ($facility->facility_config['recipes']['material_synthesis'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="material_synthesis">
                                                <i class="fas fa-atom me-1"></i> 素材合成
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="d-flex justify-content-end pt-3" style="border-top: 1px solid var(--admin-border);">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> サービス設定を保存
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        </div>

        <!-- 右カラム: 情報・統計 -->
        <div class="col-lg-4">
            <!-- 施設ステータス -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-chart-bar me-2"></i>
                        施設ステータス
                    </h3>
                </div>
                <div class="admin-card-body">
                    <div class="status-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>稼働状態</span>
                            <span class="badge {{ $facility->is_active ? 'badge-success' : 'badge-secondary' }}">
                                {{ $facility->is_active ? '稼働中' : '停止中' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="status-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>販売アイテム数</span>
                            <span class="badge badge-info">{{ $facility->facilityItems->count() }}</span>
                        </div>
                    </div>
                    
                    <div class="status-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>作成日</span>
                            <small class="text-muted">{{ $facility->created_at->format('Y/m/d') }}</small>
                        </div>
                    </div>
                    
                    <div class="status-item mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>最終更新</span>
                            <small class="text-muted">{{ $facility->updated_at->format('Y/m/d H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 危険な操作 -->
            <div class="admin-card border-danger">
                <div class="admin-card-header bg-danger text-white">
                    <h3 class="admin-card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        危険な操作
                    </h3>
                </div>
                <div class="admin-card-body">
                    <p class="text-muted mb-3">この施設を完全に削除します。この操作は元に戻せません。</p>
                    
                    <form method="POST" action="{{ route('admin.town-facilities.destroy', $facility) }}" 
                          onsubmit="return confirm('本当にこの施設を削除しますか？この操作は元に戻せません。')">
                        @csrf
                        @method('DELETE')
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash me-1"></i> 施設を削除
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.required::after {
    content: ' *';
    color: var(--admin-danger);
}

.admin-readonly-field {
    padding: 0.5rem;
    background-color: var(--admin-bg);
    border: 1px solid var(--admin-border);
    border-radius: 0.25rem;
    color: var(--admin-secondary);
}

.status-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--admin-border);
}

.status-item:last-child {
    border-bottom: none;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.badge-success { background-color: var(--admin-success); color: white; }
.badge-secondary { background-color: var(--admin-secondary); color: white; }
.badge-info { background-color: var(--admin-info); color: white; }
.badge-danger { background-color: var(--admin-danger); color: white; }
</style>

<!-- JavaScript for item management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // アイテム追加モーダル表示
    window.showAddItemModal = function() {
        // TODO: アイテム選択モーダルの実装
        alert('アイテム追加モーダルを実装中です');
    };
    
    // アイテム編集
    window.editFacilityItem = function(itemId) {
        // TODO: インライン編集またはモーダル編集の実装
        alert('アイテム編集機能を実装中です（アイテムID: ' + itemId + '）');
    };
    
    // アイテム削除
    window.deleteFacilityItem = function(itemId) {
        if (confirm('このアイテムを販売リストから削除しますか？')) {
            // TODO: Ajax削除の実装
            alert('アイテム削除機能を実装中です（アイテムID: ' + itemId + '）');
        }
    };
});
</script>
@endsection