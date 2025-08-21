<!-- Road用フォーム部品 -->
<div style="display: grid; gap: 2rem;">
    
    <!-- 基本情報 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">基本情報</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; gap: 1.5rem;">
                
                <!-- ID（新規作成時のみ） -->
                @if(!isset($road))
                <div>
                    <label for="id" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Road ID <span style="color: var(--admin-danger);">*</span>
                    </label>
                    <input type="text" 
                           id="id" 
                           name="id" 
                           value="{{ old('id') }}" 
                           class="admin-input"
                           placeholder="例: mountain_road_1"
                           required>
                    <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                        英数字とアンダースコアのみ使用可能。他のロケーションと重複しないユニークなIDを入力してください。
                    </div>
                    @error('id')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
                @else
                <!-- 編集時はIDを表示のみ -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Road ID</label>
                    <div style="padding: 0.75rem; background: var(--admin-bg); border: 1px solid var(--admin-border); border-radius: 0.5rem;">
                        <code>{{ $road->id }}</code>
                    </div>
                </div>
                @endif

                <!-- 名前 -->
                <div>
                    <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Road名 <span style="color: var(--admin-danger);">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $road->name ?? '') }}" 
                           class="admin-input"
                           placeholder="例: 山岳街道"
                           required>
                    @error('name')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- 説明 -->
                <div>
                    <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        説明
                    </label>
                    <textarea id="description" 
                              name="description" 
                              class="admin-textarea"
                              rows="3"
                              placeholder="このRoadの詳細説明を入力してください">{{ old('description', $road->description ?? '') }}</textarea>
                    @error('description')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- ゲーム設定 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">ゲーム設定</h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                
                <!-- 長さ -->
                <div>
                    <label for="length" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        長さ <span style="color: var(--admin-danger);">*</span>
                    </label>
                    <input type="number" 
                           id="length" 
                           name="length" 
                           value="{{ old('length', $road->length ?? '') }}" 
                           class="admin-input"
                           min="1" 
                           max="1000"
                           placeholder="100"
                           required>
                    <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                        1-1000の範囲で入力
                    </div>
                    @error('length')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- 難易度 -->
                <div>
                    <label for="difficulty" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        難易度 <span style="color: var(--admin-danger);">*</span>
                    </label>
                    <select id="difficulty" name="difficulty" class="admin-select" required>
                        <option value="">選択してください</option>
                        <option value="easy" {{ old('difficulty', $road->difficulty ?? '') === 'easy' ? 'selected' : '' }}>
                            簡単
                        </option>
                        <option value="normal" {{ old('difficulty', $road->difficulty ?? '') === 'normal' ? 'selected' : '' }}>
                            普通
                        </option>
                        <option value="hard" {{ old('difficulty', $road->difficulty ?? '') === 'hard' ? 'selected' : '' }}>
                            困難
                        </option>
                    </select>
                    @error('difficulty')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- エンカウント率 -->
                <div>
                    <label for="encounter_rate" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        エンカウント率
                    </label>
                    <input type="number" 
                           id="encounter_rate" 
                           name="encounter_rate" 
                           value="{{ old('encounter_rate', $road->encounter_rate ?? '') }}" 
                           class="admin-input"
                           min="0" 
                           max="1"
                           step="0.01"
                           placeholder="0.30">
                    <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                        0.00-1.00の範囲（例: 0.30 = 30%）
                    </div>
                    @error('encounter_rate')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- ステータス設定（編集時のみ） -->
    @if(isset($road))
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">ステータス設定</h3>
        </div>
        <div class="admin-card-body">
            <div>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1" 
                           {{ old('is_active', $road->is_active ?? true) ? 'checked' : '' }}
                           style="transform: scale(1.2);">
                    <span style="font-weight: 600;">アクティブ</span>
                </label>
                <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                    非アクティブにすると、ゲーム内でこのRoadは利用できなくなります。
                </div>
                @error('is_active')
                <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                    {{ $message }}
                </div>
                @enderror
            </div>
        </div>
    </div>
    @endif

</div>

<!-- フォーム送信ボタン -->
<div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
    <a href="{{ route('admin.roads.index') }}" class="admin-btn admin-btn-secondary">
        <i class="fas fa-times"></i> キャンセル
    </a>
    <button type="submit" class="admin-btn admin-btn-primary">
        <i class="fas fa-save"></i> {{ isset($road) ? '更新' : '作成' }}
    </button>
</div>