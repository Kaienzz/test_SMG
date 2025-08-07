@if($player->location_type === 'road')
<div class="movement-controls hidden" id="movement-controls">
    @if($player->game_position > 0)
        <button class="btn btn-warning" id="move-left" onclick="move('left')">←左に移動</button>
    @endif
    @if($player->game_position < 100)
        <button class="btn btn-warning" id="move-right" onclick="move('right')">→右に移動</button>
    @endif
</div>
@endif