{{-- Town State - Left Area: Merged Right + Left Content --}}

{{-- === RIGHT BAR CONTENT (First) === --}}

{{-- Movement Options --}}
<div class="movement-section">
    <h3>移動先選択</h3>
    
    {{-- Actual Town Connections from LocationService --}}
    <div class="connection-options">
        @if(isset($townConnections) && !empty($townConnections))
            @foreach($townConnections as $direction => $connection)
                @php
                    // Raw key from config may be an action label like 'move_north'
                    $rawKey = strtolower($direction);

                    // Normalize to compass direction for client logic
                    $normalizeMap = [
                        'move_north' => 'north',
                        'move_south' => 'south',
                        'move_east'  => 'east',
                        'move_west'  => 'west',
                    ];
                    $normalizedDirection = $normalizeMap[$rawKey] ?? $rawKey;

                    // Icons by normalized direction
                    $directionIcons = [
                        'north' => '⬆️',
                        'south' => '⬇️', 
                        'east' => '➡️',
                        'west' => '⬅️'
                    ];
                    $icon = $directionIcons[$normalizedDirection] ?? '🚪';

                    // UI label: prefer clean Japanese for move_*; otherwise use server-provided label/name
                    $directionLabelsJa = [
                        'north' => '北に移動',
                        'south' => '南に移動',
                        'east'  => '東に移動',
                        'west'  => '西に移動',
                    ];

                    // Detect valid action label from DB
                    $actionLabel = \App\Helpers\ActionLabel::isValidActionLabel($rawKey) ? $rawKey : null;
                    $uiLabel = null;
                    if ($actionLabel && str_starts_with($actionLabel, 'move_')) {
                        $uiLabel = $directionLabelsJa[$normalizedDirection] ?? 'この先へ移動';
                    } elseif ($actionLabel) {
                        $uiLabel = \App\Helpers\ActionLabel::getActionLabelText($actionLabel, $connection['name'] ?? null);
                    } else {
                        // Fallbacks: try compass label, then server label, then name-based generic
                        $uiLabel = $directionLabelsJa[$normalizedDirection]
                            ?? ($connection['direction_label'] ?? null)
                            ?? (\App\Helpers\ActionLabel::getActionLabelText(null, $connection['name'] ?? null));
                    }

                    // Hide internal-like names in destination
                    $destName = $connection['name'] ?? null;
                    $showDest = $destName && !preg_match('/^(move_|turn_)/i', $destName);
                @endphp
                <button 
                    class="connection-btn"
                    title="{{ $uiLabel }}"
                    data-direction="{{ $rawKey }}"
                >
                    <span class="direction-icon">{{ $icon }}</span>
                    <div class="direction-info">
                        <span class="direction-label">{{ $uiLabel }}</span>
                        @if($showDest)
                            <span class="destination-name">{{ $destName }}</span>
                        @endif
                    </div>
                </button>
            @endforeach
        @else
            <div class="no-connections">
                <p class="help-text">
                    <span class="help-icon">�</span>
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



{{-- === SEPARATOR === --}}
<hr class="content-separator">

{{-- === LEFT BAR CONTENT (Second) === --}}


{{-- Town Facilities --}}
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
                <a href="#" class="facility-link" onclick="gameManager.showNotification('この町には施設がありません', 'info')">
                    <span class="facility-icon">🏪</span>
                    <span class="facility-name">施設なし</span>
                </a>
            </div>
        @endif
    </div>
</div>

