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
        $character = (object) DummyDataService::getCharacter(1);
        $stats = DummyDataService::getCharacterDetailedStats(1);
        $summary = DummyDataService::getCharacterStatusSummary(1);
        
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

    public function gainExperience(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'integer|min:1|max:10000'
        ]);

        $character = $this->getOrCreateCharacter();
        $amount = $request->input('amount', 50);
        
        $character->gainExperience($amount);
        
        $message = "経験値を{$amount}獲得しました";

        return response()->json([
            'success' => true,
            'message' => $message,
            'character' => $character->getDetailedStats(),
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
        
        $character->experience = 0;
        $character->attack = 10;
        $character->defense = 8;
        $character->agility = 12;
        $character->evasion = 15;
        $character->hp = 100;
        $character->max_hp = 100;
        $character->mp = 50;
        $character->max_mp = 50;
        $character->accuracy = 85;

        return response()->json([
            'success' => true,
            'message' => 'キャラクターをリセットしました',
            'character' => $character->getDetailedStats(),
        ]);
    }

    private function getOrCreateCharacter(): Character
    {
        return Character::createNewCharacter('冒険者');
    }
}