{{-- 新システム：接続ベース複数接続選択UI --}}
@if($player->location_type === 'town' && isset($availableConnections) && count($availableConnections) > 1)
    <div class="multiple-connections" id="multiple-connections" style="background-color: #e8f5e8; border: 2px solid #4caf50; border-radius: 8px; padding: 15px; margin: 15px 0;">
        <h3 style="color: #2e7d32; margin-top: 0;">🗺️ 複数の道が繋がっています</h3>
        <p>進む方向を選択してください：</p>
        
        <div class="connection-options" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
            @foreach($availableConnections as $connection)
                @php
                    $actionText = $connection['action_text'] ?? ($connection['target_location']->name . 'に移動する');
                    $keyboardDisplay = $connection['keyboard_display'] ?? '';
                    $targetName = $connection['target_location']->name ?? '不明な場所';
                    
                    // Edge type based icons
                    $icon = match($connection['edge_type']) {
                        'portal' => '🌀',
                        'exit' => '🚪',
                        'enter' => '↩️',
                        'branch' => '🔀',
                        default => '🚶'
                    };
                    
                    // Action label based icons (fallback)
                    if ($icon === '🚶' && isset($connection['action_label'])) {
                        $icon = match($connection['action_label']) {
                            'move_north' => '⬆️',
                            'move_south' => '⬇️',
                            'move_east' => '➡️',
                            'move_west' => '⬅️',
                            'turn_left' => '↩️',
                            'turn_right' => '↪️',
                            default => '🧭'
                        };
                    }
                @endphp
                <button 
                    class="btn btn-success connection-btn" 
                    onclick="moveToConnection('{{ $connection['id'] }}')"
                    style="min-height: 80px; padding: 15px; text-align: center; position: relative;"
                    data-connection-id="{{ $connection['id'] }}"
                    data-keyboard="{{ $connection['keyboard_shortcut'] }}"
                    data-destination-type="{{ $connection['target_location']->category }}"
                    data-destination-id="{{ $connection['target_location']->id }}"
                    data-destination-name="{{ $targetName }}"
                    title="{{ $actionText }}{{ $keyboardDisplay ? ' (' . $keyboardDisplay . ')' : '' }}"
                >
                    <div class="direction-info">
                        <span class="direction-icon" style="font-size: 1.5em; display: block; margin-bottom: 5px;">
                            {{ $icon }}
                        </span>
                        <div style="font-weight: bold; margin-bottom: 3px;">
                            {{ $actionText }}
                        </div>
                        <div style="font-size: 0.9em; color: #1b5e20;">
                            {{ $targetName }}
                        </div>
                        @if($keyboardDisplay)
                            <div style="position: absolute; top: 5px; right: 5px; background: rgba(33, 150, 243, 0.1); border: 1px solid rgba(33, 150, 243, 0.3); border-radius: 3px; padding: 2px 5px; font-size: 0.7em; font-weight: bold; font-family: monospace; color: #1976D2;">
                                {{ $keyboardDisplay }}
                            </div>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
        
        {{-- 複数接続の説明 --}}
        <div class="connection-help" style="margin-top: 15px; padding: 10px; background-color: #f1f8e9; border-radius: 4px; font-size: 0.9em; color: #33691e;">
            💡 この町からは複数の道に向かうことができます。行きたい方向のボタンを選択するか、キーボードショートカットを使用してください。
        </div>
    </div>
    
    {{-- 単一接続用のボタンを非表示にする --}}
    <style>
        #next-location-info {
            display: none !important;
        }
    </style>
@endif