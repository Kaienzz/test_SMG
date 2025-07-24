<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>シンプルブラウザRPG - 冒険の世界へ</title>

        <!-- Design System CSS with cache buster -->
        <link rel="stylesheet" href="{{ asset('css/game-design-system.css') }}?v={{ time() }}">
        <!-- Meta tag to prevent caching during development -->
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
        
        <!-- Light Modern Theme Override -->
        <style>
            /* Modern Light Theme */
            .welcome-hero {
                background: linear-gradient(135deg, #fafafa 0%, #f8fafc 50%, #f1f5f9 100%) !important;
                color: #334155 !important;
                min-height: 100vh !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                position: relative !important;
                overflow: hidden !important;
            }
            
            .welcome-hero::before {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: 
                    radial-gradient(circle at 20% 50%, rgba(148, 163, 184, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(203, 213, 225, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 40% 80%, rgba(226, 232, 240, 0.05) 0%, transparent 50%) !important;
            }
            
            .welcome-container {
                max-width: 1200px !important;
                margin: 0 auto !important;
                padding: 1.5rem !important;
                text-align: center !important;
                position: relative !important;
                z-index: 1 !important;
            }
            
            .welcome-title {
                font-size: clamp(3rem, 8vw, 5rem) !important;
                font-weight: 700 !important;
                margin-bottom: 2rem !important;
                color: #1e293b !important;
                text-shadow: none !important;
                background: linear-gradient(135deg, #1e293b, #475569, #64748b) !important;
                background-size: 200% 200% !important;
                -webkit-background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                background-clip: text !important;
            }
            
            .welcome-subtitle {
                color: #475569 !important;
                font-size: 1.25rem !important;
                margin-bottom: 3rem !important;
                opacity: 1 !important;
                max-width: 700px !important;
                margin-left: auto !important;
                margin-right: auto !important;
                line-height: 1.7 !important;
                text-shadow: none !important;
            }
            
            .btn {
                padding: 0.875rem 1.5rem !important;
                border-radius: 0.5rem !important;
                font-weight: 500 !important;
                font-size: 1rem !important;
                border: 1px solid transparent !important;
                cursor: pointer !important;
                display: inline-block !important;
                text-align: center !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                min-width: 140px !important;
                margin: 0.5rem !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
            }
            
            .btn-primary {
                background: #0f172a !important;
                color: white !important;
                border-color: #0f172a !important;
            }
            
            .btn-primary:hover {
                background: #1e293b !important;
                border-color: #1e293b !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            }
            
            .btn-success {
                background: white !important;
                color: #475569 !important;
                border-color: #e2e8f0 !important;
            }
            
            .btn-success:hover {
                background: #f8fafc !important;
                border-color: #cbd5e1 !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            }
            
            .feature-grid {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
                gap: 2rem !important;
                margin: 4rem 0 !important;
            }
            
            .feature-card {
                background: white !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 1rem !important;
                padding: 2rem !important;
                text-align: center !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
                transition: all 0.3s ease !important;
            }
            
            .feature-card:hover {
                transform: translateY(-4px) !important;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
                border-color: #cbd5e1 !important;
            }
            
            .feature-icon {
                font-size: 3.5rem !important;
                margin-bottom: 1.5rem !important;
                display: block !important;
                filter: none !important;
            }
            
            .feature-title {
                color: #1e293b !important;
                font-weight: 600 !important;
                font-size: 1.25rem !important;
                margin-bottom: 1rem !important;
                text-shadow: none !important;
            }
            
            .feature-description {
                color: #64748b !important;
                font-size: 0.95rem !important;
                line-height: 1.6 !important;
                text-shadow: none !important;
            }
        </style>

        <!-- Scripts only (no Tailwind CSS) -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/js/app.js'])
        @endif
    </head>
    <body>
        <!-- Hero Section with Features -->
        <div class="welcome-hero">
            <div class="welcome-container">
                <h1 class="welcome-title">懐かしのCGIゲーム風RPG</h1>
                <p class="welcome-subtitle">シンプルで奥深い冒険の世界へようこそ。<br>サイコロを振って運命を切り開こう！</p>
                
                <div class="button-group">
                    @auth
                        <a href="{{ route('game.index') }}" class="btn btn-primary btn-large">ゲーム開始</a>
                        <a href="{{ route('dashboard') }}" class="btn btn-success">ダッシュボード</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary btn-large">今すぐ始める</a>
                        <a href="{{ route('login') }}" class="btn btn-success">ログイン</a>
                    @endauth
                </div>

                <!-- Features Grid -->
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🎲</div>
                        <h3 class="feature-title">サイコロベースの戦闘</h3>
                        <p class="feature-description">運と戦略が織りなす緊張感あふれる戦闘システム。サイコロの目があなたの運命を決める！</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🏘️</div>
                        <h3 class="feature-title">探索とクエスト</h3>
                        <p class="feature-description">様々な町や道を探索し、興味深いクエストに挑戦しよう。新しい発見があなたを待っている。</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">⚔️</div>
                        <h3 class="feature-title">装備とスキル</h3>
                        <p class="feature-description">豊富な装備とスキルシステムで、あなただけのキャラクターを育成しよう。</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- JavaScript for interactive elements -->
        <script>
            // Simple dice animation
            const dice = document.querySelectorAll('.dice');
            const diceSymbols = ['⚀', '⚁', '⚂', '⚃', '⚄', '⚅'];
            
            dice.forEach(die => {
                die.addEventListener('click', function() {
                    const randomIndex = Math.floor(Math.random() * diceSymbols.length);
                    this.textContent = diceSymbols[randomIndex];
                });
            });

            // Choice selection highlighting
            const choiceOptions = document.querySelectorAll('.choice-option');
            choiceOptions.forEach(option => {
                option.addEventListener('click', function() {
                    choiceOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
        </script>
    </body>
</html>