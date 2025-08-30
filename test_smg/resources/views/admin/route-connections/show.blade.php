@extends('admin.layouts.app')

@section('title', 'ロケーション接続詳細')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">ロケーション接続詳細</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.route-connections.edit', $connection->id) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> 編集
            </a>
            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash"></i> 削除
            </button>
            <a href="{{ route('admin.route-connections.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">接続情報</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">出発ロケーション</h6>
                                    <h4>{{ $connection->sourceLocation?->name ?? 'Unknown' }}</h4>
                                    <p class="mb-0">
                                        <span class="badge bg-info">{{ $connection->sourceLocation?->category ?? 'Unknown' }}</span>
                                        @if($connection->source_position !== null)
                                            <br><span class="badge bg-secondary mt-1">位置: {{ $connection->source_position }}</span>
                                        @endif
                                    </p>
                                    @if($connection->sourceLocation)
                                        <a href="{{ route('admin.locations.show', $connection->sourceLocation->id) }}" 
                                           class="btn btn-sm btn-outline-primary mt-2">詳細表示</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="text-center">
                                @if($connection->edge_type)
                                    @switch($connection->edge_type)
                                        @case('portal')
                                            <i class="fas fa-magic fa-2x text-primary"></i>
                                            @break
                                        @case('exit')
                                            <i class="fas fa-sign-out-alt fa-2x text-warning"></i>
                                            @break
                                        @case('enter')
                                            <i class="fas fa-sign-in-alt fa-2x text-success"></i>
                                            @break
                                        @case('branch')
                                            <i class="fas fa-code-branch fa-2x text-info"></i>
                                            @break
                                        @default
                                            <i class="fas fa-arrow-right fa-2x text-primary"></i>
                                    @endswitch
                                @else
                                    <i class="fas fa-arrow-right fa-2x text-primary"></i>
                                @endif
                                
                                @if($connection->edge_type)
                                    <div class="mt-1">
                                        <span class="badge bg-secondary">{{ $connection->edge_type }}</span>
                                    </div>
                                @endif
                                
                                @if($connection->action_label)
                                    <div class="mt-1">
                                        <small class="text-primary">{{ \App\Helpers\ActionLabel::getActionLabelText($connection->action_label) }}</small>
                                    </div>
                                @endif
                                
                                @if($connection->keyboard_shortcut)
                                    <div class="mt-1">
                                        <span class="badge bg-dark">{{ \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut) }}</span>
                                    </div>
                                @endif
                                
                                @if($connection->direction)
                                    <div class="mt-1">
                                        <small class="text-muted">{{ $connection->direction }}</small>
                                    </div>
                                @endif
                                
                                <div class="mt-2">
                                    @if($connection->is_enabled ?? true)
                                        <span class="badge bg-success">有効</span>
                                    @else
                                        <span class="badge bg-warning">無効</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">到達ロケーション</h6>
                                    <h4>{{ $connection->targetLocation?->name ?? 'Unknown' }}</h4>
                                    <p class="mb-0">
                                        <span class="badge bg-info">{{ $connection->targetLocation?->category ?? 'Unknown' }}</span>
                                        @if($connection->target_position !== null)
                                            <br><span class="badge bg-secondary mt-1">位置: {{ $connection->target_position }}</span>
                                        @endif
                                    </p>
                                    @if($connection->targetLocation)
                                        <a href="{{ route('admin.locations.show', $connection->targetLocation->id) }}" 
                                           class="btn btn-sm btn-outline-primary mt-2">詳細表示</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <dl class="row">
                        <dt class="col-sm-3">接続ID</dt>
                        <dd class="col-sm-9">{{ $connection->id }}</dd>
                        
                        <dt class="col-sm-3">出発ロケーションID</dt>
                        <dd class="col-sm-9"><code>{{ $connection->source_location_id }}</code></dd>
                        
                        <dt class="col-sm-3">到達ロケーションID</dt>
                        <dd class="col-sm-9"><code>{{ $connection->target_location_id }}</code></dd>
                        
                        <dt class="col-sm-3">出発位置</dt>
                        <dd class="col-sm-9">
                            @if($connection->source_position !== null)
                                <span class="badge bg-info">{{ $connection->source_position }}</span>
                            @else
                                <span class="text-muted">未設定</span>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-3">到着位置</dt>
                        <dd class="col-sm-9">
                            @if($connection->target_position !== null)
                                <span class="badge bg-info">{{ $connection->target_position }}</span>
                            @else
                                <span class="text-muted">未設定</span>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-3">エッジタイプ</dt>
                        <dd class="col-sm-9">
                            @if($connection->edge_type)
                                <span class="badge bg-secondary">{{ $connection->edge_type }}</span>
                            @else
                                <span class="text-muted">未設定</span>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-3">アクションラベル</dt>
                        <dd class="col-sm-9">
                            @if($connection->action_label)
                                <code>{{ $connection->action_label }}</code>
                                <br><small class="text-muted">{{ \App\Helpers\ActionLabel::getActionLabelText($connection->action_label) }}</small>
                            @else
                                <span class="text-muted">自動設定</span>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-3">キーボードショートカット</dt>
                        <dd class="col-sm-9">
                            @if($connection->keyboard_shortcut)
                                <span class="badge bg-dark">{{ \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut) }}</span>
                                <small class="text-muted ms-2">{{ $connection->keyboard_shortcut }}</small>
                            @else
                                <span class="text-muted">なし</span>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-3">状態</dt>
                        <dd class="col-sm-9">
                            @if($connection->is_enabled ?? true)
                                <span class="badge bg-success">有効</span>
                            @else
                                <span class="badge bg-warning">無効</span>
                            @endif
                        </dd>
                        
                        <hr class="my-3">
                        
                        <dt class="col-sm-3">方向 (レガシー)</dt>
                        <dd class="col-sm-9">{{ $connection->direction ?? '未設定' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">システム情報</h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">作成日時</dt>
                        <dd class="col-sm-7">{{ $connection->created_at->format('Y-m-d H:i:s') }}</dd>
                        <dt class="col-sm-5">更新日時</dt>
                        <dd class="col-sm-7">{{ $connection->updated_at->format('Y-m-d H:i:s') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">操作</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.route-connections.edit', $connection->id) }}" class="btn btn-primary">編集</a>
                        
                        @if($connection->sourceLocation)
                            <a href="{{ route('admin.locations.show', $connection->sourceLocation->id) }}" class="btn btn-outline-info">
                                出発地詳細
                            </a>
                        @endif
                        
                        @if($connection->targetLocation)
                            <a href="{{ route('admin.locations.show', $connection->targetLocation->id) }}" class="btn btn-outline-info">
                                到達地詳細
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">エッジタイプについて</h6>
                </div>
                <div class="card-body">
                    <small>
                        @if($connection->edge_type)
                            @switch($connection->edge_type)
                                @case('normal')
                                    <strong>Normal:</strong> 通常の移動接続。標準的な移動方法です。
                                    @break
                                @case('branch')
                                    <strong>Branch:</strong> 分岐点。複数の選択肢がある接続です。
                                    @break
                                @case('portal')
                                    <strong>Portal:</strong> ポータル接続。瞬間移動や特殊な移動手段です。
                                    @break
                                @case('exit')
                                    <strong>Exit:</strong> 出口接続。建物やエリアからの退出に使用されます。
                                    @break
                                @case('enter')
                                    <strong>Enter:</strong> 入口接続。建物やエリアへの入場に使用されます。
                                    @break
                                @default
                                    <strong>{{ ucfirst($connection->edge_type) }}:</strong> カスタムエッジタイプです。
                            @endswitch
                        @else
                            エッジタイプが設定されていません。通常の接続として扱われます。
                        @endif
                    </small>
                    
                    @if($connection->action_label)
                        <hr class="my-2">
                        <h6 class="h6 mb-2">アクション表示</h6>
                        <div class="alert alert-info py-2">
                            プレイヤーには「<strong>{{ \App\Helpers\ActionLabel::getActionLabelText($connection->action_label) }}</strong>」と表示されます
                        </div>
                    @endif
                    
                    @if($connection->keyboard_shortcut)
                        <h6 class="h6 mb-2">キーボード操作</h6>
                        <div class="alert alert-secondary py-2">
                            <span class="badge bg-dark me-2">{{ \App\Helpers\ActionLabel::getKeyboardShortcutDisplay($connection->keyboard_shortcut) }}</span>
                            キーで移動可能
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">位置設定について</h6>
                </div>
                <div class="card-body">
                    <small>
                        <dl class="mb-0">
                            <dt>町の場合</dt>
                            <dd>位置設定は不要です</dd>
                            
                            <dt>道路・ダンジョンの場合</dt>
                            <dd>0-100の範囲で位置を設定します</dd>
                            
                            <dt>位置比較ルール</dt>
                            <dd>
                                <ul class="mb-0">
                                    <li><strong>0, 100:</strong> ≤, ≥ 比較（端点）</li>
                                    <li><strong>中間値:</strong> 完全一致比較</li>
                                </ul>
                            </dd>
                        </dl>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>この接続を削除しますか？</p>
                <div class="alert alert-warning">
                    <strong>{{ $connection->sourceLocation?->name ?? 'Unknown' }}</strong> → 
                    <strong>{{ $connection->targetLocation?->name ?? 'Unknown' }}</strong>
                </div>
                <p class="text-danger"><strong>この操作は取り消せません。</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form action="{{ route('admin.route-connections.destroy', $connection->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除実行</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection