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

            <!-- サービス系施設設定（鍛冶屋・錬金屋・調合店等） -->
            @if (in_array($facility->facility_type, ['blacksmith', 'alchemy_shop', 'compounding_shop', 'tavern']))
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
                            @elseif ($facility->facility_type === 'compounding_shop')
                                <!-- 調合店設定 -->
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-mortar-pestle me-2"></i>
                                            調合店運営設定
                                        </h6>
                                        <div class="alert alert-info mb-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>調合レシピの管理について</strong><br>
                                            個別のレシピ（材料・成功率・必要レベル等）は
                                            <a href="{{ route('admin.compounding.recipes.index') }}" target="_blank" class="alert-link">
                                                <i class="fas fa-external-link-alt"></i> 調合レシピ管理画面
                                            </a>
                                            で設定・管理してください。<br>
                                            こちらは調合店の基本運営設定のみを行います。
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-check mb-4">
                                                    <input type="checkbox" name="config[auto_learn_skill]" id="auto_learn_skill" 
                                                           class="form-check-input" value="1"
                                                           {{ ($facility->facility_config['auto_learn_skill'] ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="auto_learn_skill">
                                                        <i class="fas fa-graduation-cap me-1"></i> 調合スキル自動習得
                                                    </label>
                                                    <br><small class="text-muted">未習得プレイヤーに調合スキル（生産/調合）を自動付与する</small>
                                                </div>
                                                
                                                <!-- 調合レシピ管理（サービス設定内・簡易版） -->
                                                <div class="service-recipes-section">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <div>
                                                            <h6 class="text-muted mb-1">
                                                                <i class="fas fa-flask me-2"></i>
                                                                利用可能な調合レシピ
                                                            </h6>
                                                            <small class="text-muted">
                                                                現在 <strong>{{ isset($availableRecipes) ? $availableRecipes->count() : 0 }}</strong> 個のレシピが利用可能
                                                            </small>
                                                        </div>
                                                        <div>
                                                            <a href="#recipe-management-section" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-cogs me-1"></i> レシピを管理
                                                            </a>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- 現在有効なレシピの簡易表示 -->
                                                    @if (isset($availableRecipes) && $availableRecipes->count() > 0)
                                                        <div class="current-recipes-summary">
                                                            <div class="row">
                                                                @foreach ($availableRecipes->take(6) as $recipe)
                                                                    <div class="col-md-4 mb-2">
                                                                        <div class="recipe-summary-card">
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <div>
                                                                                    <small class="fw-bold">{{ $recipe->name }}</small>
                                                                                    <br><small class="text-muted">Lv.{{ $recipe->required_skill_level }}</small>
                                                                                </div>
                                                                                <span class="badge badge-success">{{ $recipe->success_rate }}%</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                                @if ($availableRecipes->count() > 6)
                                                                    <div class="col-12">
                                                                        <small class="text-muted">...他 {{ $availableRecipes->count() - 6 }} 件</small>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="text-center py-2 text-muted">
                                                            <small>レシピが設定されていません</small>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($facility->facility_type === 'tavern')
                                <!-- 酒場設定 -->
                                <div class="row">
                                                            <i class="fas fa-table me-1"></i> テーブル表示
                                                        </button>
                                                        <button type="button" class="btn btn-outline-success" id="card-mode-btn" onclick="switchToCardMode()">
                                                            <i class="fas fa-plus me-1"></i> カード追加
                                                        </button>
                                                    </div>
                                                    <div class="btn-group">
                                                        <input type="text" id="recipe-table-search" class="form-control form-control-sm" placeholder="レシピ名で検索..." onkeyup="filterTableRecipes()" style="width: 200px;">
                                                    </div>
                                                </div>
                                                
                                                <!-- カードモード（レシピ追加用） -->
                                                <div id="card-mode-section" style="display: none;">
                                                    <div class="recipe-cards-container">
                                                        <div class="recipes-grid">
                                                            @foreach ($allRecipes as $recipe)
                                                                @if (!in_array($recipe->id, $currentRecipeIds ?? []))
                                                                    <div class="recipe-card searchable-recipe-card" data-recipe-name="{{ strtolower($recipe->name) }}">
                                                                        <div class="recipe-card-header">
                                                                            <div class="d-flex justify-content-between align-items-start">
                                                                                <div class="recipe-info">
                                                                                    <h6 class="recipe-title">{{ $recipe->name }}</h6>
                                                                                    <small class="text-muted">{{ $recipe->recipe_key }}</small>
                                                                                </div>
                                                                                <button type="button" class="btn btn-success btn-sm" onclick="addSingleRecipe({{ $recipe->id }}, '{{ addslashes($recipe->name) }}')">
                                                                                    <i class="fas fa-plus"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        <div class="recipe-card-body">
                                                                            <div class="recipe-details">
                                                                                @if($recipe->productItem)
                                                                                    <div class="product-info mb-2">
                                                                                        <small><strong>成果物:</strong> {{ $recipe->productItem->name }} × {{ $recipe->product_quantity }}</small>
                                                                                    </div>
                                                                                @endif
                                                                                <div class="recipe-stats">
                                                                                    <span class="badge badge-info">Lv.{{ $recipe->required_skill_level }}</span>
                                                                                    <span class="badge {{ $recipe->success_rate >= 90 ? 'badge-success' : ($recipe->success_rate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                                                                        {{ $recipe->success_rate }}%
                                                                                    </span>
                                                                                    <span class="text-warning"><small>{{ $recipe->sp_cost }}SP</small></span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <!-- テーブルモード（既存の詳細管理） -->
                                            <form method="POST" action="{{ route('admin.town-facilities.update-recipes', $facility) }}" id="recipesForm">
                                                @csrf
                                                @method('POST')
                                                
                                                <div id="table-mode-section">
                                                    <div class="mb-3">
                                                        <div class="d-flex gap-2 mb-3">
                                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllRecipes()">
                                                                <i class="fas fa-check-double me-1"></i> 全選択
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllRecipes()">
                                                                <i class="fas fa-times me-1"></i> 全解除
                                                            </button>
                                                        </div>
                                                    </div>
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-hover table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th width="60">選択</th>
                                                                <th>レシピ名</th>
                                                                <th>成果物</th>
                                                                <th>必要Lv</th>
                                                                <th>成功率</th>
                                                                <th>SPコスト</th>
                                                                <th>操作</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($allRecipes as $recipe)
                                                                <tr class="recipe-row searchable-table-row" data-recipe-name="{{ strtolower($recipe->name) }}">
                                                                    <td>
                                                                        <div class="form-check">
                                                                            <input type="checkbox" 
                                                                                   name="recipes[]" 
                                                                                   value="{{ $recipe->id }}" 
                                                                                   id="recipe_{{ $recipe->id }}"
                                                                                   class="form-check-input recipe-checkbox"
                                                                                   {{ in_array($recipe->id, $currentRecipeIds) ? 'checked' : '' }}>
                                                                            <label class="form-check-label" for="recipe_{{ $recipe->id }}"></label>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <span class="me-2">⚗️</span>
                                                                            <div>
                                                                                <div class="fw-bold">{{ $recipe->name }}</div>
                                                                                <small class="text-muted">{{ $recipe->recipe_key }}</small>
                                                                            </div>
                                                                        </div>
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
                                                                            <span class="text-muted">不明 (ID: {{ $recipe->product_item_id }})</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-info">Lv.{{ $recipe->required_skill_level }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge {{ $recipe->success_rate >= 90 ? 'badge-success' : ($recipe->success_rate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                                                            {{ $recipe->success_rate }}%
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="text-warning fw-bold">{{ $recipe->sp_cost }}SP</span>
                                                                    </td>
                                                                    <td>
                                                                        <a href="{{ route('admin.compounding.recipes.edit', $recipe) }}" 
                                                                           class="btn btn-outline-primary btn-sm" 
                                                                           target="_blank" 
                                                                           title="レシピを編集">
                                                                            <i class="fas fa-edit"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center pt-3" style="border-top: 1px solid var(--admin-border);">
                                                    <div class="text-muted">
                                                        <small>
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            現在 <span id="selected-count">{{ count($currentRecipeIds) }}</span> 個のレシピが選択されています
                                                        </small>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-1"></i> レシピ選択を保存
                                                    </button>
                                                </div>
                                            </form>
                                        @else
                                            <div class="text-center py-4 text-muted">
                                                <i class="fas fa-flask fa-2x mb-3"></i>
                                                <h6>調合レシピがありません</h6>
                                                <p class="mb-0">
                                                    <a href="{{ route('admin.compounding.recipes.index') }}" target="_blank" class="text-primary">
                                                        <i class="fas fa-plus me-1"></i>調合レシピ管理画面
                                                    </a>
                                                    でレシピを作成してください。
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($facility->facility_type === 'tavern')
                                <!-- 酒場設定 -->
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-heart me-2"></i>
                                            回復サービス設定
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="hp_recovery_rate" class="form-label">
                                                        <i class="fas fa-heart me-1 text-danger"></i> HP回復量 (1Gあたり)
                                                    </label>
                                                    <input type="number" name="config[hp_recovery_rate]" id="hp_recovery_rate" 
                                                           class="form-control" min="1" max="100" step="1"
                                                           value="{{ $facility->facility_config['hp_recovery_rate'] ?? $facility->facility_config['hp_rate'] ?? 10 }}">
                                                    <small class="text-muted">1Gold支払いで回復するHP量</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="mp_recovery_rate" class="form-label">
                                                        <i class="fas fa-magic me-1 text-primary"></i> MP回復量 (1Gあたり)
                                                    </label>
                                                    <input type="number" name="config[mp_recovery_rate]" id="mp_recovery_rate" 
                                                           class="form-control" min="1" max="100" step="1"
                                                           value="{{ $facility->facility_config['mp_recovery_rate'] ?? $facility->facility_config['mp_rate'] ?? 15 }}">
                                                    <small class="text-muted">1Gold支払いで回復するMP量</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label for="sp_recovery_rate" class="form-label">
                                                        <i class="fas fa-bolt me-1 text-warning"></i> SP回復量 (1Gあたり)
                                                    </label>
                                                    <input type="number" name="config[sp_recovery_rate]" id="sp_recovery_rate" 
                                                           class="form-control" min="1" max="100" step="1"
                                                           value="{{ $facility->facility_config['sp_recovery_rate'] ?? $facility->facility_config['sp_rate'] ?? 5 }}">
                                                    <small class="text-muted">1Gold支払いで回復するSP量</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="full_heal_discount" class="form-label">
                                                        <i class="fas fa-percentage me-1"></i> 全回復割引率 (%)
                                                    </label>
                                                    <input type="number" name="config[full_heal_discount]" id="full_heal_discount" 
                                                           class="form-control" min="0" max="50" step="1"
                                                           value="{{ ($facility->facility_config['full_heal_discount'] ?? 0.1) * 100 }}">
                                                    <small class="text-muted">全回復時の割引率（0-50%）</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mb-3" style="margin-top: 2rem;">
                                                    <input type="checkbox" name="config[status_healing_available]" id="status_healing" 
                                                           class="form-check-input" value="1"
                                                           {{ ($facility->facility_config['status_healing_available'] ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="status_healing">
                                                        <i class="fas fa-shield-alt me-1"></i> 状態異常回復サービス
                                                    </label>
                                                </div>
                                            </div>
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

@if ($facility->facility_type === 'compounding_shop')
<!-- 調合レシピ管理（フル幅セクション） -->
<div class="container-fluid px-4">
    <div class="admin-card mt-4">
        <div class="admin-card-header">
            <h3 class="admin-card-title" id="recipe-management-section">
                <i class="fas fa-flask me-2"></i>
                調合レシピの管理
            </h3>
        </div>
        <div class="admin-card-body">
            @if (isset($allRecipes) && $allRecipes->count() > 0)
                <!-- モード切り替えコントロール -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary" id="table-mode-btn" onclick="switchToTableMode()">
                            <i class="fas fa-table me-1"></i> テーブル表示
                        </button>
                        <button type="button" class="btn btn-outline-success" id="card-mode-btn" onclick="switchToCardMode()">
                            <i class="fas fa-plus me-1"></i> カード追加
                        </button>
                    </div>
                    <div class="recipe-search-container">
                        <input type="text" id="recipe-table-search" class="form-control" placeholder="レシピ名で検索..." onkeyup="filterTableRecipes()" style="width: 300px;">
                    </div>
                </div>

                <!-- カードモード（レシピ追加用） -->
                <div id="card-mode-section" style="display: none;">
                    <div class="recipe-cards-container">
                        <div class="recipes-grid">
                            @foreach ($allRecipes as $recipe)
                                @if (!in_array($recipe->id, $currentRecipeIds ?? []))
                                    <div class="recipe-card searchable-recipe-card" data-recipe-name="{{ strtolower($recipe->name) }}">
                                        <div class="recipe-card-header">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="recipe-info">
                                                    <h6 class="recipe-title">{{ $recipe->name }}</h6>
                                                    <small class="text-muted">{{ $recipe->recipe_key }}</small>
                                                </div>
                                                <button type="button" class="btn btn-success btn-sm" onclick="addSingleRecipe({{ $recipe->id }}, '{{ addslashes($recipe->name) }}')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="recipe-card-body">
                                            <div class="recipe-details">
                                                @if($recipe->productItem)
                                                    <div class="product-info mb-2">
                                                        <small><strong>成果物:</strong> {{ $recipe->productItem->name }} × {{ $recipe->product_quantity }}</small>
                                                    </div>
                                                @endif
                                                <div class="recipe-stats">
                                                    <span class="badge badge-info">Lv.{{ $recipe->required_skill_level }}</span>
                                                    <span class="badge {{ $recipe->success_rate >= 90 ? 'badge-success' : ($recipe->success_rate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                                        {{ $recipe->success_rate }}%
                                                    </span>
                                                    <span class="text-warning"><small>{{ $recipe->sp_cost }}SP</small></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- テーブルモード（既存の詳細管理） -->
                <div id="table-mode-section">
                    <form method="POST" action="{{ route('admin.town-facilities.update-recipes', $facility) }}" id="recipesForm">
                        @csrf
                        @method('POST')
                        
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllRecipes()">
                                <i class="fas fa-check-double me-1"></i> 全選択
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllRecipes()">
                                <i class="fas fa-times me-1"></i> 全解除
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th width="60">選択</th>
                                        <th>レシピ名</th>
                                        <th>成果物</th>
                                        <th>必要Lv</th>
                                        <th>成功率</th>
                                        <th>SPコスト</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($allRecipes as $recipe)
                                        <tr class="recipe-row searchable-table-row" data-recipe-name="{{ strtolower($recipe->name) }}">
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" 
                                                           name="recipes[]" 
                                                           value="{{ $recipe->id }}" 
                                                           id="recipe_{{ $recipe->id }}"
                                                           class="form-check-input recipe-checkbox"
                                                           {{ in_array($recipe->id, $currentRecipeIds) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="recipe_{{ $recipe->id }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2">⚗️</span>
                                                    <div>
                                                        <div class="fw-bold">{{ $recipe->name }}</div>
                                                        <small class="text-muted">{{ $recipe->recipe_key }}</small>
                                                    </div>
                                                </div>
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
                                                    <span class="text-muted">不明 (ID: {{ $recipe->product_item_id }})</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-info">Lv.{{ $recipe->required_skill_level }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $recipe->success_rate >= 90 ? 'badge-success' : ($recipe->success_rate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                                    {{ $recipe->success_rate }}%
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-warning fw-bold">{{ $recipe->sp_cost }}SP</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.compounding.recipes.edit', $recipe) }}" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   target="_blank" 
                                                   title="レシピを編集">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center pt-3" style="border-top: 1px solid var(--admin-border);">
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    現在 <span id="selected-count">{{ count($currentRecipeIds ?? []) }}</span> 個のレシピが選択されています
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> レシピ選択を保存
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-flask fa-4x mb-4"></i>
                    <h4>調合レシピがありません</h4>
                    <p class="mb-4">まず調合レシピを作成してください。</p>
                    <a href="{{ route('admin.compounding.recipes.index') }}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>調合レシピ管理画面
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endif

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
.badge-warning { background-color: var(--admin-warning); color: white; }

/* 調合レシピ管理スタイル */
.service-recipes-section {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    border: 1px solid var(--admin-border);
}

.current-recipes-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.recipe-tag {
    display: inline-flex;
    align-items: center;
    background-color: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 1rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    color: #1976d2;
}

.recipe-tag .recipe-name {
    margin-right: 0.5rem;
}

.recipe-tag .btn {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
    line-height: 1;
    border: none;
}

.recipes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.recipe-card {
    background: white;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    padding: 0.75rem;
    transition: all 0.2s ease;
}

.recipe-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-color: var(--admin-primary);
}

.recipe-card-header {
    margin-bottom: 0.5rem;
}

.recipe-title {
    margin: 0;
    color: var(--admin-dark);
    font-size: 0.9rem;
}

.recipe-card-body {
    font-size: 0.8rem;
}

.recipe-stats {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.recipe-stats .badge {
    font-size: 0.7rem;
}

.product-info {
    color: var(--admin-secondary);
}

/* レシピ追加セクション（インライン）スタイル */
.add-recipe-container {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
}

.add-recipe-container .recipes-grid {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    padding: 0.5rem;
    background: white;
}

/* レシピサマリーカード（サービス設定内） */
.recipe-summary-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    padding: 0.5rem;
    font-size: 0.875rem;
    transition: box-shadow 0.2s;
}

.recipe-summary-card:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* レシピカードコンテナ */
.recipe-cards-container {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid #dee2e6;
}

.recipe-cards-container .recipes-grid {
    max-height: 400px;
    overflow-y: auto;
    background: white;
    border-radius: 0.25rem;
    padding: 1rem;
    border: 1px solid #e9ecef;
}

/* ボタン状態の調整 */
.btn-group .btn {
    margin-right: 0;
}
</style>

<!-- アイテム追加モーダル -->
@if (in_array($facility->facility_type, ['item_shop', 'weapon_shop', 'armor_shop', 'magic_shop']))
<div class="modal fade" id="addItemModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>販売アイテム追加
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label for="item_select" class="form-label">
                                    <i class="fas fa-box me-1"></i> アイテム選択
                                </label>
                                <select name="item_id" id="item_select" class="form-control" required>
                                    <option value="">-- アイテムを選択してください --</option>
                                </select>
                                <small class="text-muted">販売するアイテムを選択してください</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="item_price" class="form-label">
                                    <i class="fas fa-coins me-1"></i> 販売価格
                                </label>
                                <input type="number" name="price" id="item_price" class="form-control" min="1" required>
                                <small class="text-muted">Gold</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="item_stock" class="form-label">
                                    <i class="fas fa-warehouse me-1"></i> 在庫数
                                </label>
                                <input type="number" name="stock" id="item_stock" class="form-control" min="-1" value="-1" required>
                                <small class="text-muted">-1で無限在庫</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3" style="margin-top: 2rem;">
                                <input type="checkbox" name="is_available" id="item_available" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="item_available">
                                    <i class="fas fa-check me-1"></i> 販売開始する
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> キャンセル
                </button>
                <button type="button" class="btn btn-primary" onclick="submitAddItem()">
                    <i class="fas fa-plus me-1"></i> アイテムを追加
                </button>
            </div>
        </div>
    </div>
</div>

<!-- アイテム編集モーダル -->
<div class="modal fade" id="editItemModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>アイテム編集
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    @csrf
                    <input type="hidden" id="edit_item_id" name="item_id">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-box me-1"></i> アイテム名
                        </label>
                        <div class="admin-readonly-field">
                            <span id="edit_item_name">-</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_item_price" class="form-label">
                                    <i class="fas fa-coins me-1"></i> 販売価格
                                </label>
                                <input type="number" name="price" id="edit_item_price" class="form-control" min="1" required>
                                <small class="text-muted">Gold</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_item_stock" class="form-label">
                                    <i class="fas fa-warehouse me-1"></i> 在庫数
                                </label>
                                <input type="number" name="stock" id="edit_item_stock" class="form-control" min="-1" required>
                                <small class="text-muted">-1で無限在庫</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_available" id="edit_item_available" class="form-check-input" value="1">
                        <label class="form-check-label" for="edit_item_available">
                            <i class="fas fa-check me-1"></i> 販売中
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> キャンセル
                </button>
                <button type="button" class="btn btn-primary" onclick="submitEditItem()">
                    <i class="fas fa-save me-1"></i> 更新
                </button>
            </div>
        </div>
    </div>
</div>
@endif


<!-- JavaScript for item management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // アイテム一覧をロード
    loadAvailableItems();
    
    // 調合レシピのチェックボックス変更時のイベントリスナー
    const recipeCheckboxes = document.querySelectorAll('.recipe-checkbox');
    recipeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    // 初期状態でテーブルモードをアクティブにする
    if (document.getElementById('table-mode-btn')) {
        switchToTableMode();
    }
    
    // アイテム追加モーダル表示
    window.showAddItemModal = function() {
        $('#addItemModal').modal('show');
        loadAvailableItems();
    };
    
    // アイテム編集
    window.editFacilityItem = function(itemId) {
        fetch(`/admin/town-facilities/{{ $facility->id }}/items/${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.item;
                    document.getElementById('edit_item_id').value = item.id;
                    document.getElementById('edit_item_name').textContent = item.item_name;
                    document.getElementById('edit_item_price').value = item.price;
                    document.getElementById('edit_item_stock').value = item.stock;
                    document.getElementById('edit_item_available').checked = item.is_available;
                    $('#editItemModal').modal('show');
                } else {
                    alert('アイテム情報の取得に失敗しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('アイテム情報の取得中にエラーが発生しました。');
            });
    };
    
    // アイテム削除
    window.deleteFacilityItem = function(itemId) {
        if (confirm('このアイテムを販売リストから削除しますか？')) {
            fetch(`/admin/town-facilities/{{ $facility->id }}/items/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`item-row-${itemId}`).remove();
                    alert(data.message);
                } else {
                    alert('削除に失敗しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('削除中にエラーが発生しました。');
            });
        }
    };
    
    // アイテム追加実行
    window.submitAddItem = function() {
        const formData = new FormData(document.getElementById('addItemForm'));
        const itemSelect = document.getElementById('item_select');
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        
        if (!selectedOption.value) {
            alert('アイテムを選択してください。');
            return;
        }
        
        formData.append('item_name', selectedOption.text.split(' (')[0]);
        
        fetch(`/admin/town-facilities/{{ $facility->id }}/items`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#addItemModal').modal('hide');
                location.reload(); // ページをリロードして最新状態を表示
            } else {
                alert('追加に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('追加中にエラーが発生しました。');
        });
    };
    
    // アイテム編集実行
    window.submitEditItem = function() {
        const formData = new FormData(document.getElementById('editItemForm'));
        const itemId = document.getElementById('edit_item_id').value;
        
        fetch(`/admin/town-facilities/{{ $facility->id }}/items/${itemId}`, {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#editItemModal').modal('hide');
                location.reload(); // ページをリロードして最新状態を表示
            } else {
                alert('更新に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('更新中にエラーが発生しました。');
        });
    };
    
    // 利用可能アイテム一覧をロード
    function loadAvailableItems() {
        fetch('/admin/api/items')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('item_select');
                if (select) {
                    select.innerHTML = '<option value="">-- アイテムを選択してください --</option>';
                    
                    let currentCategory = null;
                    data.forEach(item => {
                        if (currentCategory !== item.category_label) {
                            if (currentCategory !== null) {
                                select.appendChild(document.createElement('optgroup')).setAttribute('label', '');
                            }
                            const optgroup = document.createElement('optgroup');
                            optgroup.setAttribute('label', item.category_label);
                            select.appendChild(optgroup);
                            currentCategory = item.category_label;
                        }
                        
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.display_name;
                        select.lastElementChild.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading items:', error);
            });
    }
    
    // 調合レシピ関連の関数
    window.selectAllRecipes = function() {
        const checkboxes = document.querySelectorAll('.recipe-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelectedCount();
    };
    
    window.deselectAllRecipes = function() {
        const checkboxes = document.querySelectorAll('.recipe-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateSelectedCount();
    };
    
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.recipe-checkbox:checked').length;
        const countElement = document.getElementById('selected-count');
        if (countElement) {
            countElement.textContent = checkedCount;
        }
    }
    
    // 調合レシピ管理関数（モード切り替え）
    window.switchToTableMode = function() {
        document.getElementById('table-mode-section').style.display = 'block';
        document.getElementById('card-mode-section').style.display = 'none';
        document.getElementById('table-mode-btn').classList.remove('btn-outline-primary');
        document.getElementById('table-mode-btn').classList.add('btn-primary');
        document.getElementById('card-mode-btn').classList.remove('btn-success');
        document.getElementById('card-mode-btn').classList.add('btn-outline-success');
    };
    
    window.switchToCardMode = function() {
        document.getElementById('table-mode-section').style.display = 'none';
        document.getElementById('card-mode-section').style.display = 'block';
        document.getElementById('table-mode-btn').classList.remove('btn-primary');
        document.getElementById('table-mode-btn').classList.add('btn-outline-primary');
        document.getElementById('card-mode-btn').classList.remove('btn-outline-success');
        document.getElementById('card-mode-btn').classList.add('btn-success');
    };
    
    window.addSingleRecipe = function(recipeId, recipeName) {
        // 個別のレシピを追加
        fetch(`/admin/town-facilities/{{ $facility->id }}/recipes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                recipes: [...getCurrentRecipeIds(), recipeId]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                location.reload(); // ページをリロードして最新状態を表示
            } else {
                alert('レシピの追加に失敗しました: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('レシピの追加中にエラーが発生しました。');
        });
    };
    
    
    // 検索機能（新）
    window.filterTableRecipes = function() {
        const searchTerm = document.getElementById('recipe-table-search').value.toLowerCase();
        
        // カードモードの検索
        const recipeCards = document.querySelectorAll('.searchable-recipe-card');
        recipeCards.forEach(card => {
            const recipeName = card.getAttribute('data-recipe-name');
            if (recipeName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        // テーブルモードの検索
        const tableRows = document.querySelectorAll('.searchable-table-row');
        tableRows.forEach(row => {
            const recipeName = row.getAttribute('data-recipe-name');
            if (recipeName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };
    
    function getCurrentRecipeIds() {
        const checkedBoxes = document.querySelectorAll('.recipe-checkbox:checked');
        return Array.from(checkedBoxes).map(checkbox => parseInt(checkbox.value));
    }
});
</script>
@endsection