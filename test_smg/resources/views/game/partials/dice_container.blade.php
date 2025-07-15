@if($player->current_location_type === 'road')
    <div class="dice-container" id="dice-container">
        <h3>サイコロを振って移動しよう！</h3>
        
        <div class="movement-info">
            <h4>移動情報</h4>
            <p>サイコロ数: {{ $movementInfo['total_dice_count'] }}個 (基本: {{ $movementInfo['base_dice_count'] }}個 + 装備効果: {{ $movementInfo['extra_dice'] }}個)</p>
            <p>サイコロボーナス: +{{ $movementInfo['dice_bonus'] }}</p>
            <p>移動倍率: {{ $movementInfo['movement_multiplier'] }}倍</p>
            <p>最小移動距離: {{ $movementInfo['min_possible_movement'] }}歩</p>
            <p>最大移動距離: {{ $movementInfo['max_possible_movement'] }}歩</p>
            @if(!empty($movementInfo['special_effects']))
                <p>特殊効果: {{ implode(', ', $movementInfo['special_effects']) }}</p>
            @endif
        </div>
        
        <button class="btn btn-primary" id="roll-dice" onclick="rollDice()">サイコロを振る</button>
        
        <div class="dice-display hidden" id="dice-result">
            <div id="all-dice"></div>
        </div>
        
        <div id="dice-total" class="hidden">
            <div class="step-indicator">
                <p>基本合計: <span id="base-total">0</span></p>
                <p>ボーナス: +<span id="bonus">0</span></p>
                <p>最終移動距離: <span id="final-movement">0</span>歩</p>
                <p style="font-size: 14px; color: #6b7280;">左右のボタンで移動方向を選択してください</p>
            </div>
        </div>
    </div>
@else
    <div class="dice-container" id="dice-container">
        <h3>{{ $currentLocation->name }}にいます</h3>
        <p>道路に移動すると、サイコロを振って移動できます。</p>
        <p>今後、この町の店舗リストが表示される予定です。</p>
    </div>
@endif