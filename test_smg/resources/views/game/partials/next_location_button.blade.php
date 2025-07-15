@if($nextLocation)
    <div class="next-location" id="next-location-info" style="display: none;">
        <p>次の場所: <strong>{{ $nextLocation['name'] }}</strong></p>
        <button class="btn btn-success" id="move-to-next" onclick="moveToNext()">
            {{ $nextLocation['name'] }}に移動する
        </button>
    </div>
@endif