<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // ユーザーのデバイス情報を更新
        $user = Auth::user();
        $user->updateDeviceActivity();

        // 既存ユーザーのリダイレクト処理
        return $this->redirectAfterLogin($user);
    }

    /**
     * 認証後のリダイレクト処理
     */
    private function redirectAfterLogin($user): RedirectResponse
    {
        // ゲーム画面に戻る場合の優先度チェック
        $character = $user->character;
        
        if ($character && $character->location_type === 'battle') {
            // バトル中の場合は戦闘画面へ
            return redirect()->intended(route('battle.index'));
        }
        
        if ($character) {
            // キャラクターが存在する場合は常にゲーム画面へ（セッション継続）
            return redirect()->intended(route('game.index'));
        }
        
        // キャラクターが無い場合のみダッシュボードへ
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
