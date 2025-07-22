<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Services\DummyDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function index(): View
    {
        $character = $this->getOrCreateCharacter();
        $character->updateCharacterLevel();
        $stats = $character->getDetailedStatsWithLevel();
        $summary = $character->getStatusSummary();
        
        return view('character.index', [
            'character' => $character,
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

        $character = Character::createNewCharacter($request->input('name'));
        
        return response()->json([
            'success' => true,
            'character' => $character->getDetailedStats(),
            'message' => 'キャラクターが作成されました！'
        ]);
    }

    public function show(): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        
        return response()->json([
            'character' => $character->getDetailedStats(),
            'summary' => $character->getStatusSummary(),
        ]);
    }

    public function heal(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'integer|min:1|max:1000'
        ]);

        $character = $this->getOrCreateCharacter();
        $amount = $request->input('amount', 10);
        
        $oldHp = $character->hp;
        $character->heal($amount);
        $actualHealing = $character->hp - $oldHp;

        return response()->json([
            'success' => true,
            'message' => "HPが{$actualHealing}回復しました",
            'character' => $character->getStatusSummary(),
        ]);
    }

    public function restoreMp(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'integer|min:1|max:1000'
        ]);

        $character = $this->getOrCreateCharacter();
        $amount = $request->input('amount', 10);
        
        $oldMp = $character->mp;
        $character->restoreMP($amount);
        $actualRestore = $character->mp - $oldMp;

        return response()->json([
            'success' => true,
            'message' => "MPが{$actualRestore}回復しました",
            'character' => $character->getStatusSummary(),
        ]);
    }


    public function takeDamage(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'integer|min:1|max:1000'
        ]);

        $character = $this->getOrCreateCharacter();
        $amount = $request->input('amount', 10);
        
        $oldHp = $character->hp;
        $character->takeDamage($amount);
        $actualDamage = $oldHp - $character->hp;

        $message = "{$actualDamage}のダメージを受けました";
        if (!$character->isAlive()) {
            $message .= "。キャラクターが倒れました...";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'character' => $character->getStatusSummary(),
            'is_alive' => $character->isAlive(),
        ]);
    }

    public function reset(): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        
        $character->level = 1;
        $character->base_attack = 10;
        $character->base_defense = 8;
        $character->base_agility = 12;
        $character->base_evasion = 15;
        $character->base_max_hp = 100;
        $character->base_max_sp = 50;
        $character->base_max_mp = 30;
        $character->base_magic_attack = 8;
        $character->base_accuracy = 85;
        
        $character->updateStatsForLevel();
        $character->hp = $character->max_hp;
        $character->sp = $character->max_sp;
        $character->mp = $character->max_mp;
        $character->save();

        return response()->json([
            'success' => true,
            'message' => 'キャラクターをリセットしました',
            'character' => $character->getDetailedStats(),
        ]);
    }

    private function getOrCreateCharacter(): Character
    {
        $character = Character::first();
        if (!$character) {
            $character = Character::createNewCharacter('冒険者');
            $character->save();
        }
        return $character;
    }
}