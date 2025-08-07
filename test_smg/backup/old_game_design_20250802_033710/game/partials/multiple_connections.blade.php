{{-- 複数接続選択UI --}}
@if($player->location_type === 'town')
    @php
        // LocationServiceを使用して複数接続情報を取得
        $locationService = app(\App\Domain\Location\LocationService::class);
        $townConnections = $locationService->getTownConnections($player->location_id);
        $hasMultipleConnections = $locationService->hasMultipleConnections($player->location_id);
    @endphp
    
    @if($hasMultipleConnections && $townConnections)
        <div class="multiple-connections" id="multiple-connections" style="background-color: #e8f5e8; border: 2px solid #4caf50; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h3 style="color: #2e7d32; margin-top: 0;">🗺️ 複数の道が繋がっています</h3>
            <p>進む方向を選択してください：</p>
            
            <div class="connection-options" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
                @foreach($townConnections as $direction => $destination)
                    <button 
                        class="btn btn-success connection-btn" 
                        onclick="moveToDirection('{{ $direction }}')"
                        style="min-height: 80px; padding: 15px; text-align: center; position: relative;"
                        data-direction="{{ $direction }}"
                        data-destination-type="{{ $destination['type'] }}"
                        data-destination-id="{{ $destination['id'] }}"
                        data-destination-name="{{ $destination['name'] }}"
                    >
                        <div class="direction-info">
                            <span class="direction-icon" style="font-size: 1.5em; display: block; margin-bottom: 5px;">
                                @if($direction === 'north')
                                    ⬆️
                                @elseif($direction === 'south')
                                    ⬇️
                                @elseif($direction === 'east')
                                    ➡️
                                @elseif($direction === 'west')
                                    ⬅️
                                @else
                                    🧭
                                @endif
                            </span>
                            <div style="font-weight: bold; margin-bottom: 3px;">
                                {{ $destination['direction_label'] }}
                            </div>
                            <div style="font-size: 0.9em; color: #1b5e20;">
                                {{ $destination['name'] }}
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
            
            {{-- 複数接続の説明 --}}
            <div class="connection-help" style="margin-top: 15px; padding: 10px; background-color: #f1f8e9; border-radius: 4px; font-size: 0.9em; color: #33691e;">
                💡 この町からは複数の道に向かうことができます。行きたい方向のボタンを選択してください。
            </div>
        </div>
        
        {{-- 単一接続用のボタンを非表示にする --}}
        <style>
            #next-location-info {
                display: none !important;
            }
        </style>
    @endif
@endif