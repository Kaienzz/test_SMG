<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-slate-800 mb-2">ログイン</h2>
        <p class="text-slate-600 text-sm">アカウントにログインして冒険を続けましょう</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-700 text-sm">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

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
                autofocus 
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
                autocomplete="current-password"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition-all duration-200 @error('password') border-red-500 focus:ring-red-500 @enderror"
                placeholder="パスワードを入力"
            />
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    name="remember"
                    class="w-4 h-4 text-slate-800 bg-white border-slate-300 rounded focus:ring-slate-800 focus:ring-2"
                >
                <span class="ml-2 text-sm text-slate-600">ログイン状態を保持する</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-slate-600 hover:text-slate-800 transition-colors duration-200">
                    パスワードを忘れた方
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <button 
            type="submit"
            class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:ring-offset-2"
        >
            ログイン
        </button>
    </form>

    <!-- Registration Link -->
    <div class="mt-6 text-center">
        <p class="text-slate-600 text-sm">
            アカウントをお持ちでない方は
            <a href="{{ route('register') }}" class="text-slate-800 hover:text-slate-900 font-medium transition-colors duration-200">
                新規登録
            </a>
        </p>
    </div>
</x-guest-layout>
