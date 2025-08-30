{{-- 
    パーシャルビュー: ロケーション接続管理
    
    使用方法:
    @include('admin.shared._route_connections', [
        'location' => $location, // 編集対象のロケーション（新規作成時はnull）
        'form_prefix' => 'connections' // フォーム要素のプレフィックス
    ])
--}}

@php
    $location = $location ?? null;
    $form_prefix = $form_prefix ?? 'connections';
    $outgoing_connections = $location ? $location->sourceConnections()->with('targetLocation')->get() : collect();
    $incoming_connections = $location ? $location->targetConnections()->with('sourceLocation')->get() : collect();
    $available_locations = \App\Models\Route::active()->where('id', '!=', $location?->id)->orderBy('name')->get();
@endphp

<div class="card admin-card mt-3" id="connections-section">
    <div class="card-header admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title admin-card-title mb-0" style="margin: 0; font-size: 1.25rem; font-weight: 600;">接続管理</h3>
        <button type="button" class="btn btn-sm btn-outline-primary admin-btn admin-btn-primary" id="add-connection-btn">
            <i class="fas fa-plus"></i> 接続追加
        </button>
    </div>
    <div class="card-body admin-card-body">
        <div style="margin-bottom: 1rem; font-size: 0.875rem; color: var(--admin-secondary, #6c757d);">
            このロケーションから他のロケーションへの接続を管理します。
        </div>

        <!-- 既存の接続 -->
        @if(($outgoing_connections->isNotEmpty()) || ($incoming_connections->isNotEmpty()))
            <h6 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">現在の接続</h6>
            <div style="margin-bottom: 1rem; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">種類</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">相手</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">位置</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">エッジ</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">アクション</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">キー</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">状態</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outgoing_connections as $connection)
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 0.75rem;">
                                <span class="badge bg-info">このロケーションから</span>
                            </td>
                            <td style="padding: 0.75rem;">
                                <div>
                                    <a href="{{ route('admin.locations.show', $connection->target_location_id) }}" target="_blank" style="color: var(--admin-primary, #007bff); text-decoration: none;">{{ $connection->targetLocation?->name ?? 'Unknown' }}</a>
                                    <br><small class="text-muted">{{ $connection->targetLocation?->category }}</small>
                                </div>
                            </td>
                            <td style="padding: 0.75rem;">
                                <div class="small">
                                    @if($connection->source_position !== null)
                                        <div><strong>出発:</strong> {{ $connection->source_position }}</div>
                                    @endif
                                    @if($connection->target_position !== null)
                                        <div><strong>到着:</strong> {{ $connection->target_position }}</div>
                                    @endif
                                    @if($connection->source_position === null && $connection->target_position === null)
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->edge_type)
                                    <span style="background: #6c757d; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">{{ $connection->edge_type }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->action_label)
                                    <div class="small">
                                        {{ \App\Helpers\ActionLabel::getActionLabelText($connection->action_label) }}
                                        <br><code style="font-size: 0.7rem;">{{ $connection->action_label }}</code>
                                    </div>
                                @else
                                    <span class="text-muted">自動</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->keyboard_shortcut)
                                    <span style="background: #343a40; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-family: monospace;">{{ \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->is_enabled ?? true)
                                    <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">有効</span>
                                @else
                                    <span style="background: #ffc107; color: #212529; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">無効</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.route-connections.edit', $connection->id) }}" 
                                       style="background: transparent; border: 1px solid #007bff; color: #007bff; padding: 0.25rem 0.5rem; border-radius: 0.25rem; text-decoration: none; font-size: 0.75rem;" 
                                       title="編集" target="_blank">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            style="background: transparent; border: 1px solid #dc3545; color: #dc3545; padding: 0.25rem 0.5rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;"
                                            class="delete-existing-connection" 
                                            data-connection-id="{{ $connection->id }}"
                                            data-target-name="{{ $connection->targetLocation?->name }}"
                                            data-connection-type="outgoing" title="削除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @foreach($incoming_connections as $connection)
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 0.75rem;">
                                <span class="badge bg-warning text-dark">このロケーションへ</span>
                            </td>
                            <td style="padding: 0.75rem;">
                                <div>
                                    <a href="{{ route('admin.locations.show', $connection->source_location_id) }}" target="_blank" style="color: var(--admin-primary, #007bff); text-decoration: none;">{{ $connection->sourceLocation?->name ?? 'Unknown' }}</a>
                                    <br><small class="text-muted">{{ $connection->sourceLocation?->category }}</small>
                                </div>
                            </td>
                            <td style="padding: 0.75rem;">
                                <div class="small">
                                    @if($connection->source_position !== null)
                                        <div><strong>出発:</strong> {{ $connection->source_position }}</div>
                                    @endif
                                    @if($connection->target_position !== null)
                                        <div><strong>到着:</strong> {{ $connection->target_position }}</div>
                                    @endif
                                    @if($connection->source_position === null && $connection->target_position === null)
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->edge_type)
                                    <span style="background: #6c757d; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">{{ $connection->edge_type }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->action_label)
                                    <div class="small">
                                        {{ \App\Helpers\ActionLabel::getActionLabelText($connection->action_label) }}
                                        <br><code style="font-size: 0.7rem;">{{ $connection->action_label }}</code>
                                    </div>
                                @else
                                    <span class="text-muted">自動</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->keyboard_shortcut)
                                    <span style="background: #343a40; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-family: monospace;">{{ \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                @if($connection->is_enabled ?? true)
                                    <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">有効</span>
                                @else
                                    <span style="background: #ffc107; color: #212529; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">無効</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.route-connections.edit', $connection->id) }}" 
                                       style="background: transparent; border: 1px solid #007bff; color: #007bff; padding: 0.25rem 0.5rem; border-radius: 0.25rem; text-decoration: none; font-size: 0.75rem;" 
                                       title="編集" target="_blank">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            style="background: transparent; border: 1px solid #dc3545; color: #dc3545; padding: 0.25rem 0.5rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;"
                                            class="delete-existing-connection" 
                                            data-connection-id="{{ $connection->id }}"
                                            data-target-name="{{ $connection->sourceLocation?->name }}"
                                            data-connection-type="incoming" title="削除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- 新しい接続フォーム -->
        <h6 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">新しい接続</h6>
        <div id="connections-container">
            <!-- 動的に追加される接続フォーム -->
        </div>

        <!-- 接続テンプレート（非表示） -->
        <div id="connection-template" style="display: none;">
            <div class="connection-form" style="border: 1px solid #dee2e6; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; background-color: #f8f9fa;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h6 style="margin: 0; font-size: 1rem; font-weight: 600;">接続 #<span class="connection-index"></span></h6>
                    <button type="button" style="background: transparent; border: 1px solid #dc3545; color: #dc3545; padding: 0.25rem 0.5rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;" class="remove-connection">
                        <i class="fas fa-times"></i> 削除
                    </button>
                </div>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">接続先ロケーション <span style="color: #dc3545;">*</span></label>
                        <select style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][target_location_id]" required disabled>
                            <option value="">選択してください</option>
                            @foreach($available_locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->category }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">エッジタイプ</label>
                        <select style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][edge_type]" disabled>
                            <option value="">選択してください</option>
                            <option value="normal">通常</option>
                            <option value="branch">分岐</option>
                            <option value="portal">ポータル</option>
                            <option value="exit">出口</option>
                            <option value="enter">入口</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">出発位置</label>
                        <input type="number" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][source_position]" min="0" max="100" placeholder="0-100" disabled>
                        <small style="color: #6c757d; font-size: 0.75rem;">道路・ダンジョンの場合は必須</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">到着位置</label>
                        <input type="number" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][target_position]" min="0" max="100" placeholder="0-100" disabled>
                        <small style="color: #6c757d; font-size: 0.75rem;">道路・ダンジョンの場合は必須</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">アクションラベル</label>
                        <select style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][action_label]" disabled>
                            <option value="">自動設定</option>
                            @foreach(\App\Helpers\ActionLabel::getAllActionLabels() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">キーボードショートカット</label>
                        <select style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][keyboard_shortcut]" disabled>
                            <option value="">なし</option>
                            @foreach(\App\Helpers\ActionLabel::getAllKeyboardShortcuts() as $key => $display)
                                <option value="{{ $key }}">{{ $display }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-top: 1.5rem;">
                            <input type="checkbox" name="{{ $form_prefix }}[INDEX][is_enabled]" value="1" checked disabled style="transform: scale(1.2);">
                            <span style="font-weight: 600; font-size: 0.875rem;">有効</span>
                        </label>
                    </div>
                </div>
                
                <!-- Legacy fields (collapsed) -->
                <details style="margin-top: 1rem;">
                    <summary style="cursor: pointer; font-weight: 600; color: #6c757d; font-size: 0.875rem;">レガシーフィールド（互換性）</summary>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; margin-top: 1rem; padding: 0.5rem; background: #f8f9fa; border-radius: 0.25rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">方向（レガシー）</label>
                            <input type="text" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][direction]" placeholder="例: 北、南東" disabled>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<!-- 接続削除確認モーダル -->
<div class="modal fade" id="deleteConnectionModal" tabindex="-1" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 0.5rem; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #dee2e6;">
            <h5 style="margin: 0; font-size: 1.25rem; font-weight: 600;">接続削除確認</h5>
            <button type="button" class="modal-close-btn" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        <div style="padding: 1.5rem;">
            <p>接続先「<span id="delete-target-name"></span>」への接続を削除しますか？</p>
            <p style="color: #dc3545; font-weight: 600;">この操作は取り消せません。</p>
        </div>
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button" class="modal-close-btn" style="background: #6c757d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer;">キャンセル</button>
            <!-- Note: Avoid nested forms inside the edit form. Use a standalone hidden form submitted via JS. -->
            <button type="button" id="delete-connection-submit" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer;">削除実行</button>
        </div>
    </div>
</div>

<script>
// Initialize handlers immediately if DOM is already ready, else on DOMContentLoaded.
(function initRouteConnectionsPartial() {
    function init() {
    let connectionIndex = 0;
    let deleteActionUrl = null;
    // Create a hidden, standalone form for delete to avoid nesting inside the edit form
    let hiddenDeleteForm = document.getElementById('delete-connection-form-standalone');
    if (!hiddenDeleteForm) {
        hiddenDeleteForm = document.createElement('form');
        hiddenDeleteForm.id = 'delete-connection-form-standalone';
        hiddenDeleteForm.method = 'POST';
        hiddenDeleteForm.style.display = 'none';
        // CSRF token - より確実な方法で取得
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        // CSRFトークンを直接埋め込み
        csrf.value = '{{ csrf_token() }}';
        // Method spoofing
        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        hiddenDeleteForm.appendChild(csrf);
        hiddenDeleteForm.appendChild(method);
        document.body.appendChild(hiddenDeleteForm);
    }
    
    // 接続追加ボタン
    const addConnectionBtn = document.getElementById('add-connection-btn');
    if (addConnectionBtn) {
        addConnectionBtn.addEventListener('click', function() {
            addConnectionForm();
        });
    } else {
        console.log('Add connection button not found - this is normal for create forms');
    }
    
    // 接続フォーム追加
    function addConnectionForm() {
        const template = document.getElementById('connection-template');
        const container = document.getElementById('connections-container');
        
        // テンプレートをコピー
        const newForm = template.cloneNode(true);
        newForm.style.display = 'block';
        newForm.id = 'connection-form-' + connectionIndex;
        
        // インデックスを置換
        newForm.innerHTML = newForm.innerHTML.replace(/INDEX/g, connectionIndex);
        newForm.querySelector('.connection-index').textContent = connectionIndex + 1;

    // 入力を有効化（テンプレートは disabled のまま送信対象外）
    newForm.querySelectorAll('select, input').forEach(el => el.disabled = false);
        
        // 削除ボタンのイベント
        newForm.querySelector('.remove-connection').addEventListener('click', function() {
            newForm.remove();
            updateConnectionIndexes();
        });
        
        container.appendChild(newForm);
        connectionIndex++;
        
        updateConnectionIndexes();
    }
    
    // インデックス更新
    function updateConnectionIndexes() {
        const forms = document.querySelectorAll('#connections-container .connection-form');
        forms.forEach((form, index) => {
            form.querySelector('.connection-index').textContent = index + 1;
            
            // name属性も更新
            const inputs = form.querySelectorAll('[name*="["]');
            inputs.forEach(input => {
                const name = input.name;
                const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                input.name = newName;
            });
        });
    }
    
    // 既存接続削除 - イベント委譲を使用して確実にバインド
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-existing-connection')) {
            const button = e.target.closest('.delete-existing-connection');
            const connectionId = button.dataset.connectionId;
            const targetName = button.dataset.targetName;
            
            console.log('Delete button clicked (event delegation):', { connectionId, targetName });
            
            if (!connectionId) {
                console.error('Connection ID not found');
                alert('接続IDが見つかりません。');
                return;
            }
            
            document.getElementById('delete-target-name').textContent = targetName || 'Unknown';
            // より安全なURL生成方法
            deleteActionUrl = '{!! route("admin.route-connections.destroy", ["route_connection" => "__PLACEHOLDER__"]) !!}'.replace('__PLACEHOLDER__', connectionId);
            
            console.log('Generated delete URL:', deleteActionUrl);
            
            showModal();
        }
    });

    // Wire up the modal's delete submit button to submit the hidden form
    const deleteSubmitBtn = document.getElementById('delete-connection-submit');
    if (deleteSubmitBtn) {
        deleteSubmitBtn.addEventListener('click', function() {
            if (!deleteActionUrl) {
                console.error('Delete URL not set');
                alert('削除URLが設定されていません。ページを再読み込みしてください。');
                return;
            }
            
            console.log('Submitting delete request to:', deleteActionUrl);
            console.log('Form CSRF token:', hiddenDeleteForm.querySelector('input[name="_token"]').value);
            console.log('Form method:', hiddenDeleteForm.querySelector('input[name="_method"]').value);
            
            // Add loading state
            deleteSubmitBtn.disabled = true;
            deleteSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 削除中...';
            
            hiddenDeleteForm.action = deleteActionUrl;
            hiddenDeleteForm.submit();
        });
    }
    
    // モーダルクローズボタンのイベントリスナー
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close-btn')) {
            closeModal();
        }
    });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// モーダル表示・非表示処理 - グローバルスコープで定義
window.showModal = function() {
    document.getElementById('deleteConnectionModal').style.display = 'block';
}

window.closeModal = function() {
    document.getElementById('deleteConnectionModal').style.display = 'none';
}

// モーダル外クリックで閉じる
document.addEventListener('click', function(e) {
    const modal = document.getElementById('deleteConnectionModal');
    if (e.target === modal) {
        closeModal();
    }
});
</script>

<style>
.connection-form {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

.connection-form:hover {
    background-color: #e9ecef;
}

#connections-section .form-select,
#connections-section .form-control {
    font-size: 0.9rem;
}
</style>