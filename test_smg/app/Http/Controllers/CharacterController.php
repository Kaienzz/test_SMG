<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CharacterController extends Controller
{
    use \App\Http\Controllers\Traits\HasCharacter;
    public function index(): View
    {
        $player = $this->getOrCreatePlayer();
        $player->updatePlayerLevel();
        $stats = $player->getDetailedStatsWithLevel();
        $summary = $player->getStatusSummary();
        
        return view('character.index', [
            'player' => $player,
            'character' => $player, // 下位互換性のためのalias
            'stats' => $stats,
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('character.create');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|min:1'
        ]);

        $player = Player::createNewPlayer(Auth::id(), $request->input('name'));
        
        return response()->json([
            'success' => true,
            'player' => $player->getDetailedStats(),
            'character' => $player->getDetailedStats(), // 下位互換性のためのalias
            'message' => 'プレイヤーが作成されました！'
        ]);
    }

    public function show(): JsonResponse
    {
        $player = $this->getOrCreatePlayer();
        
        return response()->json([
            'player' => $player->getDetailedStats(),
            'character' => $player->getDetailedStats(), // 下位互換性のためのalias
            'summary' => $player->getStatusSummary(),
        ]);
    }

    public function heal(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'integer|min:1|max:1000'
        ]);

        $player = $this->getOrCreatePlayer();
        $amount = $request->input('amount', 10);
        
        $oldHp = $player->hp;
        $player->heal($amount);
        $actualHealing = $player->hp - $oldHp;

        return response()->json([
            'success' => true,
            'message' => "HPが{$actualHealing}回復しました",
            'player' => $player->getStatusSummary(),
            'character' => $player->getStatusSummary(), // 下位互換性のためのalias
        ]);
    }

    public function restoreMp(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'integer|min:1|max:1000'
        ]);

        $player = $this->getOrCreatePlayer();
        $amount = $request->input('amount', 10);
        
        $oldMp = $player->mp;
        $player->restoreMP($amount);
        $actualRestore = $player->mp - $oldMp;

        return response()->json([
            'success' => true,
            'message' => "MPが{$actualRestore}回復しました",
            'player' => $player->getStatusSummary(),
            'character' => $player->getStatusSummary(), // 下位互換性のためのalias
        ]);
    }


    public function takeDamage(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'integer|min:1|max:1000'
        ]);

        $player = $this->getOrCreatePlayer();
        $amount = $request->input('amount', 10);
        
        $oldHp = $player->hp;
        $player->takeDamage($amount);
        $actualDamage = $oldHp - $player->hp;

        $message = "{$actualDamage}のダメージを受けました";
        if (!$player->isAlive()) {
            $message .= "。プレイヤーが倒れました...";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'player' => $player->getStatusSummary(),
            'character' => $player->getStatusSummary(), // 下位互換性のためのalias
            'is_alive' => $player->isAlive(),
        ]);
    }

    public function reset(): JsonResponse
    {
        $player = $this->getOrCreatePlayer();
        
        $player->level = 1;
        $player->base_attack = 10;
        $player->base_defense = 8;
        $player->base_agility = 12;
        $player->base_evasion = 15;
        $player->base_max_hp = 100;
        $player->base_max_sp = 50;
        $player->base_max_mp = 30;
        $player->base_magic_attack = 8;
        $player->base_accuracy = 85;
        
        $player->updateStatsForLevel();
        $player->hp = $player->max_hp;
        $player->sp = $player->max_sp;
        $player->mp = $player->max_mp;
        $player->save();

        return response()->json([
            'success' => true,
            'message' => 'プレイヤーをリセットしました',
            'player' => $player->getDetailedStats(),
            'character' => $player->getDetailedStats(), // 下位互換性のためのalias
        ]);
    }

}