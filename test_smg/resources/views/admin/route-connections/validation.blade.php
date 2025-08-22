@extends('admin.layouts.app')

@section('title', 'ロケーション接続検証')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">ロケーション接続検証</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.route-connections.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">検証結果</h5>
                    <div>
                        <a href="{{ route('admin.route-connections.validate') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-sync"></i> 再検証
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($invalidConnections) === 0)
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle"></i> 検証完了</h6>
                            <p class="mb-0">すべてのロケーション接続は正常です。問題は見つかりませんでした。</p>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> 問題が見つかりました</h6>
                            <p class="mb-0">{{ count($invalidConnections) }}件の問題のある接続が見つかりました。</p>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>接続ID</th>
                                        <th>出発ロケーションID</th>
                                        <th>到達ロケーションID</th>
                                        <th>問題の内容</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invalidConnections as $connection)
                                    <tr>
                                        <td>{{ $connection['id'] }}</td>
                                        <td>
                                            <code>{{ $connection['source_id'] }}</code>
                                        </td>
                                        <td>
                                            <code>{{ $connection['target_id'] }}</code>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">{{ $connection['issue'] }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.route-connections.show', $connection['id']) }}" 
                                                   class="btn btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.route-connections.edit', $connection['id']) }}" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal{{ $connection['id'] }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(count($invalidConnections) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">修復のヒント</h6>
                    </div>
                    <div class="card-body">
                        <h6>よくある問題と解決方法：</h6>
                        <ul>
                            <li><strong>Missing location reference:</strong> 接続先のロケーションが削除されているか、IDが変更されています。接続を削除するか、正しいロケーションIDに更新してください。</li>
                            <li><strong>Circular reference:</strong> ロケーション同士が循環参照しています。接続の方向を確認してください。</li>
                            <li><strong>Duplicate connection:</strong> 同じロケーション間に重複した接続があります。不要な接続を削除してください。</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if(count($invalidConnections) > 0)
    @foreach($invalidConnections as $connection)
    <!-- 削除確認モーダル -->
    <div class="modal fade" id="deleteModal{{ $connection['id'] }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">問題のある接続を削除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>この問題のある接続を削除しますか？</p>
                    <div class="alert alert-warning">
                        <strong>接続ID:</strong> {{ $connection['id'] }}<br>
                        <strong>出発:</strong> {{ $connection['source_id'] }}<br>
                        <strong>到達:</strong> {{ $connection['target_id'] }}<br>
                        <strong>問題:</strong> {{ $connection['issue'] }}
                    </div>
                    <p class="text-danger"><strong>この操作は取り消せません。</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <form action="{{ route('admin.route-connections.destroy', $connection['id']) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">削除実行</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif
@endsection