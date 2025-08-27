@extends('admin.layouts.app')

@section('title', '新規施設作成')

@section('content')
<div class="container-fluid">
    
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <span class="me-2">🏗️</span>
                新規施設作成
            </h1>
            <p class="mb-0 text-muted">町に新しい施設を作成します</p>
        </div>
        
        <div class="btn-group" role="group">
            <a href="{{ route('admin.town-facilities.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> 施設一覧に戻る
            </a>
        </div>
    </div>

    <!-- エラー表示 -->
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

    <!-- 成功メッセージ -->
    @if (session('success'))
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- 作成フォーム -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-building me-2"></i>
                施設情報
            </h3>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.town-facilities.store') }}" id="facilityCreateForm">
                @csrf
                
                <div class="row">
                    <!-- 左カラム -->
                    <div class="col-md-6">
                        <!-- 町選択 -->
                        <div class="form-group mb-3">
                            <label for="location_id" class="form-label required">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                設置する町
                            </label>
                            <select name="location_id" id="location_id" 
                                    class="form-select @error('location_id') is-invalid @enderror" 
                                    required>
                                <option value="">-- 町を選択してください --</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location['id'] }}" 
                                            data-type="{{ $location['type'] }}"
                                            {{ old('location_id') == $location['id'] ? 'selected' : '' }}>
                                        {{ $location['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">施設を設置する町を選択してください</div>
                        </div>

                        <!-- 施設タイプ選択 -->
                        <div class="form-group mb-3">
                            <label for="facility_type" class="form-label required">
                                <i class="fas fa-store me-1"></i>
                                施設タイプ
                            </label>
                            <select name="facility_type" id="facility_type" 
                                    class="form-select @error('facility_type') is-invalid @enderror" 
                                    required>
                                <option value="">-- 施設タイプを選択してください --</option>
                                @foreach ($facilityTypes as $facilityType)
                                    <option value="{{ $facilityType->value }}" 
                                            data-icon="{{ $facilityType->getIcon() }}"
                                            data-description="{{ $facilityType->getDescription() }}"
                                            {{ old('facility_type') == $facilityType->value ? 'selected' : '' }}>
                                        {{ $facilityType->getIcon() }} {{ $facilityType->getDisplayName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('facility_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">作成する施設のタイプを選択してください</div>
                            
                            <!-- 重複警告表示エリア -->
                            <div id="duplicateWarning" class="alert alert-warning mt-2" style="display: none;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                この町には既に同じタイプの施設が存在します。
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
                                   value="{{ old('name') }}"
                                   placeholder="例: プリマ町の道具屋" 
                                   maxlength="255" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">施設の名称を入力してください（最大255文字）</div>
                        </div>
                    </div>

                    <!-- 右カラム -->
                    <div class="col-md-6">
                        <!-- 施設の説明 -->
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>
                                施設の説明
                            </label>
                            <textarea name="description" id="description" 
                                      class="form-control @error('description') is-invalid @enderror" 
                                      rows="4" 
                                      maxlength="1000" 
                                      placeholder="施設の詳細説明を入力してください（任意）">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">施設の詳細説明（最大1000文字、任意）</div>
                        </div>

                        <!-- 稼働状態 -->
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" 
                                       class="form-check-input @error('is_active') is-invalid @enderror" 
                                       value="1"
                                       {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <i class="fas fa-power-off me-1"></i>
                                    施設を稼働状態で作成する
                                </label>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">チェックを外すと非稼働状態で作成されます</div>
                        </div>

                        <!-- 選択された施設タイプの説明 -->
                        <div id="facilityTypeDescription" class="card bg-light" style="display: none;">
                            <div class="card-body">
                                <h5 id="facilityTypeTitle" class="mb-2"></h5>
                                <p id="facilityTypeDesc" class="mb-0 text-muted"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 隠しフィールド -->
                <input type="hidden" name="location_type" value="town">

                <!-- 作成ボタン -->
                <div class="d-flex justify-content-end gap-3 mt-4 pt-3" style="border-top: 1px solid var(--admin-border);">
                    <a href="{{ route('admin.town-facilities.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> キャンセル
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-plus me-1"></i> 施設を作成
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for form enhancement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationSelect = document.getElementById('location_id');
    const facilityTypeSelect = document.getElementById('facility_type');
    const nameInput = document.getElementById('name');
    const duplicateWarning = document.getElementById('duplicateWarning');
    const facilityTypeDescription = document.getElementById('facilityTypeDescription');
    const facilityTypeTitle = document.getElementById('facilityTypeTitle');
    const facilityTypeDesc = document.getElementById('facilityTypeDesc');
    
    // 施設タイプ選択時の説明表示
    facilityTypeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value && selectedOption.dataset.description) {
            const icon = selectedOption.dataset.icon || '';
            const name = selectedOption.textContent.replace(/^[^\s]*\s/, ''); // アイコンを除去
            const description = selectedOption.dataset.description;
            
            facilityTypeTitle.textContent = icon + ' ' + name;
            facilityTypeDesc.textContent = description;
            facilityTypeDescription.style.display = 'block';
        } else {
            facilityTypeDescription.style.display = 'none';
        }
        
        // 施設名の自動提案
        updateFacilityName();
        
        // 重複チェック
        checkDuplicate();
    });
    
    // 町選択時
    locationSelect.addEventListener('change', function() {
        updateFacilityName();
        checkDuplicate();
    });
    
    // 施設名の自動提案
    function updateFacilityName() {
        if (!nameInput.value || nameInput.dataset.userModified !== 'true') {
            const locationText = locationSelect.options[locationSelect.selectedIndex]?.textContent || '';
            const facilityText = facilityTypeSelect.options[facilityTypeSelect.selectedIndex]?.textContent || '';
            
            if (locationText && facilityText) {
                const facilityName = facilityText.replace(/^[^\s]*\s/, ''); // アイコンを除去
                nameInput.value = locationText + 'の' + facilityName;
            }
        }
    }
    
    // ユーザーが名前を手動で変更した場合のフラグ設定
    nameInput.addEventListener('input', function() {
        this.dataset.userModified = 'true';
    });
    
    // 重複チェック（Ajax）
    async function checkDuplicate() {
        if (locationSelect.value && facilityTypeSelect.value) {
            try {
                const response = await fetch(`/admin/town-facilities/check-duplicate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        location_id: locationSelect.value,
                        location_type: 'town',
                        facility_type: facilityTypeSelect.value
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    duplicateWarning.style.display = data.exists ? 'block' : 'none';
                }
            } catch (error) {
                console.warn('重複チェックでエラーが発生しました:', error);
            }
        } else {
            duplicateWarning.style.display = 'none';
        }
    }
});
</script>

<style>
.required::after {
    content: ' *';
    color: var(--admin-danger);
}

.admin-form-help {
    font-size: 0.875rem;
    color: var(--admin-secondary);
    margin-top: 0.25rem;
}

.invalid-feedback {
    font-size: 0.875rem;
    color: var(--admin-danger);
    margin-top: 0.25rem;
}

.is-invalid {
    border-color: var(--admin-danger);
}

#facilityTypeDescription {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection