<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-slate-800 mb-2">新しいパスワードの設定</h2>
        <p class="text-slate-600 text-sm">
            新しいパスワードを入力して、アカウントのパスワードをリセットしてください。
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                メールアドレス
            </label>
            <input 
                id="email" 
                type="email" 
                name="email" 
                value="{{ old('email', $request->email) }}" 
                required 
                autofocus 
                autocomplete="username"
                readonly
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-slate-50 text-slate-700 cursor-not-allowed"
            />
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-2">
                新しいパスワード
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

        <!-- Submit Button -->
        <button 
            type="submit"
            class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:ring-offset-2"
        >
            パスワードをリセット
        </button>
    </form>

    <!-- Back to Login -->
    <div class="mt-6 text-center">
        <p class="text-slate-600 text-sm">
            <a href="{{ route('login') }}" class="text-slate-800 hover:text-slate-900 font-medium transition-colors duration-200">
                ログインページに戻る
            </a>
        </p>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            
            if (password.length === 0) {
                strengthDiv.classList.add('hidden');
                return;
            }
            
            strengthDiv.classList.remove('hidden');
            
            let score = 0;
            
            // Length check
            if (password.length >= 8) score += 25;
            if (password.length >= 12) score += 10;
            
            // Character type checks
            if (/[a-zA-Z]/.test(password)) score += 25;
            if (/[0-9]/.test(password)) score += 25;
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
