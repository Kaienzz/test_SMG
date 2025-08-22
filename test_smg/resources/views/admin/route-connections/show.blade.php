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
                                <i class="fas fa-arrow-right fa-2x text-primary"></i>
                                <div class="mt-2">
                                    <span class="badge bg-secondary">{{ $connection->connection_type }}</span>
                                </div>
                                @if($connection->direction)
                                    <div class="mt-1">
                                        <small class="text-muted">{{ $connection->direction }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">到達ロケーション</h6>
                                    <h4>{{ $connection->targetLocation?->name ?? 'Unknown' }}</h4>
                                    <p class="mb-0">
                                        <span class="badge bg-info">{{ $connection->targetLocation?->category ?? 'Unknown' }}</span>
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
                        
                        <dt class="col-sm-3">接続タイプ</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-primary">{{ $connection->connection_type }}</span>
                        </dd>
                        
                        <dt class="col-sm-3">位置</dt>
                        <dd class="col-sm-9">{{ $connection->position ?? 'N/A' }}</dd>
                        
                        <dt class="col-sm-3">方向</dt>
                        <dd class="col-sm-9">{{ $connection->direction ?? 'N/A' }}</dd>
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
                    <h6 class="mb-0">接続タイプについて</h6>
                </div>
                <div class="card-body">
                    <small>
                        @switch($connection->connection_type)
                            @case('start')
                                <strong>Start:</strong> 開始地点への接続。このロケーションから別のロケーションへの一方向接続です。
                                @break
                            @case('end')
                                <strong>End:</strong> 終了地点への接続。別のロケーションからこのロケーションへの一方向接続です。
                                @break
                            @case('bidirectional')
                                <strong>Bidirectional:</strong> 双方向接続。両方向に移動可能な接続です。
                                @break
                            @default
                                接続タイプの詳細情報がありません。
                        @endswitch
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