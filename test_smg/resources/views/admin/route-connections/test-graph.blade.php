@extends('admin.layouts.app')

@section('title', 'Graph API Test')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Graph API テスト</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.route-connections.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 接続管理に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">API Test</h5>
                </div>
                <div class="card-body">
                    <button id="test-api-btn" class="btn btn-primary">APIをテスト</button>
                    <div id="api-result" class="mt-3"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">認証情報</h5>
                </div>
                <div class="card-body">
                    <p><strong>ユーザー:</strong> {{ auth()->user()->name }}</p>
                    <p><strong>CSRF Token:</strong> <code>{{ csrf_token() }}</code></p>
                    <p><strong>API URL:</strong> <code>{{ route('admin.route-connections.graph-data') }}</code></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Raw Data Debug</h5>
                </div>
                <div class="card-body">
                    <div id="debug-info"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testApiBtn = document.getElementById('test-api-btn');
    const apiResult = document.getElementById('api-result');
    const debugInfo = document.getElementById('debug-info');
    
    // デバッグ情報を表示
    debugInfo.innerHTML = `
        <h6>Debug Information:</h6>
        <ul>
            <li>Current URL: ${window.location.href}</li>
            <li>CSRF Token found: ${document.querySelector('meta[name="csrf-token"]') ? 'Yes' : 'No'}</li>
            <li>User Agent: ${navigator.userAgent}</li>
        </ul>
    `;
    
    testApiBtn.addEventListener('click', function() {
        testApiBtn.disabled = true;
        testApiBtn.textContent = 'テスト中...';
        apiResult.innerHTML = '<div class="spinner-border" role="status"></div>';
        
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = '{{ route("admin.route-connections.graph-data") }}';
        
        console.log('Testing API:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Success data:', data);
            apiResult.innerHTML = `
                <div class="alert alert-success">
                    <h6>API テスト成功!</h6>
                    <p><strong>ノード数:</strong> ${data.stats?.nodes_count || 0}</p>
                    <p><strong>エッジ数:</strong> ${data.stats?.edges_count || 0}</p>
                    <details>
                        <summary>詳細データ</summary>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </details>
                </div>
            `;
        })
        .catch(error => {
            console.error('API Error:', error);
            apiResult.innerHTML = `
                <div class="alert alert-danger">
                    <h6>API エラー</h6>
                    <p>${error.message}</p>
                </div>
            `;
        })
        .finally(() => {
            testApiBtn.disabled = false;
            testApiBtn.textContent = 'APIをテスト';
        });
    });
});
</script>
@endsection