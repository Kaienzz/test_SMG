<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ブラウザゲーム - 町と道の冒険</title>
    <link rel="stylesheet" href="/css/game.css">
</head>
<body>
    <div class="game-container">
        @include('game.partials.navigation')
        
        <h1>町と道の冒険ゲーム</h1>
        
        @include('game.partials.location_info')
        
        @include('game.partials.next_location_button')
        
        @include('game.partials.dice_container')
        
        @include('game.partials.movement_controls')
        
        @include('game.partials.game_controls')
    </div>

    <script src="/js/game.js"></script>
    <script>
        // ゲームデータの初期化（DTO の toJson() メソッドを使用）
        const gameData = {
            character: @json($character),
            currentLocation: @json($currentLocation),
            nextLocation: @json($nextLocation)
        };

        // ゲーム初期化
        initializeGame(gameData);
    </script>
</body>
</html>