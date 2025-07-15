<div class="location-info">
    <h2 id="current-location">{{ $currentLocation->name }}</h2>
    <p id="location-type">{{ $player->current_location_type === 'town' ? '町にいます' : '道を歩いています' }}</p>
    
    @if($player->current_location_type === 'road')
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: {{ $player->position }}%"></div>
            <div class="progress-text" id="progress-text">{{ $player->position }}/100</div>
        </div>
    @endif
</div>