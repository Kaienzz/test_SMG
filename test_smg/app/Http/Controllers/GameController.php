<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    // ゲームの状態初期化
    private function initGame(Request $request)
    {
        $state = [
            'current' => 'A', // A, road1, road2, road3, B
            'progress' => 0,
            'dice' => [null, null],
        ];
        $request->session()->put('game', $state);
        return $state;
    }

    // ゲーム状態取得
    private function getGame(Request $request)
    {
        if (!$request->session()->has('game')) {
            return $this->initGame($request);
        }
        return $request->session()->get('game');
    }

    // ゲーム状態保存
    private function saveGame(Request $request, $state)
    {
        $request->session()->put('game', $state);
    }

    public function index(Request $request)
    {
        $state = $this->getGame($request);
        return view('game.index', ['state' => $state]);
    }

    // サイコロを振る
    public function roll(Request $request)
    {
        $state = $this->getGame($request);
        $state['dice'] = [rand(1, 6), rand(1, 6)];
        $this->saveGame($request, $state);
        return redirect('/game');
    }

    // 左右に進む
    public function move(Request $request)
    {
        $state = $this->getGame($request);
        $direction = $request->input('direction'); // left or right
        $steps = array_sum($state['dice']);
        if ($direction === 'left') {
            $state['progress'] = max(0, $state['progress'] - $steps);
        } else {
            $state['progress'] = min(100, $state['progress'] + $steps);
        }
        $state['dice'] = [null, null]; // サイコロリセット
        $this->saveGame($request, $state);
        return redirect('/game');
    }

    // 次の道/町へ
    public function next(Request $request)
    {
        // リセットボタン対応
        if ($request->input('reset')) {
            $this->initGame($request);
            return redirect('/game');
        }
        $state = $this->getGame($request);
        $order = ['A', 'road1', 'road2', 'road3', 'B'];
        $idx = array_search($state['current'], $order);
        if ($idx !== false && $idx < count($order) - 1) {
            $state['current'] = $order[$idx + 1];
            $state['progress'] = ($state['current'] === 'B') ? 0 : 0;
            $state['dice'] = [null, null];
        }
        $this->saveGame($request, $state);
        return redirect('/game');
    }
}
