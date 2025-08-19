<div class="row">
    <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3">基本情報</h6>
        <table class="table table-sm">
            <tr>
                <th width="120">ダンジョンID:</th>
                <td><code>{{ $dungeonId }}</code></td>
            </tr>
            <tr>
                <th>名前:</th>
                <td class="font-weight-bold">{{ $dungeon['name'] }}</td>
            </tr>
            <tr>
                <th>説明:</th>
                <td>{{ $dungeon['description'] ?? '説明なし' }}</td>
            </tr>
            <tr>
                <th>タイプ:</th>
                <td>
                    @php
                        $typeText = match($dungeon['type'] ?? 'cave') {
                            'cave' => '洞窟',
                            'ruins' => '遺跡', 
                            'tower' => '塔',
                            'underground' => '地下',
                            default => '洞窟'
                        };
                    @endphp
                    <span class="badge bg-secondary">{{ $typeText }}</span>
                </td>
            </tr>
            <tr>
                <th>難易度:</th>
                <td>
                    @php
                        $difficultyClass = match($dungeon['difficulty'] ?? 'normal') {
                            'easy' => 'success',
                            'hard' => 'danger',
                            'extreme' => 'dark',
                            default => 'warning'
                        };
                        $difficultyText = match($dungeon['difficulty'] ?? 'normal') {
                            'easy' => '簡単',
                            'hard' => '困難',
                            'extreme' => '極難',
                            default => '普通'
                        };
                    @endphp
                    <span class="badge bg-{{ $difficultyClass }}">{{ $difficultyText }}</span>
                </td>
            </tr>
            <tr>
                <th>フロア数:</th>
                <td>{{ $dungeon['floors'] ?? 1 }}F</td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3">制限・条件</h6>
        <table class="table table-sm">
            <tr>
                <th width="120">最小レベル:</th>
                <td>{{ $dungeon['min_level'] ?? '制限なし' }}</td>
            </tr>
            <tr>
                <th>最大レベル:</th>
                <td>{{ $dungeon['max_level'] ?? '制限なし' }}</td>
            </tr>
            <tr>
                <th>ボス:</th>
                <td>
                    @if(isset($dungeon['boss']) && $dungeon['boss'])
                        <span class="text-danger font-weight-bold">{{ $dungeon['boss'] }}</span>
                    @else
                        <span class="text-muted">なし</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>作成日:</th>
                <td>{{ $dungeon['created_at'] ?? '不明' }}</td>
            </tr>
            <tr>
                <th>更新日:</th>
                <td>{{ $dungeon['updated_at'] ?? '不明' }}</td>
            </tr>
        </table>
    </div>
</div>

@if(isset($dungeon['entrance']) || isset($dungeon['connections']))
<div class="row mt-4">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-3">接続情報</h6>
        
        @if(isset($dungeon['entrance']))
        <div class="mb-3">
            <strong>入口:</strong>
            <span class="badge bg-info">
                {{ $dungeon['entrance']['type'] ?? '' }}: {{ $dungeon['entrance']['id'] ?? '' }}
            </span>
        </div>
        @endif
        
        @if(isset($dungeon['connections']) && count($dungeon['connections']) > 0)
        <div>
            <strong>接続:</strong>
            @foreach($dungeon['connections'] as $direction => $connection)
                <span class="badge bg-info me-1">
                    {{ $direction }}: {{ $connection['type'] ?? '' }}/{{ $connection['id'] ?? '' }}
                </span>
            @endforeach
        </div>
        @endif
        
        @if(!isset($dungeon['entrance']) && (!isset($dungeon['connections']) || count($dungeon['connections']) == 0))
        <span class="text-muted">接続情報なし</span>
        @endif
    </div>
</div>
@endif

@if(isset($dungeon['dungeon_roads']) && count($dungeon['dungeon_roads']) > 0)
<div class="row mt-4">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-3">ダンジョン内道路</h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>道路ID</th>
                        <th>名前</th>
                        <th>フロア</th>
                        <th>長さ</th>
                        <th>難易度</th>
                        <th>エンカウント率</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dungeon['dungeon_roads'] as $roadId => $road)
                    <tr>
                        <td><code>{{ $roadId }}</code></td>
                        <td>{{ $road['name'] ?? $roadId }}</td>
                        <td>{{ $road['floor'] ?? 1 }}F</td>
                        <td>{{ $road['length'] ?? 100 }}</td>
                        <td>
                            @php
                                $roadDifficultyClass = match($road['difficulty'] ?? 'normal') {
                                    'easy' => 'success',
                                    'hard' => 'danger',
                                    default => 'warning'
                                };
                            @endphp
                            <span class="badge bg-{{ $roadDifficultyClass }}">{{ $road['difficulty'] ?? 'normal' }}</span>
                        </td>
                        <td>{{ number_format(($road['encounter_rate'] ?? 0) * 100, 1) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if(isset($dungeon['boss_rooms']) && count($dungeon['boss_rooms']) > 0)
<div class="row mt-4">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-3">ボス部屋</h6>
        @foreach($dungeon['boss_rooms'] as $roomId => $room)
        <div class="card mb-2">
            <div class="card-body p-3">
                <h6 class="card-title mb-2">{{ $room['name'] ?? $roomId }}</h6>
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>ボス:</strong> {{ $room['boss'] ?? 'なし' }}</small><br>
                        <small><strong>最小レベル:</strong> {{ $room['min_level'] ?? 'なし' }}</small><br>
                        <small><strong>最大レベル:</strong> {{ $room['max_level'] ?? 'なし' }}</small>
                    </div>
                    <div class="col-md-6">
                        @if(isset($room['rewards']) && is_array($room['rewards']))
                        <small><strong>報酬:</strong></small><br>
                        @foreach($room['rewards'] as $reward)
                            <span class="badge bg-warning me-1">{{ $reward }}</span>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif