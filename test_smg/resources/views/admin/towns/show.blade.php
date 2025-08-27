@extends('admin.layouts.app')

@section('title', '町詳細: ' . $town->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">町詳細: {{ $town->name }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.towns.edit', $town->id) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> 編集
            </a>
            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash"></i> 削除
            </button>
            <a href="{{ route('admin.towns.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">基本情報</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">町ID</dt>
                        <dd class="col-sm-9"><code>{{ $town->id }}</code></dd>
                        
                        <dt class="col-sm-3">町名</dt>
                        <dd class="col-sm-9">{{ $town->name }}</dd>
                        
                        <dt class="col-sm-3">説明</dt>
                        <dd class="col-sm-9">{{ $town->description ?: '説明なし' }}</dd>
                        
                        <dt class="col-sm-3">カテゴリー</dt>
                        <dd class="col-sm-9"><span class="badge bg-info">{{ $town->category }}</span></dd>
                        
                        <dt class="col-sm-3">ステータス</dt>
                        <dd class="col-sm-9">
                            @if($town->is_active)
                                <span class="badge bg-success">有効</span>
                            @else
                                <span class="badge bg-secondary">無効</span>
                            @endif
                        </dd>
                        
                        
                    </dl>
                </div>
            </div>

            @php
                $outgoing = $town->sourceConnections()->with('targetLocation')->get();
                $incoming = $town->targetConnections()->with('sourceLocation')->get();
            @endphp
            @if($outgoing->count() > 0 || $incoming->count() > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">接続情報</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>種類</th>
                                    <th>接続先</th>
                                    <th>方向</th>
                                    <th>タイプ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outgoing as $connection)
                                <tr>
                                    <td><span class="badge bg-info">この町から</span></td>
                                    <td>{{ $connection->targetLocation->name ?? 'Unknown' }}</td>
                                    <td>{{ $connection->direction ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $connection->connection_type }}</span></td>
                                </tr>
                                @endforeach
                                @foreach($incoming as $connection)
                                <tr>
                                    <td><span class="badge bg-warning text-dark">この町へ</span></td>
                                    <td>{{ $connection->sourceLocation->name ?? 'Unknown' }}</td>
                                    <td>{{ $connection->direction ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $connection->connection_type }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">システム情報</h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">作成日時</dt>
                        <dd class="col-sm-7">{{ $town->created_at->format('Y-m-d H:i:s') }}</dd>
                        <dt class="col-sm-5">更新日時</dt>
                        <dd class="col-sm-7">{{ $town->updated_at->format('Y-m-d H:i:s') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">操作</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.towns.edit', $town->id) }}" class="btn btn-primary">編集</a>
                        <a href="{{ route('admin.route-connections.create', ['source_location_id' => $town->id]) }}" class="btn btn-outline-primary">
                            接続追加
                        </a>
                        <a href="{{ route('admin.monster-spawns.show', $town->id) }}" class="btn btn-outline-info">
                            スポーン設定
                        </a>
                    </div>
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
                <p>町「{{ $town->name }}」を削除しますか？</p>
                <p class="text-danger"><strong>この操作は取り消せません。</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form action="{{ route('admin.towns.destroy', $town->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除実行</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection