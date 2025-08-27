@extends('admin.layouts.app')

@section('title', 'ロケーション管理')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">ロケーション管理</h1>
            <p class="mb-0 text-muted">ゲーム内の町、道路、ダンジョンの設定を管理</p>
        </div>
        <div class="btn-group" role="group">
            @if($canManageGameData ?? false)
                <a href="{{ route('admin.locations.export') }}" class="btn btn-outline-success">
                    <i class="fas fa-download"></i> エクスポート
                </a>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-upload"></i> インポート
                </button>
            @endif
        </div>
    </div>

    @if(isset($error))
        <div class="admin-alert admin-alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
        </div>
    @endif

    <!-- 統計カード -->
    @if(isset($stats))
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-primary); margin-bottom: 0.5rem;">
                    {{ $stats['roads_count'] }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">道路</div>
                <div style="margin-top: 0.5rem;">
                    <i class="fas fa-road fa-2x" style="color: var(--admin-primary);"></i>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-success); margin-bottom: 0.5rem;">
                    {{ $stats['towns_count'] }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">町</div>
                <div style="margin-top: 0.5rem;">
                    <i class="fas fa-city fa-2x" style="color: var(--admin-success);"></i>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-info); margin-bottom: 0.5rem;">
                    {{ $stats['dungeons_count'] }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">ダンジョン</div>
                <div style="margin-top: 0.5rem;">
                    <i class="fas fa-dungeon fa-2x" style="color: var(--admin-info);"></i>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--admin-warning); margin-bottom: 0.5rem;">
                    {{ $stats['total_connections'] }}
                </div>
                <div style="color: var(--admin-secondary); font-weight: 500;">総接続数</div>
                <div style="margin-top: 0.5rem;">
                    <i class="fas fa-project-diagram fa-2x" style="color: var(--admin-warning);"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- メイン管理パネル -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- 管理メニューカード -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">管理メニュー</h6>
            </div>
            <div class="admin-card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.roads.index') }}" class="btn btn-outline-primary btn-block py-3">
                                <i class="fas fa-road fa-2x d-block mb-2"></i>
                                <h6 class="mb-0">道路管理</h6>
                                <small class="text-muted">道路の追加・編集・削除</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.towns.index') }}" class="btn btn-outline-success btn-block py-3">
                                <i class="fas fa-city fa-2x d-block mb-2"></i>
                                <h6 class="mb-0">町管理</h6>
                                <small class="text-muted">町の追加・編集・削除</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.dungeons.index') }}" class="btn btn-outline-info btn-block py-3">
                                <i class="fas fa-dungeon fa-2x d-block mb-2"></i>
                                <h6 class="mb-0">ダンジョン管理</h6>
                                <small class="text-muted">ダンジョンの追加・編集・削除</small>
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('admin.route-connections.index') }}" class="btn btn-outline-warning btn-block py-3">
                                <i class="fas fa-project-diagram fa-2x d-block mb-2"></i>
                                <h6 class="mb-0">接続関係管理</h6>
                                <small class="text-muted">ロケーション間の接続を管理</small>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-block py-3" data-bs-toggle="modal" data-bs-target="#configModal">
                                <i class="fas fa-cog fa-2x d-block mb-2"></i>
                                <h6 class="mb-0">設定情報</h6>
                                <small class="text-muted">設定ファイルの詳細情報</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 最近のバックアップ -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">最近のバックアップ</h6>
                </div>
                <div class="card-body">
                    @if(isset($recent_backups) && count($recent_backups) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recent_backups as $backup)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="font-weight-bold">{{ $backup['filename'] }}</div>
                                    <small class="text-muted">{{ date('Y/m/d H:i', $backup['modified']) }}</small>
                                </div>
                                @if($canManageGameData ?? false)
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            onclick="restoreBackup('{{ $backup['filename'] }}')">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-3">バックアップファイルがありません</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- インポートモーダル -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.locations.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">設定インポート</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="config_file" class="form-label">設定ファイル (JSON)</label>
                        <input type="file" class="form-control" id="config_file" name="config_file" accept=".json" required>
                        <div class="form-text">最大2MBまでのJSONファイルをアップロードできます。</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>警告:</strong> インポートすると現在の設定が上書きされます。事前にバックアップを取ることをお勧めします。
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">インポート</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 設定情報モーダル -->
@if(isset($config_status))
<div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">設定ファイル情報</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>ファイル存在</strong></td>
                        <td>
                            @if($config_status['file_exists'])
                                <span class="badge bg-success">有効</span>
                            @else
                                <span class="badge bg-danger">無効</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>ファイルサイズ</strong></td>
                        <td>{{ number_format($config_status['file_size']) }} bytes</td>
                    </tr>
                    <tr>
                        <td><strong>最終更新</strong></td>
                        <td>{{ $config_status['last_modified'] ?? '不明' }}</td>
                    </tr>
                    <tr>
                        <td><strong>バージョン</strong></td>
                        <td>{{ $config_status['version'] }}</td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- バックアップ復元フォーム -->
<form id="restoreForm" action="{{ route('admin.locations.restore') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="backup_file" id="backup_file_input">
</form>

@endsection

@push('scripts')
<script>
function restoreBackup(filename) {
    if (confirm('バックアップ「' + filename + '」から復元しますか？現在の設定は上書きされます。')) {
        document.getElementById('backup_file_input').value = filename;
        document.getElementById('restoreForm').submit();
    }
}
</script>
@endpush