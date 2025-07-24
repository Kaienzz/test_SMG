<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0" style="background: linear-gradient(135deg, #fafafa 0%, #f8fafc 50%, #f1f5f9 100%);">
            <div class="mb-8">
                <a href="/" class="flex items-center justify-center">
                    <div class="text-center">
                        <h1 class="text-4xl font-bold text-slate-800 mb-2">test_smg</h1>
                        <p class="text-slate-600 text-sm">シンプルブラウザRPG</p>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md px-8 py-8 bg-white shadow-lg overflow-hidden rounded-2xl border border-slate-200">
                {{ $slot }}
            </div>
            
            <!-- フッター -->
            <div class="mt-8 text-center">
                <p class="text-slate-500 text-sm">
                    © 2025 test_smg. モダンなCGI風ブラウザゲーム
                </p>
            </div>
        </div>
    </body>
</html>
