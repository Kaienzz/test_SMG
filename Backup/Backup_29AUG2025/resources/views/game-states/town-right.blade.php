{{-- Town State - Right Area: Movement Options --}}

<div class="movement-section">
    <h3>移動先選択</h3>
    
    {{-- Multiple Connections --}}
    @php
        $connections = [
            ['direction' => 'north', 'name' => 'プリマ街道', 'icon' => '⬆️', 'description' => '北の町へ続く街道'],
            ['direction' => 'east', 'name' => '森の街道', 'icon' => '➡️', 'description' => '森を抜ける道'],
            ['direction' => 'south', 'name' => '商業街道', 'icon' => '⬇️', 'description' => '商業都市への道'],
            ['direction' => 'west', 'name' => '山岳街道', 'icon' => '⬅️', 'description' => '山間部への険しい道']
        ];
    @endphp

    <div class="connection-options">
        @foreach($connections as $connection)
            <button 
                class="connection-btn"
                onclick="moveToDirection('{{ $connection['direction'] }}')"
                title="{{ $connection['description'] }}"
                data-direction="{{ $connection['direction'] }}"
            >
                <span class="direction-icon">{{ $connection['icon'] }}</span>
                <div class="direction-info">
                    <span class="direction-label">{{ ucfirst($connection['direction']) }}</span>
                    <span class="destination-name">{{ $connection['name'] }}</span>
                </div>
            </button>
        @endforeach
    </div>

    <div class="movement-help">
        <p class="help-text">
            <span class="help-icon">💡</span>
            道を選択して冒険に出発しましょう！
        </p>
    </div>
</div>


