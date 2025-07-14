<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GameController extends Controller
{
    // ゲーム画面表示
    public function index()
    {
        return view('game');
    }

    // ゲーム状態取得API
    public function state(Request $request)
    {
        // 仮の状態を返す
        $state = Session::get('game_state', [
            'current_road' => 1,
            'progress' => 0,
            'position' => 'road', // 'road' or 'town'
        ]);
        return response()->json($state);
    }

    // サイコロを振って移動API
    public function move(Request $request)
    {
        // 仮のロジック
        $direction = $request->input('direction', 'right');
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        $move = $dice1 + $dice2;
        $state = Session::get('game_state', [
            'current_road' => 1,
            'progress' => 0,
            'position' => 'road',
        ]);
        if ($direction === 'right') {
            $state['progress'] = min(100, $state['progress'] + $move);
        } else {
            $state['progress'] = max(0, $state['progress'] - $move);
        }
        Session::put('game_state', $state);
        return response()->json([
            'dice' => [$dice1, $dice2],
            'state' => $state,
        ]);
    }

    // 次の道または町へ進むAPI
    public function next(Request $request)
    {
        $state = Session::get('game_state', [
            'current_road' => 1,
            'progress' => 0,
            'position' => 'road',
        ]);
        if ($state['position'] === 'road') {
            if ($state['progress'] === 0) {
                $state['position'] = 'town';
                $state['current_road'] = max(1, $state['current_road'] - 1);
            } elseif ($state['progress'] === 100) {
                $state['position'] = 'town';
                $state['current_road'] = min(3, $state['current_road'] + 1);
            }
        } else {
            $state['position'] = 'road';
        }
        // 町A/Bの判定は省略（デモ用）
        Session::put('game_state', $state);
        return response()->json($state);
    }
}