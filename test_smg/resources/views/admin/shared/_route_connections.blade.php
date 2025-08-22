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
    $existing_connections = $location ? $location->connections()->with('targetLocation')->get() : collect();
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
        @if($existing_connections->isNotEmpty())
            <h6 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">現在の接続</h6>
            <div style="margin-bottom: 1rem; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">接続先</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">タイプ</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">方向</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($existing_connections as $connection)
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 0.75rem;">
                                <a href="{{ route('admin.locations.show', $connection->target_location_id) }}" target="_blank" style="color: var(--admin-primary, #007bff); text-decoration: none;">
                                    {{ $connection->targetLocation?->name ?? 'Unknown' }}
                                </a>
                            </td>
                            <td style="padding: 0.75rem;">
                                <span style="background: #6c757d; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">{{ $connection->connection_type }}</span>
                            </td>
                            <td style="padding: 0.75rem;">{{ $connection->direction ?? 'N/A' }}</td>
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
                                            data-target-name="{{ $connection->targetLocation?->name }}" title="削除">
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
                
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">接続先ロケーション <span style="color: #dc3545;">*</span></label>
                        <select style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][target_location_id]" required>
                            <option value="">選択してください</option>
                            @foreach($available_locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->category }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">接続タイプ <span style="color: #dc3545;">*</span></label>
                        <select style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][connection_type]" required>
                            <option value="">選択してください</option>
                            <option value="start">開始点</option>
                            <option value="end">終了点</option>
                            <option value="bidirectional">双方向</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">位置</label>
                        <input type="number" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][position]" min="0" placeholder="並び順">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">方向</label>
                        <input type="text" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 0.875rem;" name="{{ $form_prefix }}[INDEX][direction]" placeholder="例: 北、南東">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 接続削除確認モーダル -->
<div class="modal fade" id="deleteConnectionModal" tabindex="-1" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 0.5rem; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #dee2e6;">
            <h5 style="margin: 0; font-size: 1.25rem; font-weight: 600;">接続削除確認</h5>
            <button type="button" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; font-size: 1.5rem; cursor: pointer;" onclick="closeModal()">×</button>
        </div>
        <div style="padding: 1.5rem;">
            <p>接続先「<span id="delete-target-name"></span>」への接続を削除しますか？</p>
            <p style="color: #dc3545; font-weight: 600;">この操作は取り消せません。</p>
        </div>
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button" style="background: #6c757d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer;" onclick="closeModal()">キャンセル</button>
            <form id="delete-connection-form" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer;">削除実行</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let connectionIndex = 0;
    
    // 接続追加ボタン
    document.getElementById('add-connection-btn').addEventListener('click', function() {
        addConnectionForm();
    });
    
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
    
    // 既存接続削除
    document.querySelectorAll('.delete-existing-connection').forEach(button => {
        button.addEventListener('click', function() {
            const connectionId = this.dataset.connectionId;
            const targetName = this.dataset.targetName;
            
            document.getElementById('delete-target-name').textContent = targetName;
            document.getElementById('delete-connection-form').action = 
                '{{ route("admin.route-connections.destroy", ":id") }}'.replace(':id', connectionId);
            
            showModal();
        });
    });
});

// モーダル表示・非表示処理
function showModal() {
    document.getElementById('deleteConnectionModal').style.display = 'block';
}

function closeModal() {
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