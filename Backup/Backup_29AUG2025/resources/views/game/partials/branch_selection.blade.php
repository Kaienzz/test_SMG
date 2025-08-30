{{-- 分岐選択UI --}}
@if($player->location_type === 'road')
    @php
        // LocationServiceを使用して分岐情報を取得
        $locationService = app(\App\Domain\Location\LocationService::class);
        $branchOptions = $locationService->getBranchOptions($player->location_id, $player->game_position);
    @endphp
    
    @if($branchOptions)
        <div class="branch-selection" id="branch-selection" style="background-color: #fff3e0; border: 2px solid #ff9800; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h3 style="color: #e65100; margin-top: 0;">🛤️ 分岐点です</h3>
            <p>進む方向を選択してください：</p>
            
            <div class="branch-options" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                @foreach($branchOptions as $direction => $destination)
                    <button 
                        class="btn btn-warning branch-btn" 
                        onclick="selectBranch('{{ $direction }}')"
                        style="min-width: 120px; margin: 5px;"
                        data-direction="{{ $direction }}"
                        data-destination-type="{{ $destination['type'] }}"
                        data-destination-id="{{ $destination['id'] }}"
                        data-destination-name="{{ $destination['name'] }}"
                    >
                        <span class="direction-icon">
                            @if($direction === 'straight')
                                ⬆️
                            @elseif($direction === 'left')  
                                ⬅️
                            @elseif($direction === 'right')
                                ➡️
                            @else
                                🧭
                            @endif
                        </span>
                        <div>
                            <strong>{{ $destination['direction_label'] }}</strong><br>
                            <small>{{ $destination['name'] }}</small>
                        </div>
                    </button>
                @endforeach
            </div>
            
            {{-- 分岐選択の説明 --}}
            <div class="branch-help" style="margin-top: 15px; padding: 10px; background-color: #f5f5f5; border-radius: 4px; font-size: 0.9em; color: #666;">
                💡 分岐点では通常の移動はできません。上記のボタンから進路を選択してください。
            </div>
        </div>
        
        {{-- 通常の移動ボタンを非表示にする --}}
        <style>
            #movement-controls {
                display: none !important;
            }
            #next-location-info {
                display: none !important;
            }
        </style>
    @endif
@endif