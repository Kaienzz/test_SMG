<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Application\Services\BattleStateManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BattleController extends Controller
{
    use \App\Http\Controllers\Traits\HasCharacter;
    
    public function __construct(
        private readonly BattleStateManager $battleStateManager
    ) {}
    
    public function index(): View|RedirectResponse
    {
        $userId = Auth::id();
        
        // セッションから戦闘データを移行（下位互換性のため）
        $this->battleStateManager->migrateBattleSessionToDatabase($userId);
        
        $battleData = $this->battleStateManager->getActiveBattleData($userId);
        
        if (!$battleData) {
            return redirect()->route('game.index');
        }
        
        return view('battle.index', $battleData);
    }

    public function startBattle(Request $request): JsonResponse
    {
        $monster = $request->input('monster');
        $character = $this->getOrCreateCharacter();
        
        $battleResult = $this->battleStateManager->startBattle($character, $monster);
        
        return response()->json($battleResult->toArray());
    }

    public function attack(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $battleResult = $this->battleStateManager->processAttack($userId);
        
        return response()->json($battleResult->toArray());
    }

    public function defend(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $battleResult = $this->battleStateManager->processDefense($userId);
        
        return response()->json($battleResult->toArray());
    }

    public function escape(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $battleResult = $this->battleStateManager->processEscape($userId);
        
        return response()->json($battleResult->toArray());
    }
    
    public function useSkill(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $battleResult = $this->battleStateManager->processSkillUse($userId, $request);
        
        return response()->json($battleResult->toArray());
    }
    
    public function endBattle(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $battleResult = $this->battleStateManager->endBattle($userId);
        
        return response()->json($battleResult->toArray());
    }
}