<div class="location-info">
    <h2 id="current-location">{{ $currentLocation->name }}</h2>
    <p id="location-type">{{ $player->location_type === 'town' ? '町にいます' : '道を歩いています' }}</p>
    
    @if($player->location_type === 'town')
        @php
            $townFacilities = \App\Models\TownFacility::getFacilitiesByLocation($player->location_id, 'town');
        @endphp
        
        {{-- 施設メニュー（水色ボックス） - 町別の施設を表示 --}}
        <div class="facility-menu" style="background-color: #e0f7fa; border: 2px solid #00acc1; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
            <h3>町の施設</h3>
            @if($townFacilities->count() > 0)
                @foreach($townFacilities as $facility)
                    @php
                        $facilityType = \App\Enums\FacilityType::from($facility->facility_type);
                        $routeName = match($facilityType) {
                            \App\Enums\FacilityType::ITEM_SHOP => 'facilities.item.index',
                            \App\Enums\FacilityType::BLACKSMITH => 'facilities.blacksmith.index',
                            \App\Enums\FacilityType::TAVERN => 'facilities.tavern.index',
                            default => null
                        };
                    @endphp
                    
                    @if($routeName && \Route::has($routeName))
                        <a href="{{ route($routeName) }}" class="btn btn-primary" title="{{ $facility->description ?? $facilityType->getDescription() }}" style="margin: 5px;">
                            <span class="facility-icon">{{ $facilityType->getIcon() }}</span>
                            {{ $facility->name }}
                        </a>
                    @endif
                @endforeach
            @else
                {{-- フォールバック: 施設データがない場合の基本表示 --}}
                @if(\Route::has('facilities.item.index'))
                    <a href="{{ route('facilities.item.index') }}" class="btn btn-primary" title="道具屋" style="margin: 5px;">
                        <span class="facility-icon">🏪</span>
                        道具屋
                    </a>
                @endif
                @if(\Route::has('facilities.blacksmith.index'))
                    <a href="{{ route('facilities.blacksmith.index') }}" class="btn btn-primary" title="鍛冶屋" style="margin: 5px;">
                        <span class="facility-icon">⚒️</span>
                        鍛冶屋
                    </a>
                @endif
            @endif
        </div>
        
        {{-- 移動メニュー（黄緑色ボックス）は next_location_button.blade.php で処理 --}}
    @endif
    
    @if($player->location_type === 'road')
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: {{ $player->game_position ?? 0 }}%"></div>
            <div class="progress-text" id="progress-text">{{ $player->game_position ?? 0 }}/100</div>
        </div>
        
        @php
            $character = null;
            $gatheringSkill = null;
            
            // 安全にgetCharacter()メソッドを呼び出し
            if (is_object($player) && method_exists($player, 'getCharacter')) {
                $character = $player->getCharacter();
            } elseif (is_object($player) && isset($player->getCharacter) && is_callable($player->getCharacter)) {
                $character = call_user_func($player->getCharacter);
            }
            
            // プレイヤーが取得できた場合のみスキルチェック
            if ($player && is_object($player) && method_exists($player, 'getSkill')) {
                $gatheringSkill = $player->getSkill('採集');
            }
        @endphp
        
        @if($gatheringSkill)
            <div class="road-actions">
                <h3>道での行動</h3>
                <div class="gathering-section">
                    <button id="gathering-btn" class="btn btn-success" onclick="performGathering()">
                        <span class="icon">🌿</span>
                        採集する
                    </button>
                    <button id="gathering-info-btn" class="btn btn-info" onclick="showGatheringInfo()">
                        <span class="icon">📊</span>
                        採集情報
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>