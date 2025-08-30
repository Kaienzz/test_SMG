{{-- Updated Connection-based Movement --}}
@if(isset($availableConnections) && !empty($availableConnections))
    <div class="next-location-connections" id="next-location-connections">
        <h4>利用可能な移動先</h4>
        @foreach($availableConnections as $connection)
            @php
                $actionText = \App\Helpers\ActionLabel::getActionLabelText(
                    $connection->action_label,
                    $connection->targetLocation->name ?? null
                );
                $keyboardDisplay = \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut);
            @endphp
            <button 
                class="btn btn-success next-connection-btn" 
                onclick="moveToConnection('{{ $connection->id }}')"
                data-connection-id="{{ $connection->id }}"
                data-keyboard="{{ $connection->keyboard_shortcut }}"
                title="{{ $actionText }}{{ $keyboardDisplay ? ' (' . $keyboardDisplay . ')' : '' }}"
            >
                <span class="btn-icon">
                    @switch($connection->edge_type)
                        @case('portal')
                            🌀
                            @break
                        @case('exit')
                            🚪
                            @break
                        @case('enter')
                            ↩️
                            @break
                        @case('branch')
                            🔀
                            @break
                        @default
                            {{ $keyboardDisplay ?? '🚶' }}
                    @endswitch
                </span>
                <span class="btn-text">{{ $actionText }}</span>
                @if($keyboardDisplay)
                    <span class="keyboard-hint">{{ $keyboardDisplay }}</span>
                @endif
            </button>
        @endforeach
    </div>
@endif

{{-- Legacy fallback for compatibility --}}
@if(isset($nextLocation) && (!isset($availableConnections) || empty($availableConnections)))
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
    
    <div class="next-location legacy-next-location" id="legacy-next-location-info" style="display: {{ $showButton ? 'block' : 'none' }};">
        <p>次の場所: <strong>{{ $nextLocation->name }}</strong></p>
        <button class="btn btn-success" id="{{ $buttonId }}" onclick="{{ $buttonFunction }}">
            {{ $nextLocation->name }}に移動する
        </button>
    </div>
@endif