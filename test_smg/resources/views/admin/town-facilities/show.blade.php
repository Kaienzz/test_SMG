@extends('admin.layouts.app')

@section('title', $facility->name . ' - 詳細')

@section('content')
<div class="container-fluid">
    
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <span class="me-2">{{ $facilityType->getIcon() }}</span>
                {{ $facility->name }}
            </h1>
            <p class="mb-0 text-muted">{{ $facilityType->getDescription() }}</p>
        </div>
        
        <div class="btn-group" role="group">
            <a href="{{ route('admin.town-facilities.edit', $facility) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> 編集
            </a>
            <a href="{{ route('admin.town-facilities.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> 施設一覧
            </a>
        </div>
    </div>

    <!-- 成功メッセージ -->
    @if (session('success'))
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <!-- 左カラム: メイン情報 -->
        <div class="col-lg-8">
            <!-- 基本情報 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        基本情報
                    </h3>
                </div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">施設名</label>
                                <div class="info-value">{{ $facility->name }}</div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <label class="info-label">設置場所</label>
                                <div class="info-value">
                                    <span class="badge badge-secondary me-2">{{ $facility->location_id }}</span>
                                    <span class="text-muted">（{{ $facility->location_type }}）</span>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <label class="info-label">施設タイプ</label>
                                <div class="info-value">
                                    <span class="me-2">{{ $facilityType->getIcon() }}</span>
                                    {{ $facilityType->getDisplayName() }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">稼働状態</label>
                                <div class="info-value">
                                    <span class="badge {{ $facility->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $facility->is_active ? '稼働中' : '停止中' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <label class="info-label">作成日時</label>
                                <div class="info-value">{{ $facility->created_at->format('Y年m月d日 H:i') }}</div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <label class="info-label">最終更新</label>
                                <div class="info-value">{{ $facility->updated_at->format('Y年m月d日 H:i') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    @if ($facility->description)
                        <div class="info-item mt-3">
                            <label class="info-label">説明</label>
                            <div class="info-value">{{ $facility->description }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 販売アイテム一覧（商品系施設のみ） -->
            @if (in_array($facility->facility_type, ['item_shop', 'weapon_shop', 'armor_shop', 'magic_shop']))
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="admin-card-title">
                                <i class="fas fa-shopping-bag me-2"></i>
                                販売アイテム（{{ $facilityItems->count() }}件）
                            </h3>
                            <a href="{{ route('admin.town-facilities.edit', $facility) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-edit me-1"></i> アイテム管理
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        @if ($facilityItems->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>アイテム</th>
                                            <th>販売価格</th>
                                            <th>在庫</th>
                                            <th>状態</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($facilityItems as $facilityItem)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
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
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                <h5>販売アイテムがありません</h5>
                                <p>編集画面からアイテムを追加してください。</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 調合レシピ一覧（調合店のみ） -->
            @if ($facility->facility_type === 'compounding_shop')
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="admin-card-title">
                                <i class="fas fa-flask me-2"></i>
                                利用可能な調合レシピ（{{ $availableRecipes->count() }}件）
                            </h3>
                            <a href="{{ route('admin.town-facilities.edit', $facility) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-edit me-1"></i> レシピ管理
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        @if ($availableRecipes->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>レシピ名</th>
                                            <th>成果物</th>
                                            <th>必要Lv</th>
                                            <th>成功率</th>
                                            <th>SPコスト</th>
                                            <th>材料</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($availableRecipes as $recipe)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $recipe->name }}</div>
                                                    <small class="text-muted">{{ $recipe->recipe_key }}</small>
                                                </td>
                                                <td>
                                                    @if($recipe->productItem)
                                                        <div class="d-flex align-items-center">
                                                            <span class="me-2">📦</span>
                                                            <div>
                                                                <div class="fw-bold">{{ $recipe->productItem->name }}</div>
                                                                <small class="text-muted">× {{ $recipe->product_quantity }}</small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">不明なアイテム (ID: {{ $recipe->product_item_id }})</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">Lv {{ $recipe->required_skill_level }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ $recipe->success_rate }}%</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ $recipe->sp_cost }} SP</span>
                                                </td>
                                                <td>
                                                    @if($recipe->ingredients->count() > 0)
                                                        <div class="recipe-ingredients">
                                                            @foreach($recipe->ingredients as $ingredient)
                                                                <div class="ingredient-item">
                                                                    @if($ingredient->item)
                                                                        <small>{{ $ingredient->item->name }} × {{ $ingredient->quantity }}</small>
                                                                    @else
                                                                        <small class="text-muted">不明 (ID: {{ $ingredient->item_id }}) × {{ $ingredient->quantity }}</small>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <small class="text-muted">材料なし</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-flask fa-3x mb-3"></i>
                                <h5>利用可能な調合レシピがありません</h5>
                                <p>編集画面からレシピを有効化してください。</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 施設設定（サービス系施設） -->
            @if (in_array($facility->facility_type, ['blacksmith', 'alchemy_shop', 'tavern']) && $facility->facility_config)
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-cogs me-2"></i>
                            施設設定
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        @if ($facility->facility_type === 'blacksmith' && isset($facility->facility_config['services']))
                            <h5>鍛冶屋サービス</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="service-status mb-3">
                                        <i class="fas fa-hammer me-2"></i>
                                        <strong>修理サービス</strong>
                                        <span class="badge {{ ($facility->facility_config['services']['repair']['enabled'] ?? false) ? 'badge-success' : 'badge-secondary' }} ms-2">
                                            {{ ($facility->facility_config['services']['repair']['enabled'] ?? false) ? '利用可能' : '無効' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="service-status mb-3">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        <strong>強化サービス</strong>
                                        <span class="badge {{ ($facility->facility_config['services']['enhance']['enabled'] ?? false) ? 'badge-success' : 'badge-secondary' }} ms-2">
                                            {{ ($facility->facility_config['services']['enhance']['enabled'] ?? false) ? '利用可能' : '無効' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="service-status mb-3">
                                        <i class="fas fa-tools me-2"></i>
                                        <strong>分解サービス</strong>
                                        <span class="badge {{ ($facility->facility_config['services']['dismantle']['enabled'] ?? false) ? 'badge-success' : 'badge-secondary' }} ms-2">
                                            {{ ($facility->facility_config['services']['dismantle']['enabled'] ?? false) ? '利用可能' : '無効' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @elseif ($facility->facility_type === 'alchemy_shop' && isset($facility->facility_config['recipes']))
                            <h5>錬金術レシピ</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="service-status mb-3">
                                        <i class="fas fa-flask me-2"></i>
                                        <strong>ポーション作成</strong>
                                        <span class="badge {{ ($facility->facility_config['recipes']['potion_crafting'] ?? false) ? 'badge-success' : 'badge-secondary' }} ms-2">
                                            {{ ($facility->facility_config['recipes']['potion_crafting'] ?? false) ? '利用可能' : '無効' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="service-status mb-3">
                                        <i class="fas fa-magic me-2"></i>
                                        <strong>武器強化</strong>
                                        <span class="badge {{ ($facility->facility_config['recipes']['weapon_enhancement'] ?? false) ? 'badge-success' : 'badge-secondary' }} ms-2">
                                            {{ ($facility->facility_config['recipes']['weapon_enhancement'] ?? false) ? '利用可能' : '無効' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="service-status mb-3">
                                        <i class="fas fa-atom me-2"></i>
                                        <strong>素材合成</strong>
                                        <span class="badge {{ ($facility->facility_config['recipes']['material_synthesis'] ?? false) ? 'badge-success' : 'badge-secondary' }} ms-2">
                                            {{ ($facility->facility_config['recipes']['material_synthesis'] ?? false) ? '利用可能' : '無効' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-cog fa-2x mb-2"></i>
                                <p>設定情報がありません</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- 右カラム: サイド情報 -->
        <div class="col-lg-4">
            <!-- クイック統計 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-chart-bar me-2"></i>
                        統計情報
                    </h3>
                </div>
                <div class="admin-card-body">
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>販売アイテム数</span>
                            <span class="badge badge-info">{{ $facilityItems->count() }}</span>
                        </div>
                    </div>
                    
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>販売中アイテム</span>
                            <span class="badge badge-success">{{ $facilityItems->where('is_available', true)->count() }}</span>
                        </div>
                    </div>
                    
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>停止中アイテム</span>
                            <span class="badge badge-secondary">{{ $facilityItems->where('is_available', false)->count() }}</span>
                        </div>
                    </div>
                    
                    @if ($facility->facility_type === 'compounding_shop')
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>利用可能レシピ</span>
                                <span class="badge badge-info">{{ $availableRecipes->count() }}</span>
                            </div>
                        </div>
                    @endif
                    
                    @if ($facilityItems->count() > 0)
                        <div class="stat-item mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>平均価格</span>
                                <span class="fw-bold">{{ number_format($facilityItems->avg('price')) }}G</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- クイック操作 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-tools me-2"></i>
                        クイック操作
                    </h3>
                </div>
                <div class="admin-card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.town-facilities.edit', $facility) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> 施設を編集
                        </a>
                        
                        @if ($facility->is_active)
                            <form method="POST" action="{{ route('admin.town-facilities.update', $facility) }}" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="0">
                                <input type="hidden" name="name" value="{{ $facility->name }}">
                                <input type="hidden" name="facility_type" value="{{ $facility->facility_type }}">
                                <input type="hidden" name="location_id" value="{{ $facility->location_id }}">
                                <input type="hidden" name="location_type" value="{{ $facility->location_type }}">
                                <input type="hidden" name="description" value="{{ $facility->description }}">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-pause me-1"></i> 施設を停止
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.town-facilities.update', $facility) }}" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="1">
                                <input type="hidden" name="name" value="{{ $facility->name }}">
                                <input type="hidden" name="facility_type" value="{{ $facility->facility_type }}">
                                <input type="hidden" name="location_id" value="{{ $facility->location_id }}">
                                <input type="hidden" name="location_type" value="{{ $facility->location_type }}">
                                <input type="hidden" name="description" value="{{ $facility->description }}">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-play me-1"></i> 施設を稼働
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 施設情報 -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        施設について
                    </h3>
                </div>
                <div class="admin-card-body">
                    <p class="mb-3">{{ $facilityType->getDescription() }}</p>
                    
                    <div class="facility-type-info">
                        <h6>このタイプの施設では:</h6>
                        <ul class="mb-0">
                            @if (in_array($facility->facility_type, ['item_shop', 'weapon_shop', 'armor_shop', 'magic_shop']))
                                <li>アイテムの販売</li>
                                <li>在庫管理</li>
                                <li>価格設定</li>
                            @elseif ($facility->facility_type === 'blacksmith')
                                <li>武器・防具の修理</li>
                                <li>装備の強化</li>
                                <li>アイテムの分解</li>
                            @elseif ($facility->facility_type === 'alchemy_shop')
                                <li>ポーション作成</li>
                                <li>武器の錬金強化</li>
                                <li>素材の合成</li>
                            @elseif ($facility->facility_type === 'tavern')
                                <li>HP・MP・SPの回復</li>
                                <li>休息サービス</li>
                            @elseif ($facility->facility_type === 'compounding_shop')
                                <li>調合レシピの管理</li>
                                <li>材料から消耗品を作成</li>
                                <li>レシピの有効/無効切り替え</li>
                            @else
                                <li>専用サービスの提供</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-label {
    font-weight: 600;
    color: var(--admin-secondary);
    font-size: 0.875rem;
    display: block;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 500;
    color: var(--admin-dark);
}

.info-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--admin-border);
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.stat-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--admin-border);
}

.stat-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.service-status {
    padding: 0.5rem;
    background-color: var(--admin-bg);
    border-radius: 0.25rem;
    border: 1px solid var(--admin-border);
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.badge-success { background-color: var(--admin-success); color: white; }
.badge-secondary { background-color: var(--admin-secondary); color: white; }
.badge-info { background-color: var(--admin-info); color: white; }
.badge-warning { background-color: var(--admin-warning); color: white; }
.badge-danger { background-color: var(--admin-danger); color: white; }

.facility-type-info ul {
    list-style-type: disc;
    margin-left: 1.25rem;
}

.facility-type-info li {
    margin-bottom: 0.25rem;
    color: var(--admin-secondary);
}

.recipe-ingredients {
    max-width: 200px;
}

.ingredient-item {
    margin-bottom: 0.125rem;
    line-height: 1.2;
}
</style>
@endsection