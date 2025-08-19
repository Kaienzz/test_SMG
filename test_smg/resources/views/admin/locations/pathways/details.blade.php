<div class="row">
    <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3">基本情報</h6>
        <table class="table table-sm">
            <tr>
                <th width="120">ID:</th>
                <td><code>{{ $pathwayId }}</code></td>
            </tr>
            <tr>
                <th>名前:</th>
                <td class="font-weight-bold">{{ $pathway['name'] }}</td>
            </tr>
            <tr>
                <th>カテゴリー:</th>
                <td>
                    @php
                        $categoryClass = match($pathway['category'] ?? 'road') {
                            'road' => 'primary',
                            'dungeon' => 'danger',
                            default => 'secondary'
                        };
                        $categoryText = match($pathway['category'] ?? 'road') {
                            'road' => '道路',
                            'dungeon' => 'ダンジョン',
                            default => '不明'
                        };
                    @endphp
                    <span class="badge bg-{{ $categoryClass }}">{{ $categoryText }}</span>
                </td>
            </tr>
            <tr>
                <th>説明:</th>
                <td>{{ $pathway['description'] ?? '説明なし' }}</td>
            </tr>
            <tr>
                <th>長さ:</th>
                <td>{{ $pathway['length'] ?? 100 }}</td>
            </tr>
            <tr>
                <th>難易度:</th>
                <td>
                    @php
                        $difficultyClass = match($pathway['difficulty'] ?? 'normal') {
                            'easy' => 'success',
                            'hard' => 'warning',
                            'extreme' => 'danger',
                            default => 'info'
                        };
                        $difficultyText = match($pathway['difficulty'] ?? 'normal') {
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
                <th>エンカウント率:</th>
                <td>{{ number_format(($pathway['encounter_rate'] ?? 0) * 100, 1) }}%</td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3">接続情報</h6>
        @if(isset($pathway['connections']) && count($pathway['connections']) > 0)
        <table class="table table-sm">
            @foreach($pathway['connections'] as $direction => $connection)
            <tr>
                <th width="80">{{ ucfirst($direction) }}:</th>
                <td>
                    <span class="badge bg-info">
                        {{ $connection['type'] ?? '' }}: {{ $connection['id'] ?? '' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </table>
        @else
        <span class="text-muted">接続情報なし</span>
        @endif
    </div>
</div>

@if($pathway['category'] === 'dungeon')
<div class="row mt-4">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-3">ダンジョン固有情報</h6>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="120">ダンジョンタイプ:</th>
                        <td>
                            @if(isset($pathway['dungeon_type']))
                                @php
                                    $typeClass = match($pathway['dungeon_type']) {
                                        'cave' => 'secondary',
                                        'ruins' => 'warning',
                                        'tower' => 'info',
                                        'underground' => 'dark',
                                        default => 'secondary'
                                    };
                                    $typeText = match($pathway['dungeon_type']) {
                                        'cave' => '洞窟',
                                        'ruins' => '遺跡',
                                        'tower' => '塔',
                                        'underground' => '地下',
                                        default => $pathway['dungeon_type']
                                    };
                                @endphp
                                <span class="badge bg-{{ $typeClass }}">{{ $typeText }}</span>
                            @else
                                <span class="text-muted">未設定</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>フロア数:</th>
                        <td>{{ $pathway['floors'] ?? 1 }}F</td>
                    </tr>
                    <tr>
                        <th>最小推奨レベル:</th>
                        <td>{{ $pathway['min_level'] ?? '制限なし' }}</td>
                    </tr>
                    <tr>
                        <th>最大推奨レベル:</th>
                        <td>{{ $pathway['max_level'] ?? '制限なし' }}</td>
                    </tr>
                    <tr>
                        <th>ボス:</th>
                        <td>
                            @if(isset($pathway['boss']) && $pathway['boss'])
                                <span class="text-danger font-weight-bold">{{ $pathway['boss'] }}</span>
                            @else
                                <span class="text-muted">なし</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                @if(isset($pathway['min_level']) || isset($pathway['max_level']))
                <div class="alert alert-info">
                    <h6><i class="fas fa-level-up-alt"></i> レベル制限</h6>
                    @if(isset($pathway['min_level']) && isset($pathway['max_level']))
                        <p class="mb-0">推奨レベル: Lv.{{ $pathway['min_level'] }} ～ Lv.{{ $pathway['max_level'] }}</p>
                    @elseif(isset($pathway['min_level']))
                        <p class="mb-0">最小レベル: Lv.{{ $pathway['min_level'] }}+</p>
                    @elseif(isset($pathway['max_level']))
                        <p class="mb-0">最大レベル: ～Lv.{{ $pathway['max_level'] }}</p>
                    @endif
                </div>
                @endif
                
                @if(isset($pathway['boss']) && $pathway['boss'])
                <div class="alert alert-danger">
                    <h6><i class="fas fa-dragon"></i> ボス情報</h6>
                    <p class="mb-0">ボス名: <strong>{{ $pathway['boss'] }}</strong></p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@if(isset($pathway['branches']) && count($pathway['branches']) > 0)
<div class="row mt-4">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-3">分岐情報</h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>位置</th>
                        <th>分岐先</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pathway['branches'] as $position => $branches)
                    <tr>
                        <td><strong>{{ $position }}</strong></td>
                        <td>
                            @foreach($branches as $direction => $connection)
                                <div class="mb-1">
                                    <strong>{{ $direction }}:</strong>
                                    <span class="badge bg-secondary">
                                        {{ $connection['type'] ?? '' }}: {{ $connection['id'] ?? '' }}
                                    </span>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if(isset($pathway['special_actions']) && count($pathway['special_actions']) > 0)
<div class="row mt-4">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-3">特殊アクション</h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>位置</th>
                        <th>アクション名</th>
                        <th>タイプ</th>
                        <th>条件</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pathway['special_actions'] as $position => $action)
                    <tr>
                        <td><strong>{{ $position }}</strong></td>
                        <td>{{ $action['name'] ?? 'アクション' }}</td>
                        <td>
                            @php
                                $actionTypeClass = match($action['type'] ?? 'unknown') {
                                    'boss_battle' => 'danger',
                                    'treasure_chest' => 'warning',
                                    'warp_portal' => 'info',
                                    'rest_spot' => 'success',
                                    'merchant_stand' => 'primary',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $actionTypeClass }}">{{ $action['type'] ?? 'unknown' }}</span>
                        </td>
                        <td>{{ $action['condition'] ?? 'なし' }}</td>
                        <td>
                            @if(isset($action['data']) && is_array($action['data']))
                            <small>
                                @foreach($action['data'] as $key => $value)
                                    <strong>{{ $key }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}<br>
                                @endforeach
                            </small>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if($pathway['category'] === 'dungeon' && isset($pathway['dungeon_roads']) && count($pathway['dungeon_roads']) > 0)
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
                    @foreach($pathway['dungeon_roads'] as $roadId => $road)
                    <tr>
                        <td><code>{{ $roadId }}</code></td>
                        <td>{{ $road['name'] ?? $roadId }}</td>
                        <td>{{ $road['floor'] ?? 1 }}F</td>
                        <td>{{ $road['length'] ?? 100 }}</td>
                        <td>
                            @php
                                $roadDifficultyClass = match($road['difficulty'] ?? 'normal') {
                                    'easy' => 'success',
                                    'hard' => 'warning',
                                    'extreme' => 'danger',
                                    default => 'info'
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

@if($pathway['category'] === 'dungeon' && isset($pathway['boss_rooms']) && count($pathway['boss_rooms']) > 0)
<div class="row mt-4">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-3">ボス部屋</h6>
        @foreach($pathway['boss_rooms'] as $roomId => $room)
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

<div class="row mt-4">
    <div class="col-12">
        <div class="text-center">
            <a href="{{ route('admin.locations.pathways.edit', $pathwayId) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> 編集
            </a>
            <a href="{{ route('admin.locations.pathways') }}" class="btn btn-secondary ms-2">
                <i class="fas fa-list"></i> 一覧に戻る
            </a>
        </div>
    </div>
</div>