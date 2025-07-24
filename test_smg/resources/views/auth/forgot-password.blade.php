<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-slate-800 mb-2">パスワードリセット</h2>
        <p class="text-slate-600 text-sm">
            パスワードを忘れてしまいましたか？ご登録のメールアドレスを入力していただくと、
            パスワードリセット用のリンクをお送りします。
        </p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-green-700 text-sm font-medium">{{ session('status') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition-all duration-200 @error('email') border-red-500 focus:ring-red-500 @enderror"
                placeholder="your@email.com"
            />
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button -->
        <button 
            type="submit"
            class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:ring-offset-2"
        >
            パスワードリセットリンクを送信
        </button>
    </form>

    <!-- Back to Login -->
    <div class="mt-6 text-center">
        <p class="text-slate-600 text-sm">
            パスワードを思い出しましたか？
            <a href="{{ route('login') }}" class="text-slate-800 hover:text-slate-900 font-medium transition-colors duration-200">
                ログインページに戻る
            </a>
        </p>
    </div>
</x-guest-layout>
