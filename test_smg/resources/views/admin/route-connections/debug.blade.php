@extends('admin.layouts.app')

@section('title', 'Debug Page')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Debug - タブ切り替えテスト</h1>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>テスト結果</h5>
                </div>
                <div class="card-body">
                    <div id="debug-output"></div>
                    
                    <!-- シンプルなボタンテスト -->
                    <div class="mt-3">
                        <h6>シンプルボタンテスト:</h6>
                        <button type="button" class="btn btn-primary" onclick="testFunction1()">テスト1</button>
                        <button type="button" class="btn btn-secondary" onclick="window.testFunction2()">テスト2</button>
                        <button type="button" class="btn btn-info" id="test-btn-3">テスト3（イベントリスナー）</button>
                    </div>
                    
                    <!-- タブテスト -->
                    <div class="mt-4">
                        <h6>タブテスト:</h6>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="list-tab" onclick="showTab('list')">
                                リスト表示
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="graph-tab" onclick="showTab('graph')">
                                グラフ表示
                            </button>
                        </div>
                        
                        <div class="mt-3">
                            <div id="list-view" class="tab-content" style="display: block;">
                                <div class="alert alert-info">リスト表示コンテンツ</div>
                            </div>
                            <div id="graph-view" class="tab-content" style="display: none;">
                                <div class="alert alert-success">グラフ表示コンテンツ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// デバッグ出力関数
function debugLog(message) {
    const output = document.getElementById('debug-output');
    const timestamp = new Date().toLocaleTimeString();
    output.innerHTML += `<div>[${timestamp}] ${message}</div>`;
    console.log(`[DEBUG] ${message}`);
}

// シンプルテスト関数1
function testFunction1() {
    debugLog('testFunction1が呼ばれました');
    alert('testFunction1が動作しました！');
}

// グローバルテスト関数2
window.testFunction2 = function() {
    debugLog('window.testFunction2が呼ばれました');
    alert('window.testFunction2が動作しました！');
};

// タブ切り替え関数（デバッグ版）
window.showTab = function(tabName) {
    debugLog(`showTab('${tabName}')が呼ばれました`);
    
    try {
        // すべてのタブコンテンツを隠す
        const listView = document.getElementById('list-view');
        const graphView = document.getElementById('graph-view');
        const listTab = document.getElementById('list-tab');
        const graphTab = document.getElementById('graph-tab');
        
        debugLog(`要素取得結果: listView=${listView ? 'OK' : 'NG'}, graphView=${graphView ? 'OK' : 'NG'}, listTab=${listTab ? 'OK' : 'NG'}, graphTab=${graphTab ? 'OK' : 'NG'}`);
        
        if (!listView || !graphView || !listTab || !graphTab) {
            debugLog('ERROR: 必要な要素が見つかりません');
            return;
        }
        
        listView.style.display = 'none';
        graphView.style.display = 'none';
        
        // すべてのタブボタンのアクティブ状態をリセット
        listTab.classList.remove('active');
        graphTab.classList.remove('active');
        
        // 選択されたタブを表示
        if (tabName === 'list') {
            listView.style.display = 'block';
            listTab.classList.add('active');
            debugLog('リストタブをアクティブにしました');
        } else if (tabName === 'graph') {
            graphView.style.display = 'block';
            graphTab.classList.add('active');
            debugLog('グラフタブをアクティブにしました');
        }
        
        debugLog(`タブ切り替え完了: ${tabName}`);
        
    } catch (error) {
        debugLog(`ERROR: ${error.message}`);
        console.error('showTab error:', error);
    }
};

// DOM読み込み完了後の処理
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOMContentLoaded - ページ読み込み完了');
    
    // イベントリスナーのテスト
    const testBtn3 = document.getElementById('test-btn-3');
    if (testBtn3) {
        testBtn3.addEventListener('click', function() {
            debugLog('テスト3（イベントリスナー）が動作しました');
            alert('イベントリスナーが正常に動作しました！');
        });
        debugLog('テスト3のイベントリスナーを設定しました');
    }
    
    // 各要素の存在確認
    const elements = ['list-tab', 'graph-tab', 'list-view', 'graph-view'];
    elements.forEach(id => {
        const element = document.getElementById(id);
        debugLog(`要素 #${id}: ${element ? '存在' : '不在'}`);
    });
    
    debugLog('初期化完了');
});

// ページ読み込み時のテスト
debugLog('スクリプト読み込み開始');
</script>
@endsection