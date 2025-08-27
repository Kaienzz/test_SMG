{{-- Town State - Left Area: Movement Selection and Town Facilities --}}

{{-- 1. Movement Options --}}
<div class="movement-section">
    <h3>移動先選択</h3>
    
    {{-- Actual Town Connections from LocationService --}}
    <div class="connection-options">
        @if(isset($townConnections) && !empty($townConnections))
            @foreach($townConnections as $direction => $connection)
                @php
                    $directionIcons = [
                        'north' => '⬆️',
                        'south' => '⬇️', 
                        'east' => '➡️',
                        'west' => '⬅️'
                    ];
                    $icon = $directionIcons[$direction] ?? '🚪';
                @endphp
                <button 
                    class="connection-btn"
                    onclick="moveToDirection('{{ $direction }}')"
                    title="{{ $connection['name'] ?? 'Unknown destination' }}"
                    data-direction="{{ $direction }}"
                >
                    <span class="direction-icon">{{ $icon }}</span>
                    <div class="direction-info">
                        <span class="direction-label">{{ $connection['direction_label'] ?? ucfirst($direction) }}</span>
                        <span class="destination-name">{{ $connection['name'] ?? 'Unknown' }}</span>
                    </div>
                </button>
            @endforeach
        @else
            <div class="no-connections">
                <p class="help-text">
                    <span class="help-icon">🚫</span>
                    この町からは移動できません
                </p>
            </div>
        @endif
    </div>

    <div class="movement-help">
        <p class="help-text">
            <span class="help-icon">💡</span>
            道を選択して冒険に出発しましょう！
        </p>
    </div>
</div>

{{-- 2. Town Facilities --}}
<div class="town-facilities">
    <h4>町の施設</h4>
    @php
        // 実装済み施設を取得
        $townFacilities = \App\Models\TownFacility::getFacilitiesByLocation($player->location_id ?? 'town_a', 'town');
    @endphp

    <div class="facility-list">
        @if($townFacilities->count() > 0)
            @foreach($townFacilities as $facility)
                @php
                    $facilityType = \App\Enums\FacilityType::from($facility->facility_type);
                    $routeName = match($facilityType) {
                        \App\Enums\FacilityType::ITEM_SHOP => 'facilities.item.index',
                        \App\Enums\FacilityType::BLACKSMITH => 'facilities.blacksmith.index',
                        \App\Enums\FacilityType::TAVERN => 'facilities.tavern.index',
                        \App\Enums\FacilityType::ALCHEMY_SHOP => 'facilities.alchemy.index',
                        default => null
                    };
                @endphp
                
                @if($routeName && \Route::has($routeName))
                    <div class="facility-item" title="{{ $facility->description ?? $facilityType->getDescription() }}">
                        <a href="{{ route($routeName) }}" class="facility-link">
                            <span class="facility-icon">{{ $facilityType->getIcon() }}</span>
                            <span class="facility-name">{{ $facility->name }}</span>
                        </a>
                    </div>
                @endif
            @endforeach
        @else
            {{-- フォールバック表示 --}}
            <div class="facility-item">
                <a href="#" class="facility-link" onclick="gameManager.showNotification('この町には店がありません', 'info')">
                    <span class="facility-icon">🏪</span>
                    <span class="facility-name">施設なし</span>
                </a>
            </div>
        @endif
    </div>
</div>

