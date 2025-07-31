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
     * ユーザーエクスペリエンス向上のため、ログイン完了後は常にDashboardにリダイレクト
     */
    private function redirectAfterLogin($user): RedirectResponse
    {
        // intended URLがある場合は削除（使用しない）
        session()->forget('url.intended');
        
        // プレイヤーの作成・更新を実行（データの整合性確保）
        $player = $user->getOrCreatePlayer();
        
        // 常にDashboardにリダイレクトし、ユーザーが自分でゲームを開始する
        return redirect()->route('dashboard')->with('status', 'ログインが完了しました。');
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
