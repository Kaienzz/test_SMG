<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ブラウザゲームデモ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .progress-bar { width: 100%; height: 30px; background: #eee; border-radius: 5px; margin: 20px 0; }
        .progress-inner { height: 100%; background: #4caf50; border-radius: 5px; transition: width 0.3s; }
        .controls { margin: 20px 0; }
        button { font-size: 1.2em; margin: 0 10px; }
    </style>
</head>
<body>
    <h1>町A⇔町B ゲームデモ</h1>
    <div id="game-info">
        <div>現在地: <span id="location"></span></div>
        <div>道路: <span id="road-num"></span></div>
        <div class="progress-bar">
            <div class="progress-inner" id="progress-bar-inner" style="width:0%"></div>
        </div>
        <div>進捗: <span id="progress">0</span>/100</div>
    </div>
    <div class="controls">
        <button id="left-btn">左へ</button>
        <button id="right-btn">右へ</button>
    </div>
    <div class="controls">
        <button id="next-btn" style="display:none;">次へ</button>
    </div>
    <div id="dice-result"></div>
    <script>
    let state = {};
    function updateUI() {
        document.getElementById('road-num').textContent = state.current_road;
        document.getElementById('progress').textContent = state.progress;
        document.getElementById('progress-bar-inner').style.width = state.progress + '%';
        document.getElementById('location').textContent = (state.position === 'town') ? (state.current_road === 0 ? '町A' : (state.current_road === 4 ? '町B' : '町')) : '道路';
        // 端に到達時のみ「次へ」ボタン表示
        if (state.position === 'road' && (state.progress === 0 || state.progress === 100)) {
            document.getElementById('next-btn').style.display = '';
        } else if (state.position === 'town') {
            document.getElementById('next-btn').style.display = '';
        } else {
            document.getElementById('next-btn').style.display = 'none';
        }
    }
    async function fetchState() {
        const res = await fetch('/game/state');
        state = await res.json();
        updateUI();
    }
    document.getElementById('left-btn').onclick = async () => {
        const res = await fetch('/game/move', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify({direction:'left'}) });
        const data = await res.json();
        state = data.state;
        document.getElementById('dice-result').textContent = `サイコロ: ${data.dice[0]} + ${data.dice[1]} = ${data.dice[0]+data.dice[1]}`;
        updateUI();
    };
    document.getElementById('right-btn').onclick = async () => {
        const res = await fetch('/game/move', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify({direction:'right'}) });
        const data = await res.json();
        state = data.state;
        document.getElementById('dice-result').textContent = `サイコロ: ${data.dice[0]} + ${data.dice[1]} = ${data.dice[0]+data.dice[1]}`;
        updateUI();
    };
    document.getElementById('next-btn').onclick = async () => {
        const res = await fetch('/game/next', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'} });
        state = await res.json();
        document.getElementById('dice-result').textContent = '';
        updateUI();
    };
    fetchState();
    </script>
</body>
</html>