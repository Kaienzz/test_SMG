@if($player->current_location_type === 'road')
<div class="movement-controls hidden" id="movement-controls">
    <button class="btn btn-warning" id="move-left" onclick="move('left')">←左に移動</button>
    <button class="btn btn-warning" id="move-right" onclick="move('right')">→右に移動</button>
</div>
@endif