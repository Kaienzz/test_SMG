<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>インベントリー - RPGゲーム</title>
    <style>
        :root {
            /* design_rules.mdのカラーシステムを使用 */
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-100: #dbeafe;
            --success: #059669;
            --warning: #d97706;
            --error: #dc2626;
            --gray-100: #f4f4f5;
            --gray-200: #e4e4e7;
            --gray-300: #d4d4d8;
            --gray-500: #71717a;
            --bg-primary: #ffffff;
            --bg-secondary: #fafafa;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-light: #e5e7eb;
            --border-medium: #d1d5db;
            
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
            --shadow-card: 0 2px 8px 0 rgba(0, 0, 0, 0.08);
            --shadow-button: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            --shadow-focus: 0 0 0 3px rgba(59, 130, 246, 0.3);
            --font-primary: system-ui, -apple-system, 'Segoe UI', 'Noto Sans JP', sans-serif;
        }

        body {
            font-family: var(--font-primary);
            background: var(--bg-secondary);
            color: var(--text-primary);
            margin: 0;
            padding: var(--space-4);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .nav-link {
            display: inline-block;
            margin-right: var(--space-3);
            padding: var(--space-2) var(--space-4);
            background: var(--gray-100);
            color: var(--primary-500);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: var(--primary-100);
        }

        .game-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: var(--space-5);
            box-shadow: var(--shadow-card);
            border: 2px solid var(--border-light);
            margin-bottom: var(--space-6);
        }

        .game-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 var(--space-4) 0;
            text-align: center;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }

        .inventory-slot {
            aspect-ratio: 1;
            border: 2px solid var(--border-medium);
            border-radius: var(--radius-lg);
            padding: var(--space-2);
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 80px;
        }

        .inventory-slot:hover {
            border-color: var(--primary-500);
            background: var(--primary-100);
        }

        .inventory-slot.empty {
            opacity: 0.5;
        }

        .inventory-slot.has-item {
            background: var(--bg-primary);
            border-color: var(--success);
        }

        .slot-number {
            position: absolute;
            top: 2px;
            left: 4px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .item-name {
            font-weight: 600;
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: var(--space-1);
        }

        .item-quantity {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .item-durability {
            font-size: 0.75rem;
            color: var(--warning);
            text-align: center;
        }

        .rarity-1 { border-left: 4px solid #9ca3af; }
        .rarity-2 { border-left: 4px solid #10b981; }
        .rarity-3 { border-left: 4px solid #3b82f6; }
        .rarity-4 { border-left: 4px solid #8b5cf6; }
        .rarity-5 { border-left: 4px solid #f59e0b; }

        .btn {
            padding: var(--space-3) var(--space-5);
            border-radius: var(--radius-md);
            font-weight: 500;
            border: 2px solid transparent;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-button);
            margin: var(--space-2);
        }

        .btn-primary {
            background: var(--primary-500);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-600);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--text-primary);
            border-color: var(--border-medium);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .button-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: var(--space-4) 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: var(--space-2) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .message {
            margin: var(--space-4) 0;
            padding: var(--space-3);
            border-radius: var(--radius-md);
            text-align: center;
            font-weight: 500;
            display: none;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .item-controls {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-primary);
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-md);
            padding: var(--space-2);
            box-shadow: var(--shadow-card);
            z-index: 10;
        }

        .inventory-slot:hover .item-controls {
            display: block;
        }

        .control-btn {
            display: block;
            width: 100%;
            padding: var(--space-1) var(--space-2);
            margin-bottom: var(--space-1);
            background: var(--primary-500);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            cursor: pointer;
        }

        .control-btn:hover {
            background: var(--primary-600);
        }

        .control-btn.danger {
            background: var(--error);
        }

        .add-item-section {
            background: var(--bg-primary);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .item-select {
            width: 100%;
            padding: var(--space-3);
            border: 2px solid var(--border-medium);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-3);
            font-family: var(--font-primary);
        }

        .quantity-input {
            width: 80px;
            padding: var(--space-2);
            border: 2px solid var(--border-medium);
            border-radius: var(--radius-md);
            text-align: center;
            margin: 0 var(--space-2);
        }

        @media (max-width: 768px) {
            .inventory-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="/character" class="nav-link">キャラクター</a>
            <a href="/skills" class="nav-link">スキル</a>
            <a href="/game" class="nav-link">ゲーム</a>
            <a href="/" class="nav-link">ホーム</a>
        </nav>

        <h1 style="text-align: center; margin-bottom: var(--space-6);">インベントリー</h1>

        <div class="stats-grid">
            <div class="game-card">
                <h3 class="game-card-title">インベントリ情報</h3>
                <div class="stat-item">
                    <span>使用中スロット</span>
                    <span id="used-slots">{{ $inventoryData['used_slots'] }}</span>
                </div>
                <div class="stat-item">
                    <span>最大スロット</span>
                    <span id="max-slots">{{ $inventoryData['max_slots'] }}</span>
                </div>
                <div class="stat-item">
                    <span>空きスロット</span>
                    <span id="available-slots">{{ $inventoryData['available_slots'] }}</span>
                </div>
            </div>

            <div class="game-card">
                <h3 class="game-card-title">キャラクター</h3>
                <div class="stat-item">
                    <span>名前</span>
                    <span>{{ $character->name }}</span>
                </div>
                <div class="stat-item">
                    <span>レベル</span>
                    <span>{{ $character->level }}</span>
                </div>
                <div class="stat-item">
                    <span>HP</span>
                    <span id="character-hp">{{ $character->hp }}/{{ $character->max_hp }}</span>
                </div>
                <div class="stat-item">
                    <span>MP</span>
                    <span id="character-mp">{{ $character->mp }}/{{ $character->max_mp }}</span>
                </div>
                <div class="stat-item">
                    <span>SP</span>
                    <span id="character-sp">{{ $character->sp }}/{{ $character->max_sp }}</span>
                </div>
            </div>
        </div>

        <div id="message" class="message"></div>

        <div class="game-card">
            <h2 class="game-card-title">アイテムスロット</h2>
            <div class="inventory-grid" id="inventory-grid">
                @foreach($inventoryData['slots'] as $index => $slot)
                    <div class="inventory-slot {{ isset($slot['empty']) ? 'empty' : 'has-item' }} {{ isset($slot['item_info']['rarity']) ? 'rarity-' . $slot['item_info']['rarity'] : '' }}" 
                         data-slot="{{ $index }}" 
                         onclick="selectSlot({{ $index }})">
                        <div class="slot-number">{{ $index + 1 }}</div>
                        
                        @if(!isset($slot['empty']))
                            <div class="item-name">{{ $slot['item_name'] }}</div>
                            @if(isset($slot['item_info']) && isset($slot['item_info']['has_stack_limit']) && $slot['item_info']['has_stack_limit'])
                                <div class="item-quantity">{{ $slot['quantity'] }}/{{ $slot['item_info']['stack_limit'] ?? 50 }}</div>
                            @elseif($slot['quantity'] > 1)
                                <div class="item-quantity">x{{ $slot['quantity'] }}</div>
                            @endif
                            @if(!is_null($slot['durability']))
                                <div class="item-durability">{{ $slot['durability'] }}/{{ $slot['item_info']['max_durability'] ?? 100 }}</div>
                            @endif
                            
                            <div class="item-controls">
                                @if(isset($slot['item_info']) && $slot['item_info']['is_usable'])
                                    <button class="control-btn" onclick="useItem({{ $index }})">使用</button>
                                @endif
                                <button class="control-btn" onclick="removeItem({{ $index }}, 1)">削除1</button>
                                <button class="control-btn danger" onclick="removeItem({{ $index }}, {{ $slot['quantity'] }})">全削除</button>
                            </div>
                        @else
                            <div style="color: var(--text-secondary); font-size: 0.875rem;">空き</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="add-item-section">
            <h3 style="margin-bottom: var(--space-4);">アイテム追加</h3>
            <div style="display: flex; align-items: center; flex-wrap: wrap;">
                <select class="item-select" id="item-select" style="flex: 1; min-width: 200px;">
                    <option value="">アイテムを選択してください</option>
                    @foreach($sampleItems as $item)
                        <option value="{{ $item['name'] }}">
                            {{ $item['name'] }} ({{ $item['category_name'] }})
                        </option>
                    @endforeach
                </select>
                
                <input type="number" class="quantity-input" id="quantity-input" value="1" min="1" max="999">
                
                <button class="btn btn-primary" onclick="addSelectedItem()">追加</button>
            </div>
        </div>

        <div class="game-card">
            <h3 class="game-card-title">管理機能</h3>
            <div class="button-group">
                <button class="btn btn-success" onclick="addSampleItems()">サンプルアイテム追加</button>
                <button class="btn btn-warning" onclick="expandSlots(2)">スロット拡張 (+2)</button>
                <button class="btn btn-secondary" onclick="clearInventory()">インベントリクリア</button>
            </div>
        </div>
    </div>

    <script>
        let selectedSlot = null;

        function showMessage(text, type = 'success') {
            const messageEl = document.getElementById('message');
            messageEl.textContent = text;
            messageEl.className = `message ${type}`;
            messageEl.style.display = 'block';
        }

        function selectSlot(slotIndex) {
            // スロット選択のビジュアルフィードバック
            document.querySelectorAll('.inventory-slot').forEach(slot => {
                slot.style.outline = '';
            });
            
            const slot = document.querySelector(`[data-slot="${slotIndex}"]`);
            if (slot) {
                slot.style.outline = '2px solid var(--primary-500)';
                selectedSlot = slotIndex;
            }
        }

        function refreshInventory() {
            fetch('/inventory/show')
                .then(response => response.json())
                .then(data => {
                    updateInventoryDisplay(data.inventory);
                    updateCharacterDisplay(data.character);
                });
        }

        function updateInventoryDisplay(inventoryData) {
            document.getElementById('used-slots').textContent = inventoryData.used_slots;
            document.getElementById('max-slots').textContent = inventoryData.max_slots;
            document.getElementById('available-slots').textContent = inventoryData.available_slots;
            
            // インベントリグリッドを再構築
            const grid = document.getElementById('inventory-grid');
            grid.innerHTML = '';
            
            for (let i = 0; i < inventoryData.max_slots; i++) {
                const slot = inventoryData.slots[i];
                const slotEl = document.createElement('div');
                slotEl.className = `inventory-slot ${slot.empty ? 'empty' : 'has-item'}`;
                if (slot.item_info && slot.item_info.rarity) {
                    slotEl.className += ` rarity-${slot.item_info.rarity}`;
                }
                slotEl.setAttribute('data-slot', i);
                slotEl.onclick = () => selectSlot(i);
                
                let innerHTML = `<div class="slot-number">${i + 1}</div>`;
                
                if (!slot.empty) {
                    innerHTML += `<div class="item-name">${slot.item_name}</div>`;
                    if (slot.item_info && slot.item_info.has_stack_limit && slot.item_info.stack_limit) {
                        innerHTML += `<div class="item-quantity">${slot.quantity}/${slot.item_info.stack_limit}</div>`;
                    } else if (slot.quantity > 1) {
                        innerHTML += `<div class="item-quantity">x${slot.quantity}</div>`;
                    }
                    if (slot.durability !== null) {
                        const maxDur = slot.item_info && slot.item_info.max_durability ? slot.item_info.max_durability : 100;
                        innerHTML += `<div class="item-durability">${slot.durability}/${maxDur}</div>`;
                    }
                    
                    innerHTML += '<div class="item-controls">';
                    if (slot.item_info && slot.item_info.is_usable) {
                        innerHTML += `<button class="control-btn" onclick="useItem(${i})">使用</button>`;
                    }
                    innerHTML += `<button class="control-btn" onclick="removeItem(${i}, 1)">削除1</button>`;
                    innerHTML += `<button class="control-btn danger" onclick="removeItem(${i}, ${slot.quantity})">全削除</button>`;
                    innerHTML += '</div>';
                } else {
                    innerHTML += '<div style="color: var(--text-secondary); font-size: 0.875rem;">空き</div>';
                }
                
                slotEl.innerHTML = innerHTML;
                grid.appendChild(slotEl);
            }
        }

        function updateCharacterDisplay(character) {
            document.getElementById('character-hp').textContent = `${character.hp}/${character.max_hp}`;
            document.getElementById('character-mp').textContent = `${character.mp}/${character.max_mp}`;
            document.getElementById('character-sp').textContent = `${character.sp}/${character.max_sp}`;
        }

        function addSelectedItem() {
            const itemName = document.getElementById('item-select').value;
            const quantity = parseInt(document.getElementById('quantity-input').value) || 1;
            
            if (!itemName) {
                showMessage('アイテムを選択してください', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('item_name', itemName);
            formData.append('quantity', quantity);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch('/inventory/add-item', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    updateInventoryDisplay(data.inventory);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('アイテム追加中にエラーが発生しました: ' + error.message, 'error');
            });
        }

        function removeItem(slotIndex, quantity) {
            const formData = new FormData();
            formData.append('slot_index', slotIndex);
            formData.append('quantity', quantity);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch('/inventory/remove-item', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    updateInventoryDisplay(data.inventory);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('削除中にエラーが発生しました: ' + error.message, 'error');
            });
        }

        function useItem(slotIndex) {
            const formData = new FormData();
            formData.append('slot_index', slotIndex);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch('/inventory/use-item', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    updateInventoryDisplay(data.inventory);
                    updateCharacterDisplay(data.character);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('使用中にエラーが発生しました: ' + error.message, 'error');
            });
        }

        function addSampleItems() {
            fetch('/inventory/add-sample-items', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    updateInventoryDisplay(data.inventory);
                }
            });
        }

        function expandSlots(additionalSlots) {
            fetch('/inventory/expand-slots', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    additional_slots: additionalSlots
                })
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    updateInventoryDisplay(data.inventory);
                }
            });
        }

        function clearInventory() {
            if (confirm('インベントリをクリアしますか？')) {
                fetch('/inventory/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showMessage(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        updateInventoryDisplay(data.inventory);
                    }
                });
            }
        }
    </script>
</body>
</html>