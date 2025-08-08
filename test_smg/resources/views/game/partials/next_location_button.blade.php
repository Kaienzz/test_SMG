@if($nextLocation)
    @php
        $showButton = false;
        
        // 現在いる場所と次の場所が同じ場合は表示しない
        $isSameLocation = false;
        if ($player->location_type === 'town' && isset($nextLocation->id)) {
            $isSameLocation = ($player->location_id === $nextLocation->id);
        }
        
        if (!$isSameLocation) {
            if ($player->location_type === 'town') {
                // 町にいるときは（同じ町でなければ）常に表示
                $showButton = true;
            } elseif ($player->location_type === 'road') {
                // 道路の境界（0、50、100）にいるときのみ表示
                $showButton = ($player->game_position === 0 || $player->game_position === 50 || $player->game_position === 100);
            }
        }
        
        // 町用と道路用でIDを分ける
        $buttonId = $player->location_type === 'town' ? 'move-to-next-town' : 'move-to-next-road';
        $buttonFunction = $player->location_type === 'town' ? 'moveToNextFromTown()' : 'moveToNextFromRoad()';
    @endphp
    
    <div class="next-location" id="next-location-info" style="display: {{ $showButton ? 'block' : 'none' }};">
        <p>次の場所: <strong>{{ $nextLocation->name }}</strong></p>
        <button class="btn btn-success" id="{{ $buttonId }}" onclick="{{ $buttonFunction }}">
            {{ $nextLocation->name }}に移動する
        </button>
    </div>
@endif