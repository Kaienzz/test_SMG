<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ダッシュボード') | {{ config('app.name') }} 管理画面</title>
    
    <!-- Modern Light Theme - 既存スタイルとの統一 -->
    <link href="{{ asset('css/game-unified-layout.css') }}" rel="stylesheet">
    
    <!-- FontAwesome アイコン -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- 管理画面専用スタイル -->
    <style>
        :root {
            /* 管理画面カラーパレット - ゲームテーマとの差別化 */
            --admin-primary: #2563eb;
            --admin-primary-dark: #1d4ed8;
            --admin-secondary: #64748b;
            --admin-success: #10b981;
            --admin-warning: #f59e0b;
            --admin-danger: #ef4444;
            --admin-info: #06b6d4;
            
            /* 背景色 */
            --admin-bg: #f8fafc;
            --admin-sidebar-bg: #ffffff;
            --admin-content-bg: #ffffff;
            
            /* ボーダー・影 */
            --admin-border: #e2e8f0;
            --admin-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --admin-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* 管理画面専用レイアウト */
        .admin-layout {
            min-height: 100vh;
            background-color: var(--admin-bg);
            display: flex;
            flex-direction: column;
        }

        /* ヘッダー */
        .admin-header {
            background: var(--admin-sidebar-bg);
            border-bottom: 1px solid var(--admin-border);
            box-shadow: var(--admin-shadow);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .admin-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--admin-primary);
            margin: 0;
        }

        .admin-header .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* メインコンテンツエリア */
        .admin-main {
            display: flex;
            flex: 1;
        }

        /* サイドバー */
        .admin-sidebar {
            width: 256px;
            background: var(--admin-sidebar-bg);
            border-right: 1px solid var(--admin-border);
            overflow-y: auto;
            box-shadow: var(--admin-shadow);
        }

        .admin-nav {
            padding: 1.5rem 0;
        }

        .admin-nav-section {
            margin-bottom: 2rem;
        }

        .admin-nav-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--admin-secondary);
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.05em;
        }

        .admin-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .admin-nav-item:hover {
            background-color: #f3f4f6;
            color: var(--admin-primary);
            border-left-color: var(--admin-primary);
        }

        .admin-nav-item.active {
            background-color: #eff6ff;
            color: var(--admin-primary);
            border-left-color: var(--admin-primary);
            font-weight: 600;
        }

        .admin-nav-icon {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        /* サブメニューのスタイル */
        .admin-nav-submenu {
            background-color: #f9fafb;
            border-left: 2px solid #e5e7eb;
            margin-left: 1.5rem;
        }

        .admin-nav-submenu hr {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin: 0.5rem 1rem;
        }

        .admin-nav-subitem {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }

        .admin-nav-subitem:hover {
            background-color: #f3f4f6;
            color: var(--admin-primary);
            border-left-color: var(--admin-primary);
        }

        .admin-nav-subitem.active {
            background-color: #eff6ff;
            color: var(--admin-primary);
            border-left-color: var(--admin-primary);
            font-weight: 600;
        }

        .admin-nav-subitem .admin-nav-icon {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        /* コンテンツエリア */
        .admin-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        /* ページヘッダー */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 0.5rem 0;
        }

        .page-subtitle {
            color: var(--admin-secondary);
            margin: 0;
        }

        /* パンくずリスト */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .breadcrumb-item {
            color: var(--admin-secondary);
            text-decoration: none;
        }

        .breadcrumb-item:hover {
            color: var(--admin-primary);
        }

        .breadcrumb-item.active {
            color: #1f2937;
            font-weight: 500;
        }

        .breadcrumb-separator {
            color: var(--admin-border);
        }

        /* カード */
        .admin-card {
            background: var(--admin-content-bg);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: var(--admin-shadow);
            overflow: hidden;
        }

        .admin-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            background: #f9fafb;
        }

        .admin-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .admin-card-body {
            padding: 1.5rem;
        }

        /* ボタン */
        .admin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }

        .admin-btn-primary {
            background-color: var(--admin-primary);
            color: white;
        }

        .admin-btn-primary:hover {
            background-color: var(--admin-primary-dark);
            color: white;
        }

        .admin-btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        .admin-btn-success {
            background-color: var(--admin-success);
            color: white;
        }

        .admin-btn-warning {
            background-color: var(--admin-warning);
            color: white;
        }

        .admin-btn-danger {
            background-color: var(--admin-danger);
            color: white;
        }

        /* ステータスバッジ */
        .admin-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .admin-badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .admin-badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .admin-badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .admin-badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* アラート */
        .admin-alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .admin-alert-success {
            background-color: #f0fdf4;
            border-color: var(--admin-success);
            color: #166534;
        }

        .admin-alert-warning {
            background-color: #fffbeb;
            border-color: var(--admin-warning);
            color: #92400e;
        }

        .admin-alert-danger {
            background-color: #fef2f2;
            border-color: var(--admin-danger);
            color: #991b1b;
        }

        .admin-alert-info {
            background-color: #f0f9ff;
            border-color: var(--admin-info);
            color: #1e40af;
        }

        /* テーブル */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--admin-content-bg);
        }

        .admin-table th {
            background-color: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid var(--admin-border);
        }

        .admin-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }

        .admin-table tbody tr:hover {
            background-color: #f9fafb;
        }

        /* フォーム */
        .admin-form-group {
            margin-bottom: 1.5rem;
        }

        .admin-form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .admin-form-input {
            display: block;
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            background-color: white;
            transition: border-color 0.2s;
        }

        .admin-form-input:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* レスポンシブ */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                position: fixed;
                top: 64px;
                left: -100%;
                height: calc(100vh - 64px);
                z-index: 30;
                transition: left 0.3s;
            }

            .admin-sidebar.open {
                left: 0;
            }

            .admin-content {
                padding: 1rem;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }

        /* ユーティリティクラス */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mb-4 { margin-bottom: 1rem; }
        .mt-4 { margin-top: 1rem; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        .gap-2 { gap: 0.5rem; }
        .w-full { width: 100%; }

        /* Bootstrap互換クラス */
        .container-fluid { width: 100%; max-width: none; }
        .row { display: flex; flex-wrap: wrap; margin: 0 -0.75rem; }
        .col, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-lg-8, .col-xl-3 {
            padding: 0 0.75rem;
            flex: 1;
        }
        .col-md-2 { flex: 0 0 16.666667%; max-width: 16.666667%; }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-lg-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-xl-3 { flex: 0 0 25%; max-width: 25%; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }

        /* カード（Bootstrap互換） */
        .card { 
            background: var(--admin-content-bg);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: var(--admin-shadow);
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            background: #f9fafb;
        }
        .card-body {
            padding: 1.5rem;
        }
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        /* アラート（Bootstrap互換） */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        .alert-success {
            background-color: #f0fdf4;
            border-color: var(--admin-success);
            color: #166534;
        }
        .alert-warning {
            background-color: #fffbeb;
            border-color: var(--admin-warning);
            color: #92400e;
        }
        .alert-danger {
            background-color: #fef2f2;
            border-color: var(--admin-danger);
            color: #991b1b;
        }
        .alert-info {
            background-color: #f0f9ff;
            border-color: var(--admin-info);
            color: #1e40af;
        }

        /* フォーム（Bootstrap互換） */
        .form-group { margin-bottom: 1.5rem; }
        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            display: block;
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            background-color: white;
            transition: border-color 0.2s;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .is-invalid {
            border-color: var(--admin-danger);
        }
        .invalid-feedback {
            color: var(--admin-danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* ボタン（Bootstrap互換） */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            gap: 0.375rem;
            line-height: 1.5;
            font-family: inherit;
            font-size: 0.875rem;
        }
        .btn i {
            font-size: 1em;
        }
        .btn i:only-child {
            margin: 0;
        }
        .btn-primary {
            background-color: var(--admin-primary);
            color: white;
            border-color: var(--admin-primary);
        }
        .btn-primary:hover {
            background-color: var(--admin-primary-dark);
            color: white;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
            border-color: #6b7280;
        }
        .btn-outline-primary {
            background-color: transparent;
            color: var(--admin-primary);
            border-color: var(--admin-primary);
        }
        .btn-outline-primary:hover {
            background-color: var(--admin-primary);
            color: white;
        }
        .btn-outline-secondary {
            background-color: transparent;
            color: #6b7280;
            border-color: #6b7280;
        }
        .btn-outline-danger {
            background-color: transparent;
            color: var(--admin-danger);
            border-color: var(--admin-danger);
        }
        .btn-outline-danger:hover {
            background-color: var(--admin-danger);
            color: white;
        }
        .btn-outline-info {
            background-color: transparent;
            color: var(--admin-info);
            border-color: var(--admin-info);
        }
        .btn-outline-info:hover {
            background-color: var(--admin-info);
            color: white;
        }
        .btn-outline-success {
            background-color: transparent;
            color: var(--admin-success);
            border-color: var(--admin-success);
        }
        .btn-outline-success:hover {
            background-color: var(--admin-success);
            color: white;
        }
        .btn-outline-warning {
            background-color: transparent;
            color: var(--admin-warning);
            border-color: var(--admin-warning);
        }
        .btn-outline-warning:hover {
            background-color: var(--admin-warning);
            color: white;
        }
        .btn-success {
            background-color: var(--admin-success);
            color: white;
            border-color: var(--admin-success);
        }
        .btn-success:hover {
            background-color: #059669;
            color: white;
        }
        .btn-warning {
            background-color: var(--admin-warning);
            color: white;
            border-color: var(--admin-warning);
        }
        .btn-warning:hover {
            background-color: #d97706;
            color: white;
        }
        .btn-danger {
            background-color: var(--admin-danger);
            color: white;
            border-color: var(--admin-danger);
        }
        .btn-danger:hover {
            background-color: #dc2626;
            color: white;
        }
        .btn-info {
            background-color: var(--admin-info);
            color: white;
            border-color: var(--admin-info);
        }
        .btn-info:hover {
            background-color: #0891b2;
            color: white;
        }
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1.125rem;
        }
        .btn:disabled,
        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn:disabled:hover,
        .btn.disabled:hover {
            background-color: initial;
            border-color: initial;
            color: initial;
        }
        .btn-group {
            display: inline-flex;
            vertical-align: middle;
        }
        .btn-group .btn {
            position: relative;
            flex: 1 1 auto;
        }
        .btn-group .btn:not(:last-child) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            margin-right: -1px;
        }
        .btn-group .btn:not(:first-child) {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .btn-group .btn:hover,
        .btn-group .btn:focus,
        .btn-group .btn:active {
            z-index: 1;
        }
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 4px;
        }
        .btn-group-lg .btn {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border-radius: 8px;
        }
        .btn-block {
            display: block;
            width: 100%;
        }

        /* バッジ（Bootstrap互換） */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .bg-primary { background-color: var(--admin-primary); color: white; }
        .bg-success { background-color: var(--admin-success); color: white; }
        .bg-warning { background-color: var(--admin-warning); color: white; }
        .bg-danger { background-color: var(--admin-danger); color: white; }
        .bg-info { background-color: var(--admin-info); color: white; }
        .bg-secondary { background-color: #6b7280; color: white; }
        .bg-light { background-color: #f8f9fa; color: #1f2937; }
        .bg-dark { background-color: #1f2937; color: white; }

        /* テーブル（Bootstrap互換） */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--admin-content-bg);
        }
        .table thead th {
            background-color: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid var(--admin-border);
        }
        .table tbody td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
        }
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        .table-bordered {
            border: 1px solid var(--admin-border);
        }
        .table-bordered th,
        .table-bordered td {
            border: 1px solid var(--admin-border);
        }
        .table-responsive {
            overflow-x: auto;
        }

        /* ページネーション */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .breadcrumb-item {
            color: var(--admin-secondary);
            text-decoration: none;
        }
        .breadcrumb-item:hover {
            color: var(--admin-primary);
        }
        .breadcrumb-item.active {
            color: #1f2937;
            font-weight: 500;
        }

        /* タブ（Bootstrap互換） */
        .nav {
            display: flex;
            flex-wrap: wrap;
            padding-left: 0;
            margin-bottom: 0;
            list-style: none;
        }
        .nav-tabs {
            border-bottom: 1px solid var(--admin-border);
        }
        .nav-tabs .nav-item {
            margin-bottom: -1px;
        }
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            padding: 0.75rem 1rem;
            margin-right: 2px;
            text-decoration: none;
            color: var(--admin-secondary);
            background-color: transparent;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-tabs .nav-link:hover {
            border-color: var(--admin-border) var(--admin-border) var(--admin-border);
            color: var(--admin-primary);
        }
        .nav-tabs .nav-link.active {
            color: var(--admin-primary);
            background-color: var(--admin-content-bg);
            border-color: var(--admin-border) var(--admin-border) var(--admin-content-bg);
            font-weight: 600;
        }
        .nav-item {
            list-style: none;
        }
        .tab-content {
            margin-top: 1rem;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active,
        .tab-pane.show {
            display: block;
        }
        .fade {
            transition: opacity 0.15s linear;
        }
        .fade:not(.show) {
            opacity: 0;
        }

        /* タイポグラフィ */
        .h1, .h2, .h3, .h4, .h5, .h6,
        h1, h2, h3, h4, h5, h6 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 600;
            line-height: 1.2;
            color: #1f2937;
        }
        .h1, h1 { font-size: 2.5rem; }
        .h2, h2 { font-size: 2rem; }
        .h3, h3 { font-size: 1.75rem; }
        .h4, h4 { font-size: 1.5rem; }
        .h5, h5 { font-size: 1.25rem; }
        .h6, h6 { font-size: 1rem; }

        /* テキストカラー */
        .text-muted { color: var(--admin-secondary); }
        .text-primary { color: var(--admin-primary); }
        .text-secondary { color: #6b7280; }
        .text-success { color: var(--admin-success); }
        .text-warning { color: var(--admin-warning); }
        .text-danger { color: var(--admin-danger); }
        .text-info { color: var(--admin-info); }
        .text-gray-800 { color: #1f2937; }
        .text-gray-600 { color: #4b5563; }
        .text-gray-500 { color: #6b7280; }
        .text-gray-400 { color: #9ca3af; }
        .text-gray-300 { color: #d1d5db; }
        .text-white { color: white; }
        .text-dark { color: #1f2937; }

        /* フォントウェイト */
        .font-weight-normal { font-weight: 400; }
        .font-weight-bold { font-weight: 600; }
        .font-weight-bolder { font-weight: 700; }

        /* マージン・パディング */
        .m-0 { margin: 0; }
        .mt-0 { margin-top: 0; }
        .mb-0 { margin-bottom: 0; }
        .ml-0 { margin-left: 0; }
        .mr-0 { margin-right: 0; }
        .mt-1 { margin-top: 0.25rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mt-3 { margin-top: 0.75rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mt-4 { margin-top: 1rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mt-5 { margin-top: 1.25rem; }
        .mb-5 { margin-bottom: 1.25rem; }
        .mr-2 { margin-right: 0.5rem; }
        .me-2 { margin-right: 0.5rem; }
        .ml-2 { margin-left: 0.5rem; }
        .ms-2 { margin-left: 0.5rem; }
        .me-1 { margin-right: 0.25rem; }
        .ms-1 { margin-left: 0.25rem; }
        .me-3 { margin-right: 0.75rem; }
        .ms-3 { margin-left: 0.75rem; }

        .p-0 { padding: 0; }
        .pt-0 { padding-top: 0; }
        .pb-0 { padding-bottom: 0; }
        .pl-0 { padding-left: 0; }
        .pr-0 { padding-right: 0; }
        .pt-1 { padding-top: 0.25rem; }
        .pb-1 { padding-bottom: 0.25rem; }
        .pt-2 { padding-top: 0.5rem; }
        .pb-2 { padding-bottom: 0.5rem; }
        .pt-3 { padding-top: 0.75rem; }
        .pb-3 { padding-bottom: 0.75rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-3 { padding-top: 1rem; padding-bottom: 1rem; }

        /* Display・Flex */
        .d-none { display: none; }
        .d-block { display: block; }
        .d-inline { display: inline; }
        .d-inline-block { display: inline-block; }
        .d-flex { display: flex; }
        .d-inline-flex { display: inline-flex; }
        .justify-content-start { justify-content: flex-start; }
        .justify-content-center { justify-content: center; }
        .justify-content-end { justify-content: flex-end; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-around { justify-content: space-around; }
        .align-items-start { align-items: flex-start; }
        .align-items-center { align-items: center; }
        .align-items-end { align-items: flex-end; }
        .gap-1 { gap: 0.25rem; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }

        /* その他のユーティリティ */
        .shadow { box-shadow: var(--admin-shadow); }
        .h-100 { height: 100%; }
        .w-100 { width: 100%; }
        .text-uppercase { text-transform: uppercase; }
        .text-lowercase { text-transform: lowercase; }
        .text-capitalize { text-transform: capitalize; }
        .text-decoration-none { text-decoration: none; }
        .border { border: 1px solid var(--admin-border); }
        .border-0 { border: 0; }
        .rounded { border-radius: 6px; }
        .rounded-0 { border-radius: 0; }
        .position-relative { position: relative; }
        .position-absolute { position: absolute; }
        .overflow-hidden { overflow: hidden; }
        .overflow-auto { overflow: auto; }
    </style>

    @stack('styles')
</head>
<body>
    <div class="admin-layout">
        <!-- ヘッダー -->
        <header class="admin-header">
            <div class="flex items-center gap-2">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1>{{ config('app.name') }} 管理画面</h1>
            </div>
            
            <div class="user-menu">
                <span>{{ $adminUser->name }}</span>
                <div class="admin-badge admin-badge-info">{{ ucfirst($adminUser->admin_level) }}</div>
                <a href="{{ route('logout') }}" class="admin-btn admin-btn-secondary"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    ログアウト
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </header>

        <div class="admin-main">
            <!-- サイドバー -->
            <nav class="admin-sidebar" id="adminSidebar">
                <div class="admin-nav">
                    <!-- ダッシュボード -->
                    <div class="admin-nav-section">
                        <div class="admin-nav-title">ダッシュボード</div>
                        <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                            </svg>
                            概要
                        </a>
                    </div>

                    <!-- ユーザー管理 -->
                    @if((isset($canManageUsers) && $canManageUsers) || (isset($adminUser) && $adminUser->admin_level === 'super'))
                    <div class="admin-nav-section">
                        <div class="admin-nav-title">ユーザー管理</div>
                        <a href="{{ route('admin.users.index') }}" class="admin-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                            </svg>
                            ユーザー一覧
                        </a>
                        <a href="{{ route('admin.players.index') }}" class="admin-nav-item {{ request()->routeIs('admin.players.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                            プレイヤー管理
                        </a>
                    </div>
                    @endif

                    <!-- ゲームデータ管理 -->
                    @if((isset($canManageGameData) && $canManageGameData) || (isset($adminUser) && $adminUser->admin_level === 'super'))
                    <div class="admin-nav-section">
                        <div class="admin-nav-title">ゲームデータ</div>
                        <a href="{{ route('admin.items.index') }}" class="admin-nav-item {{ request()->routeIs('admin.items.index') || request()->routeIs('admin.items.show') || request()->routeIs('admin.items.edit') || request()->routeIs('admin.items.create') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            アイテム管理
                        </a>
                        <div class="admin-nav-submenu">
                            <a href="{{ route('admin.items.standard') }}" class="admin-nav-subitem {{ request()->routeIs('admin.items.standard*') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                </svg>
                                標準アイテム管理
                            </a>
                            <a href="{{ route('admin.items.standard.create') }}" class="admin-nav-subitem {{ request()->routeIs('admin.items.standard.create') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                </svg>
                                標準アイテム追加
                            </a>
                        </div>
                        <a href="{{ route('admin.monsters.index') }}" class="admin-nav-item {{ request()->routeIs('admin.monsters.index') || request()->routeIs('admin.monsters.show') || request()->routeIs('admin.monsters.edit') || request()->routeIs('admin.monsters.create') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            モンスター管理
                        </a>
                        <div class="admin-nav-submenu">
                            <a href="{{ route('admin.monsters.spawn-lists.index') }}" class="admin-nav-subitem {{ request()->routeIs('admin.monsters.spawn-lists.*') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                                </svg>
                                モンスタースポーン管理
                            </a>
                        </div>
                        <a href="{{ route('admin.shops.index') }}" class="admin-nav-item {{ request()->routeIs('admin.shops.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5zM8 15a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            ショップ管理
                        </a>
                        <a href="{{ route('admin.locations.index') }}" class="admin-nav-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            マップ管理
                        </a>
                        <div class="admin-nav-submenu">
                            <a href="{{ route('admin.locations.pathways') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.pathways*') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                                </svg>
                                道・ダンジョン管理
                            </a>
                            <a href="{{ route('admin.locations.towns') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.towns*') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5zM8 15a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                町管理
                            </a>
                            <a href="{{ route('admin.locations.connections') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.connections*') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"></path>
                                </svg>
                                マップ接続管理
                            </a>
                            <hr class="border-top my-2 mx-3">
                            <a href="{{ route('admin.locations.roads') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.roads*') && !request()->routeIs('admin.locations.pathways*') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                道管理（旧）
                            </a>
                            <a href="{{ route('admin.locations.dungeons') }}" class="admin-nav-subitem {{ request()->routeIs('admin.locations.dungeons*') && !request()->routeIs('admin.locations.pathways*') ? 'active' : '' }}">
                                <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                ダンジョン管理（旧）
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- 分析・監視 -->
                    @if((isset($canAccessAnalytics) && $canAccessAnalytics) || (isset($adminUser) && $adminUser->admin_level === 'super'))
                    <div class="admin-nav-section">
                        <div class="admin-nav-title">分析・監視</div>
                        <a href="{{ route('admin.analytics.index') }}" class="admin-nav-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                            </svg>
                            分析ダッシュボード
                        </a>
                        <a href="{{ route('admin.audit.index') }}" class="admin-nav-item {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586 13.293 6.293a1 1 0 011.414 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            監査ログ
                        </a>
                    </div>
                    @endif

                    <!-- システム管理 -->
                    @if((isset($canManageSystem) && $canManageSystem) || (isset($adminUser) && $adminUser->admin_level === 'super'))
                    <div class="admin-nav-section">
                        <div class="admin-nav-title">システム管理</div>
                        <a href="{{ route('admin.system.config') }}" class="admin-nav-item {{ request()->routeIs('admin.system.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                            </svg>
                            システム設定
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="admin-nav-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                            </svg>
                            ロール・権限管理
                        </a>
                    </div>
                    @endif
                </div>
            </nav>

            <!-- メインコンテンツ -->
            <main class="admin-content">
                <!-- パンくずリスト -->
                @if(isset($breadcrumb))
                <nav class="breadcrumb">
                    @foreach($breadcrumb as $index => $item)
                        @if($index > 0)
                            <span class="breadcrumb-separator">></span>
                        @endif
                        @if($item['active'])
                            <span class="breadcrumb-item active">{{ $item['title'] }}</span>
                        @else
                            <a href="{{ $item['url'] }}" class="breadcrumb-item">{{ $item['title'] }}</a>
                        @endif
                    @endforeach
                </nav>
                @endif

                <!-- ページヘッダー -->
                <div class="page-header">
                    <h1 class="page-title">@yield('title', 'ダッシュボード')</h1>
                    @hasSection('subtitle')
                        <p class="page-subtitle">@yield('subtitle')</p>
                    @endif
                </div>

                <!-- アラート表示 -->
                @if(session('success'))
                    <div class="admin-alert admin-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="admin-alert admin-alert-warning">
                        {{ session('warning') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="admin-alert admin-alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="admin-alert admin-alert-danger">
                        <ul style="margin: 0; padding-left: 1rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- ページコンテンツ -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // モバイルメニュートグル
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('open');
        });

        // CSRF Token設定
        window.axios = window.axios || {};
        window.axios.defaults = window.axios.defaults || {};
        window.axios.defaults.headers = window.axios.defaults.headers || {};
        window.axios.defaults.headers.common = window.axios.defaults.headers.common || {};
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // 管理画面共通機能
        window.AdminUI = {
            // 確認ダイアログ
            confirm: function(message, callback) {
                if (confirm(message)) {
                    callback();
                }
            },

            // 危険な操作の確認
            confirmDangerous: function(message, callback) {
                const confirmMessage = message + '\n\nこの操作は元に戻せません。続行しますか？';
                if (confirm(confirmMessage)) {
                    callback();
                }
            },

            // 成功メッセージ表示
            showSuccess: function(message) {
                this.showAlert(message, 'success');
            },

            // エラーメッセージ表示
            showError: function(message) {
                this.showAlert(message, 'danger');
            },

            // アラート表示
            showAlert: function(message, type) {
                const alertHtml = `<div class="admin-alert admin-alert-${type}">${message}</div>`;
                const container = document.querySelector('.admin-content');
                container.insertAdjacentHTML('afterbegin', alertHtml);
                
                // 5秒後に自動削除
                setTimeout(() => {
                    const alert = container.querySelector('.admin-alert');
                    if (alert) {
                        alert.remove();
                    }
                }, 5000);
            }
        };

        // タブ機能
        function initializeTabs() {
            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('data-bs-target');
                    const target = document.querySelector(targetId);
                    
                    if (!target) return;
                    
                    // 全てのタブボタンからactiveクラスを削除
                    const allButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
                    allButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // 全てのタブペインを非表示
                    const allPanes = document.querySelectorAll('.tab-pane');
                    allPanes.forEach(pane => {
                        pane.classList.remove('active', 'show');
                    });
                    
                    // クリックされたボタンをアクティブに
                    this.classList.add('active');
                    
                    // 対象のタブペインを表示
                    target.classList.add('active', 'show');
                });
            });
        }

        // DOMContentLoaded時にタブを初期化
        document.addEventListener('DOMContentLoaded', function() {
            initializeTabs();
        });
    </script>

    @stack('scripts')
</body>
</html>