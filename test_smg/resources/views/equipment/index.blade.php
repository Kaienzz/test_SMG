<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>装備変更 - {{ ($player ?? $character)->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #3498db;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .character-info {
            background: rgba(52, 73, 94, 0.8);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 2px solid #3498db;
        }

        .equipment-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .equipment-panel, .inventory-panel {
            background: rgba(44, 62, 80, 0.9);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #34495e;
        }

        .panel-title {
            color: #e74c3c;
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 10px;
        }

        .equipment-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .equipment-slot {
            background: rgba(52, 73, 94, 0.8);
            border: 2px dashed #7f8c8d;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            min-height: 120px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .equipment-slot:hover {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.1);
        }

        .equipment-slot.equipped {
            border: 2px solid #27ae60;
            background: rgba(39, 174, 96, 0.1);
        }

        .slot-label {
            font-weight: bold;
            color: #3498db;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .slot-item {
            color: #ecf0f1;
            font-size: 0.8rem;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .item-stats {
            font-size: 0.7rem;
            color: #bdc3c7;
        }

        .stats-summary {
            background: rgba(39, 174, 96, 0.2);
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #27ae60;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .stat-item {
            text-align: center;
            padding: 8px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }

        .stat-label {
            color: #bdc3c7;
            font-size: 0.8rem;
        }

        .stat-value {
            color: #27ae60;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .inventory-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
        }

        .inventory-item {
            background: rgba(52, 73, 94, 0.8);
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #7f8c8d;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .inventory-item:hover {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.1);
        }

        .inventory-item.equippable {
            border-color: #27ae60;
        }

        .sample-equipment {
            margin-top: 30px;
            background: rgba(142, 68, 173, 0.2);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #8e44ad;
        }

        .category-section {
            margin-bottom: 20px;
        }

        .category-title {
            color: #9b59b6;
            font-size: 1.2rem;
            margin-bottom: 10px;
            border-bottom: 1px solid #9b59b6;
            padding-bottom: 5px;
        }

        .sample-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }

        .sample-item {
            background: rgba(52, 73, 94, 0.8);
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #8e44ad;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sample-item:hover {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
        }

        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: #27ae60;
        }

        .btn-success:hover {
            background: #229954;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }

        .message.success {
            background: #27ae60;
            color: white;
        }

        .message.error {
            background: #e74c3c;
            color: white;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>装備変更</h1>
        </div>

        <div class="character-info">
            <h3>{{ ($player ?? $character)->name }} (Lv.{{ ($player ?? $character)->level }})</h3>
            <p>HP: {{ ($player ?? $character)->hp }}/{{ ($player ?? $character)->max_hp }} | SP: {{ ($player ?? $character)->sp }}/{{ ($player ?? $character)->max_sp }}</p>
        </div>

        <div class="equipment-container">
            <div class="equipment-panel">
                <h3 class="panel-title">装備中のアイテム</h3>
                
                <div class="equipment-slots">
                    @foreach(['weapon' => '武器', 'body_armor' => '胴体防具', 'shield' => '盾', 'helmet' => '兜', 'boots' => 'ブーツ', 'accessory' => 'アクセサリー'] as $slot => $label)
                        <div class="equipment-slot {{ $equippedItems[$slot] ? 'equipped' : '' }}" data-slot="{{ $slot }}">
                            <div class="slot-label">{{ $label }}</div>
                            @if($equippedItems[$slot])
                                <div class="slot-item">
                                    <div class="item-name">
                                        {{ $equippedItems[$slot]['name'] }}
                                    </div>
                                    <div class="item-stats">
                                        @foreach($equippedItems[$slot]['effects'] as $effect => $value)
                                            @if(is_numeric($value))
                                                {{ $effect }}: +{{ $value }}<br>
                                            @endif
                                        @endforeach
                                    </div>
                                    <button class="btn btn-danger" onclick="unequipItem('{{ $slot }}')">外す</button>
                                </div>
                            @else
                                <div class="slot-item">
                                    <em>なし</em><br>
                                    <button class="btn" onclick="showEquippableItems('{{ $slot }}')">装備</button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="stats-summary">
                    <h4 style="color: #27ae60; margin-bottom: 15px; text-align: center;">装備による合計ステータス</h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">攻撃力</div>
                            <div class="stat-value">+{{ $totalStats['attack'] }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">防御力</div>
                            <div class="stat-value">+{{ $totalStats['defense'] }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">素早さ</div>
                            <div class="stat-value">+{{ $totalStats['agility'] }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">回避</div>
                            <div class="stat-value">+{{ $totalStats['evasion'] }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">HP</div>
                            <div class="stat-value">+{{ $totalStats['hp'] }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">MP</div>
                            <div class="stat-value">+{{ $totalStats['mp'] }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">命中力</div>
                            <div class="stat-value">+{{ $totalStats['accuracy'] }}</div>
                        </div>
                        @if(!empty($totalStats['effects']))
                            <div class="stat-item" style="grid-column: span 2;">
                                <div class="stat-label">特殊効果</div>
                                <div class="stat-value">
                                    @foreach($totalStats['effects'] as $effect => $value)
                                        @if($effect === 'status_immunity')
                                            状態異常無効<br>
                                        @elseif($effect === 'dice_bonus')
                                            サイコロ目+{{ $value }}<br>
                                        @elseif($effect === 'extra_dice')
                                            追加サイコロ+{{ $value }}<br>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="inventory-panel">
                <h3 class="panel-title">インベントリー</h3>
                <div class="inventory-items" id="inventory-items">
                    @foreach($inventoryItems as $inventoryItem)
                        @if($inventoryItem['item']['is_equippable'])
                            <div class="inventory-item equippable" 
                                 data-item-id="{{ $inventoryItem['item']['id'] }}"
                                 data-category="{{ $inventoryItem['item']['category'] }}">
                                <div class="item-name">
                                    {{ $inventoryItem['item']['name'] }}
                                </div>
                                <div class="item-stats">
                                    {{ $inventoryItem['item']['description'] }}<br>
                                    数量: {{ $inventoryItem['quantity'] }}<br>
                                    @foreach($inventoryItem['item']['effects'] as $effect => $value)
                                        @if(is_numeric($value))
                                            {{ $effect }}: +{{ $value }}<br>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="sample-equipment">
            <h3 class="panel-title">サンプル装備アイテムを追加</h3>
            @foreach($sampleEquipmentItems as $category => $items)
                <div class="category-section">
                    <h4 class="category-title">{{ 
                        $category === 'weapons' ? '武器' : 
                        ($category === 'body_armor' ? '胴体防具' : 
                        ($category === 'shields' ? '盾' : 
                        ($category === 'helmets' ? '兜' : 
                        ($category === 'boots' ? 'ブーツ' : 'アクセサリー'))))
                    }}</h4>
                    <div class="sample-items">
                        @foreach($items as $item)
                            <div class="sample-item" onclick="addSampleItem('{{ $category }}', '{{ $item['name'] }}')">
                                <div class="item-name">{{ $item['name'] }}</div>
                                <div class="item-stats">
                                    {{ $item['description'] }}<br>
                                    @foreach($item['effects'] as $effect => $value)
                                        @if(is_numeric($value))
                                            {{ $effect }}: +{{ $value }}<br>
                                        @elseif($effect === 'status_immunity')
                                            状態異常無効<br>
                                        @elseif($effect === 'dice_bonus')
                                            サイコロ目+{{ $value }}<br>
                                        @elseif($effect === 'extra_dice')
                                            追加サイコロ+{{ $value }}<br>
                                        @endif
                                    @endforeach
                                </div>
                                <button class="btn btn-success">追加</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="message" id="message"></div>

    <script>
        const playerId = {{ ($player ?? $character)->id }};
        const characterId = playerId; // 下位互換性
        let currentSelectingSlot = null;

        function showMessage(text, type = 'success') {
            const message = document.getElementById('message');
            message.textContent = text;
            message.className = `message ${type}`;
            message.style.display = 'block';
            
            setTimeout(() => {
                message.style.display = 'none';
            }, 3000);
        }

        function showEquippableItems(slot) {
            currentSelectingSlot = slot;
            const items = document.querySelectorAll('.inventory-item.equippable');
            
            items.forEach(item => {
                const category = item.dataset.category;
                const isEquippableForSlot = isItemEquippableForSlot(category, slot);
                
                if (isEquippableForSlot) {
                    item.style.border = '2px solid #e74c3c';
                    item.onclick = () => equipItemFromInventory(item.dataset.itemId, slot);
                } else {
                    item.style.border = '1px solid #7f8c8d';
                    item.onclick = null;
                }
            });
            
            showMessage(`${getSlotName(slot)}に装備可能なアイテムを選択してください`, 'info');
        }

        function isItemEquippableForSlot(category, slot) {
            const slotCategoryMap = {
                'weapon': ['weapon'],
                'body_armor': ['body_equipment'],
                'shield': ['shield'],
                'helmet': ['head_equipment'],
                'boots': ['foot_equipment'],
                'accessory': ['accessory']
            };

            return slotCategoryMap[slot] && slotCategoryMap[slot].includes(category);
        }

        function getSlotName(slot) {
            const slotNames = {
                'weapon': '武器',
                'body_armor': '胴体防具',
                'shield': '盾',
                'helmet': '兜',
                'boots': 'ブーツ',
                'accessory': 'アクセサリー'
            };
            return slotNames[slot] || slot;
        }

        function equipItemFromInventory(itemId, slot) {
            fetch('/equipment/equip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    character_id: characterId,
                    item_id: parseInt(itemId),
                    slot: slot
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('エラーが発生しました', 'error');
            });
        }

        function unequipItem(slot) {
            fetch('/equipment/unequip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    character_id: characterId,
                    slot: slot
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('エラーが発生しました', 'error');
            });
        }

        function addSampleItem(category, itemName) {
            fetch('/equipment/add-sample', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    character_id: characterId,
                    category: category,
                    item_name: itemName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('エラーが発生しました', 'error');
            });
        }
    </script>
</body>
</html>