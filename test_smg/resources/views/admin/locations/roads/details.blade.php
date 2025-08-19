<div class="row">
    <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3">基本情報</h6>
        <table class="table table-sm">
            <tr>
                <th width="120">道路ID:</th>
                <td><code>{{ $roadId }}</code></td>
            </tr>
            <tr>
                <th>名前:</th>
                <td class="font-weight-bold">{{ $road['name'] }}</td>
            </tr>
            <tr>
                <th>説明:</th>
                <td>{{ $road['description'] ?? '説明なし' }}</td>
            </tr>
            <tr>
                <th>長さ:</th>
                <td>{{ $road['length'] ?? 100 }}</td>
            </tr>
            <tr>
                <th>難易度:</th>
                <td>
                    @php
                        $difficultyClass = match($road['difficulty'] ?? 'normal') {
                            'easy' => 'success',
                            'hard' => 'danger',
                            default => 'warning'
                        };
                        $difficultyText = match($road['difficulty'] ?? 'normal') {
                            'easy' => '簡単',
                            'hard' => '困難',
                            default => '普通'
                        };
                    @endphp
                    <span class="badge bg-{{ $difficultyClass }}">{{ $difficultyText }}</span>
                </td>
            </tr>
            <tr>
                <th>エンカウント率:</th>
                <td>{{ number_format(($road['encounter_rate'] ?? 0) * 100, 1) }}%</td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3">接続情報</h6>
        @if(isset($road['connections']) && count($road['connections']) > 0)
        <table class="table table-sm">
            @foreach($road['connections'] as $direction => $connection)
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

@if(isset($road['branches']) && count($road['branches']) > 0)
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
                    @foreach($road['branches'] as $position => $branches)
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

@if(isset($road['special_actions']) && count($road['special_actions']) > 0)
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
                    @foreach($road['special_actions'] as $position => $action)
                    <tr>
                        <td><strong>{{ $position }}</strong></td>
                        <td>{{ $action['name'] ?? 'アクション' }}</td>
                        <td>
                            <span class="badge bg-warning">{{ $action['type'] ?? 'unknown' }}</span>
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