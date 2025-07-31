<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            ブラウザRPG - ダッシュボード
        </h2>
    </x-slot>

    <div class="py-12" style="background: linear-gradient(135deg, #fafafa 0%, #f8fafc 50%, #f1f5f9 100%); min-height: calc(100vh - 64px);">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- New User Welcome Message -->
            @if(session('welcome_new_user'))
                <div class="mb-8 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-2xl p-8 shadow-lg">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold mb-4">🎉 ようこそ、冒険者！</h2>
                        <p class="text-blue-100 text-lg mb-6">
                            アカウント作成ありがとうございます！あなた専用のキャラクターと装備が準備されました。<br>
                            A町から始まる壮大な冒険の世界へ旅立ちましょう！
                        </p>
                        <div class="flex justify-center space-x-4">
                            <a href="{{ route('game.index') }}" class="bg-white text-blue-600 font-semibold py-3 px-8 rounded-lg hover:bg-blue-50 transition-all duration-200 transform hover:-translate-y-1">
                                🚀 冒険を始める
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Status Messages -->
            @if(session('status'))
                <div class="mb-8 bg-green-50 border border-green-200 text-green-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="text-green-600 mr-3">✅</div>
                        <div class="text-lg font-medium">{{ session('status') }}</div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-lg rounded-2xl border border-slate-200">
                <div class="p-8 text-slate-900">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-semibold text-slate-800 mb-2">ようこそ、{{ Auth::user()->name }}さん！</h3>
                        <p class="text-slate-600">あなたの冒険の拠点です</p>
                    </div>
                    
                    @php
                        $player = Auth::user()->getOrCreatePlayer();
                    @endphp
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- ゲーム開始 -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-6 hover:shadow-md transition-all duration-200">
                            <h4 class="text-slate-800 font-semibold text-xl mb-3 flex items-center">
                                🎮 <span class="ml-2">ゲーム開始</span>
                            </h4>
                            <p class="text-slate-600 mb-4">
                                @if($player && $player->location_type !== 'town')
                                    前回の冒険の続きから始めましょう！現在地: {{ $player->location_id === 'town_a' ? 'A町' : $player->location_id }}
                                @elseif($player)
                                    A町での冒険が待っています！町の施設を利用したり、次の場所へ移動しましょう。
                                @else
                                    新しい冒険の世界へ旅立ちましょう！A町からあなたの物語が始まります。
                                @endif
                            </p>
                            <a href="{{ route('game.index') }}" 
                               style="
                                   display: inline-flex !important;
                                   align-items: center !important;
                                   justify-content: center !important;
                                   padding: 0.875rem 1.5rem !important;
                                   background-color: #0f172a !important;
                                   color: white !important;
                                   font-weight: 500 !important;
                                   border-radius: 0.5rem !important;
                                   border: 1px solid transparent !important;
                                   text-decoration: none !important;
                                   cursor: pointer !important;
                                   transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                                   min-height: 44px !important;
                                   min-width: 44px !important;
                                   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
                                   font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans JP', system-ui, sans-serif !important;
                                   font-size: 1rem !important;
                                   line-height: 1.2 !important;
                                   user-select: none !important;
                               "
                               onmouseover="this.style.backgroundColor='#1e293b'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)'"
                               onmouseout="this.style.backgroundColor='#0f172a'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)'"
                               onmousedown="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px rgba(0, 0, 0, 0.05)'"
                               onmouseup="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)'"
                               onfocus="this.style.boxShadow='0 0 0 2px rgba(59, 130, 246, 0.8)'"
                               onblur="this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)'">
                                @if($player && $player->location_type !== 'town')
                                    冒険を続ける
                                @else
                                    冒険を始める
                                @endif
                            </a>
                        </div>
                        
                        <!-- プロフィール -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-6 hover:shadow-md transition-all duration-200">
                            <h4 class="text-slate-800 font-semibold text-xl mb-3 flex items-center">
                                👤 <span class="ml-2">プロフィール</span>
                            </h4>
                            <p class="text-slate-600 mb-4">アカウント設定を管理できます。</p>
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg border border-slate-300 transition-all duration-200 transform hover:-translate-y-0.5">
                                プロフィール編集
                            </a>
                        </div>
                    </div>
                    
                    <!-- ゲーム情報 -->
                    @if($player)
                    <div class="mt-8 bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-6">
                        <h4 class="text-slate-800 font-semibold text-xl mb-6 flex items-center">
                            📊 <span class="ml-2">キャラクター情報</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-sm">
                            <div class="text-center bg-white rounded-lg p-4 border border-slate-200">
                                <div class="text-3xl font-bold text-blue-600 mb-2">{{ $player->level ?? 1 }}</div>
                                <div class="text-slate-600 font-medium">レベル</div>
                            </div>
                            <div class="text-center bg-white rounded-lg p-4 border border-slate-200">
                                <div class="text-3xl font-bold text-green-600 mb-2">{{ number_format($player->gold ?? 0) }}</div>
                                <div class="text-slate-600 font-medium">所持金 (G)</div>
                            </div>
                            <div class="text-center bg-white rounded-lg p-4 border border-slate-200">
                                <div class="text-3xl font-bold text-red-600 mb-2">{{ $player->hp ?? 0 }}/{{ $player->max_hp ?? 0 }}</div>
                                <div class="text-slate-600 font-medium">HP</div>
                            </div>
                            <div class="text-center bg-white rounded-lg p-4 border border-slate-200">
                                <div class="text-2xl font-bold text-purple-600 mb-2">
                                    @if($player->location_id === 'town_a')
                                        A町
                                    @else
                                        {{ $player->location_id }}
                                    @endif
                                </div>
                                <div class="text-slate-600 font-medium">現在地</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
