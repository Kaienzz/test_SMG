<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-slate-800 mb-2">新規登録</h2>
        <p class="text-slate-600 text-sm">新しいアカウントを作成して冒険を始めましょう</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                冒険者名
            </label>
            <input 
                id="name" 
                type="text" 
                name="name" 
                value="{{ old('name') }}" 
                required 
                autofocus 
                autocomplete="name"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition-all duration-200 @error('name') border-red-500 focus:ring-red-500 @enderror"
                placeholder="あなたの冒険者名"
            />
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                メールアドレス
            </label>
            <input 
                id="email" 
                type="email" 
                name="email" 
                value="{{ old('email') }}" 
                required 
                autocomplete="username"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition-all duration-200 @error('email') border-red-500 focus:ring-red-500 @enderror"
                placeholder="your@email.com"
            />
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-2">
                パスワード
            </label>
            <input 
                id="password" 
                type="password" 
                name="password" 
                required 
                autocomplete="new-password"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition-all duration-200 @error('password') border-red-500 focus:ring-red-500 @enderror"
                placeholder="8文字以上のパスワード"
                onchange="checkPasswordStrength(this.value)"
            />
            
            <!-- Password Strength Indicator -->
            <div id="password-strength" class="mt-2 hidden">
                <div class="flex items-center space-x-2">
                    <div class="flex-1 bg-slate-200 rounded-full h-2">
                        <div id="strength-bar" class="h-2 rounded-full transition-all duration-300"></div>
                    </div>
                    <span id="strength-text" class="text-xs text-slate-600"></span>
                </div>
                <div id="password-requirements" class="mt-2 text-xs text-slate-500">
                    <p class="mb-1">パスワードの要件:</p>
                    <ul class="space-y-1">
                        <li id="req-length" class="flex items-center">
                            <span class="w-3 h-3 mr-2 rounded-full bg-slate-300"></span>
                            8文字以上
                        </li>
                        <li id="req-letter" class="flex items-center">
                            <span class="w-3 h-3 mr-2 rounded-full bg-slate-300"></span>
                            英字を含む
                        </li>
                        <li id="req-number" class="flex items-center">
                            <span class="w-3 h-3 mr-2 rounded-full bg-slate-300"></span>
                            数字を含む
                        </li>
                    </ul>
                </div>
            </div>
            
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">
                パスワード確認
            </label>
            <input 
                id="password_confirmation" 
                type="password" 
                name="password_confirmation" 
                required 
                autocomplete="new-password"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition-all duration-200 @error('password_confirmation') border-red-500 focus:ring-red-500 @enderror"
                placeholder="パスワードをもう一度入力"
            />
            @error('password_confirmation')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Terms Agreement -->
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <p class="text-xs text-slate-600 leading-relaxed">
                アカウントを作成することで、本サービスの
                <a href="#" class="text-slate-800 hover:text-slate-900 underline">利用規約</a>
                および
                <a href="#" class="text-slate-800 hover:text-slate-900 underline">プライバシーポリシー</a>
                に同意したものとみなされます。
            </p>
        </div>

        <!-- Submit Button -->
        <button 
            type="submit"
            style="
                width: 100%;
                background-color: #0f172a !important;
                color: white !important;
                font-weight: 500 !important;
                padding: 0.875rem 1.5rem !important;
                border-radius: 0.5rem !important;
                border: 1px solid transparent !important;
                cursor: pointer !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                min-height: 44px !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
                text-decoration: none !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
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
            onblur="this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)'"
        >
            アカウント作成
        </button>
    </form>

    <!-- Login Link -->
    <div class="mt-6 text-center">
        <p style="color: #475569 !important; font-size: 0.875rem !important;">
            既にアカウントをお持ちの方は
            <a href="{{ route('login') }}" 
               style="
                   color: #1e293b !important;
                   font-weight: 500 !important;
                   text-decoration: none !important;
                   transition: color 0.2s ease !important;
               "
               onmouseover="this.style.color='#0f172a'"
               onmouseout="this.style.color='#1e293b'">
                ログイン
            </a>
        </p>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const reqLength = document.getElementById('req-length').querySelector('span');
            const reqLetter = document.getElementById('req-letter').querySelector('span');
            const reqNumber = document.getElementById('req-number').querySelector('span');
            
            if (password.length === 0) {
                strengthDiv.classList.add('hidden');
                return;
            }
            
            strengthDiv.classList.remove('hidden');
            
            let score = 0;
            let requirements = 0;
            
            // Check length
            if (password.length >= 8) {
                score += 25;
                requirements++;
                reqLength.className = 'w-3 h-3 mr-2 rounded-full bg-green-500';
            } else {
                reqLength.className = 'w-3 h-3 mr-2 rounded-full bg-slate-300';
            }
            
            // Check for letters
            if (/[a-zA-Z]/.test(password)) {
                score += 25;
                requirements++;
                reqLetter.className = 'w-3 h-3 mr-2 rounded-full bg-green-500';
            } else {
                reqLetter.className = 'w-3 h-3 mr-2 rounded-full bg-slate-300';
            }
            
            // Check for numbers
            if (/[0-9]/.test(password)) {
                score += 25;
                requirements++;
                reqNumber.className = 'w-3 h-3 mr-2 rounded-full bg-green-500';
            } else {
                reqNumber.className = 'w-3 h-3 mr-2 rounded-full bg-slate-300';
            }
            
            // Additional complexity
            if (password.length >= 12) score += 10;
            if (/[^a-zA-Z0-9]/.test(password)) score += 15;
            
            // Update bar and text
            strengthBar.style.width = score + '%';
            
            if (score < 25) {
                strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-red-500';
                strengthText.textContent = '弱い';
                strengthText.className = 'text-xs text-red-600';
            } else if (score < 50) {
                strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-yellow-500';
                strengthText.textContent = '普通';
                strengthText.className = 'text-xs text-yellow-600';
            } else if (score < 75) {
                strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-blue-500';
                strengthText.textContent = '良い';
                strengthText.className = 'text-xs text-blue-600';
            } else {
                strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-green-500';
                strengthText.textContent = '強い';
                strengthText.className = 'text-xs text-green-600';
            }
        }
    </script>
</x-guest-layout>
