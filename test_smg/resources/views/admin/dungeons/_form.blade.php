<!-- DungeonDesc用フォーム部品 -->
<div style="display: grid; gap: 2rem;">
    
    <!-- 基本情報 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-dungeon"></i> ダンジョン基本情報
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; gap: 1.5rem;">
                
                <!-- ダンジョンID（新規作成時のみ） -->
                @if(!isset($dungeon))
                <div>
                    <label for="dungeon_id" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        ダンジョンID <span style="color: var(--admin-danger);">*</span>
                    </label>
                    <input type="text" 
                           id="dungeon_id" 
                           name="dungeon_id" 
                           value="{{ old('dungeon_id') }}" 
                           class="admin-input"
                           placeholder="例: ancient_pyramid"
                           required>
                    <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                        英数字とアンダースコアのみ使用可能。他のダンジョンと重複しないユニークなIDを入力してください。<br>
                        このIDはフロア作成時に自動的に使用されます。
                    </div>
                    @error('dungeon_id')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
                @else
                <!-- 編集時はダンジョンIDを表示のみ -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">ダンジョンID</label>
                    <div style="padding: 0.75rem; background: var(--admin-bg); border: 1px solid var(--admin-border); border-radius: 0.5rem;">
                        <code>{{ $dungeon->dungeon_id }}</code>
                        <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                            このダンジョンには {{ $dungeon->floors->count() }} 個のフロアが関連付けられています。
                        </div>
                    </div>
                </div>
                @endif

                <!-- ダンジョン名 -->
                <div>
                    <label for="dungeon_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        ダンジョン名 <span style="color: var(--admin-danger);">*</span>
                    </label>
                    <input type="text" 
                           id="dungeon_name" 
                           name="dungeon_name" 
                           value="{{ old('dungeon_name', $dungeon->dungeon_name ?? '') }}" 
                           class="admin-input"
                           placeholder="例: 古代のピラミッド"
                           required>
                    <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                        プレイヤーに表示されるダンジョンの名前です。
                    </div>
                    @error('dungeon_name')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- ダンジョン説明 -->
                <div>
                    <label for="dungeon_desc" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        ダンジョン説明
                    </label>
                    <textarea id="dungeon_desc" 
                              name="dungeon_desc" 
                              class="admin-textarea"
                              rows="4"
                              placeholder="このダンジョンの詳細説明を入力してください。プレイヤーに表示される説明文です。">{{ old('dungeon_desc', $dungeon->dungeon_desc ?? '') }}</textarea>
                    <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                        ダンジョンの背景、構造、特徴などを記述してください。
                    </div>
                    @error('dungeon_desc')
                    <div style="margin-top: 0.5rem; color: var(--admin-danger); font-size: 0.875rem;">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- ステータス設定（編集時のみ） -->
    @if(isset($dungeon))
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-toggle-on"></i> ステータス設定
            </h3>
        </div>
        <div class="admin-card-body">
            <div>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1" 
                           {{ old('is_active', $dungeon->is_active ?? true) ? 'checked' : '' }}
                           style="transform: scale(1.2);">
                    <span style="font-weight: 600;">アクティブ</span>
                </label>
                <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--admin-secondary);">
                    非アクティブにすると、ゲーム内でこのダンジョンは利用できなくなります。<br>
                    関連するフロア（{{ $dungeon->floors->count() }}個）にも影響を与える可能性があります。
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

    @if(isset($dungeon) && $dungeon->floors->count() > 0)
    <!-- フロア情報プレビュー（編集時のみ） -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-layer-group"></i> 現在のフロア構成
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="margin-bottom: 1rem; color: var(--admin-secondary);">
                このダンジョンには現在 {{ $dungeon->floors->count() }} 個のフロアが設定されています。
            </div>
            <div style="display: grid; gap: 0.5rem; max-height: 200px; overflow-y: auto;">
                @foreach($dungeon->floors as $floor)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: var(--admin-bg); border: 1px solid var(--admin-border); border-radius: 0.375rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <code style="background: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                            {{ $floor->id }}
                        </code>
                        <div>
                            <div style="font-weight: 600; font-size: 0.875rem;">{{ $floor->name }}</div>
                            @if($floor->description)
                            <div style="font-size: 0.75rem; color: var(--admin-secondary);">
                                {{ Str::limit($floor->description, 40) }}
                            </div>
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        @if($floor->is_active)
                        <span class="admin-badge admin-badge-success admin-badge-sm">Active</span>
                        @else
                        <span class="admin-badge admin-badge-secondary admin-badge-sm">Inactive</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <div style="margin-top: 1rem; text-align: right;">
                <a href="{{ route('admin.dungeons.floors', $dungeon->id) }}" 
                   class="admin-btn admin-btn-sm admin-btn-info">
                    <i class="fas fa-cog"></i> フロア管理
                </a>
                <a href="{{ route('admin.dungeons.create-floor', $dungeon->id) }}" 
                   class="admin-btn admin-btn-sm admin-btn-success">
                    <i class="fas fa-plus"></i> フロア追加
                </a>
            </div>
        </div>
    </div>
    @endif

    @if(!isset($dungeon))
    <!-- 次のステップ案内（新規作成時のみ） -->
    <div class="admin-card" style="background: linear-gradient(135deg, #f0f9ff, #e0f7fa);">
        <div class="admin-card-header">
            <h3 class="admin-card-title" style="color: var(--admin-info);">
                <i class="fas fa-info-circle"></i> 次のステップ
            </h3>
        </div>
        <div class="admin-card-body">
            <div style="color: var(--admin-secondary);">
                ダンジョンを作成した後は、以下の手順でダンジョンを構築してください：
            </div>
            <ol style="margin: 1rem 0; padding-left: 1.5rem; color: var(--admin-secondary);">
                <li>ダンジョンの基本情報を保存</li>
                <li>フロアを追加してダンジョンの構造を作成</li>
                <li>各フロアにモンスタースポーンを設定</li>
                <li>フロア間の接続を設定</li>
                <li>テストプレイで動作確認</li>
            </ol>
            <div style="padding: 1rem; background: rgba(6, 182, 212, 0.1); border-left: 4px solid var(--admin-info); border-radius: 0.25rem;">
                <strong>ヒント:</strong> ダンジョンIDは後から変更できません。フロア作成時に自動的に「dungeon_id + フロア番号」の形式でフロアIDが生成されます。
            </div>
        </div>
    </div>
    @endif

</div>

<!-- フォーム送信ボタン -->
<div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
    <a href="{{ route('admin.dungeons.index') }}" class="admin-btn admin-btn-secondary">
        <i class="fas fa-times"></i> キャンセル
    </a>
    <button type="submit" class="admin-btn admin-btn-primary">
        <i class="fas fa-save"></i> {{ isset($dungeon) ? '更新' : '作成' }}
    </button>
</div>

<style>
.admin-badge-sm {
    font-size: 0.7rem;
    padding: 0.125rem 0.375rem;
}
</style>