<h1>ブラウザゲームデモ</h1>
<p>町A &lt;=&gt; 道路1 &lt;=&gt; 道路2 &lt;=&gt; 道路3 &lt;=&gt; 町B</p>
<hr>

@php
    $current = $state['current'];
    $progress = $state['progress'];
    $dice = $state['dice'];
    $isRoad = in_array($current, ['road1', 'road2', 'road3']);
@endphp

<p><strong>現在地：</strong>
    @if($current === 'A') 町A
    @elseif($current === 'B') 町B
    @elseif($current === 'road1') 道路1
    @elseif($current === 'road2') 道路2
    @elseif($current === 'road3') 道路3
    @endif
</p>

@if($isRoad)
    <div style="width:300px; background:#eee; border:1px solid #ccc; margin-bottom:10px;">
        <div style="width:{{ $progress }}%; background:#4caf50; color:white; text-align:center;">{{ $progress }}%</div>
    </div>
@endif

{{-- サイコロ --}}
@if($isRoad && is_null($dice[0]) && is_null($dice[1]))
    <form method="POST" action="/game/roll">
        @csrf
        <button type="submit">サイコロを振る</button>
    </form>
@endif

@if($isRoad && !is_null($dice[0]) && !is_null($dice[1]))
    <p>サイコロの目：{{ $dice[0] }} と {{ $dice[1] }}（合計: {{ $dice[0]+$dice[1] }}）</p>
    <form method="POST" action="/game/move" style="display:inline;">
        @csrf
        <input type="hidden" name="direction" value="left">
        <button type="submit">左へ進む</button>
    </form>
    <form method="POST" action="/game/move" style="display:inline;">
        @csrf
        <input type="hidden" name="direction" value="right">
        <button type="submit">右へ進む</button>
    </form>
@endif

{{-- 次へ進むボタン --}}
@if($isRoad && ($progress === 0 || $progress === 100))
    <form method="POST" action="/game/next" style="margin-top:10px;">
        @csrf
        <button type="submit">次へ進む</button>
    </form>
@endif

{{-- 町Bに到達したらゴール表示 --}}
@if($current === 'B')
    <h2>ゴール！町Bに到着しました！</h2>
@endif

{{-- リセット --}}
<form method="POST" action="/game/next" style="margin-top:20px;">
    @csrf
    <input type="hidden" name="reset" value="1">
    <button type="submit">リセット</button>
</form>