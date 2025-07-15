<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Equipment;
use App\Models\Skill;
use App\Models\ActiveEffect;

class MovementService
{
    public function calculateDiceRoll(int $characterId = null): array
    {
        $baseDiceCount = 2;
        $baseDiceSides = 6;
        $bonusToTotal = 0;
        $extraDice = 0;
        
        if ($characterId) {
            $character = Character::find($characterId);
            if ($character) {
                $equipment = $character->getEquipment();
                $equipmentStats = $equipment->getTotalStats();
                
                $bonusToTotal = $equipmentStats['effects']['dice_bonus'] ?? 0;
                $extraDice = $equipmentStats['effects']['extra_dice'] ?? 0;
            }
        }
        
        $totalDiceCount = $baseDiceCount + $extraDice;
        $diceRolls = [];
        $total = 0;
        
        for ($i = 0; $i < $totalDiceCount; $i++) {
            $roll = rand(1, $baseDiceSides);
            $diceRolls[] = $roll;
            $total += $roll;
        }
        
        $total += $bonusToTotal;
        
        return [
            'dice_rolls' => $diceRolls,
            'dice_count' => $totalDiceCount,
            'base_total' => array_sum($diceRolls),
            'bonus' => $bonusToTotal,
            'total' => $total,
            'effects' => [
                'extra_dice' => $extraDice,
                'dice_bonus' => $bonusToTotal,
            ],
            'rolled_at' => now()->toISOString()
        ];
    }
    
    public function calculateMovementEffects(int $characterId = null): array
    {
        $effects = [
            'dice_bonus' => 0,
            'extra_dice' => 0,
            'movement_multiplier' => 1.0,
            'special_effects' => [],
        ];
        
        if ($characterId) {
            $character = Character::find($characterId);
            if ($character) {
                $equipmentEffects = $this->getEquipmentMovementEffects($character);
                $skillEffects = $this->getSkillMovementEffects($character);
                $itemEffects = $this->getItemMovementEffects($character);
                
                $effects = $this->mergeEffects($effects, $equipmentEffects, $skillEffects, $itemEffects);
            }
        }
        
        return $effects;
    }
    
    private function getEquipmentMovementEffects(Character $character): array
    {
        $equipment = $character->getEquipment();
        $equipmentStats = $equipment->getTotalStats();
        
        return [
            'dice_bonus' => $equipmentStats['effects']['dice_bonus'] ?? 0,
            'extra_dice' => $equipmentStats['effects']['extra_dice'] ?? 0,
            'movement_multiplier' => 1.0,
            'special_effects' => [],
        ];
    }
    
    private function getSkillMovementEffects(Character $character): array
    {
        $skills = Skill::where('character_id', $character->id)
                      ->where('is_active', true)
                      ->get();
        
        $effects = [
            'dice_bonus' => 0,
            'extra_dice' => 0,
            'movement_multiplier' => 1.0,
            'special_effects' => [],
        ];
        
        foreach ($skills as $skill) {
            $skillEffects = $skill->getMovementEffects();
            
            $effects['dice_bonus'] += $skillEffects['dice_bonus'];
            $effects['extra_dice'] += $skillEffects['extra_dice'];
            $effects['movement_multiplier'] += $skillEffects['movement_multiplier'];
            
            if (!empty($skillEffects['special_effects'])) {
                $effects['special_effects'] = array_merge(
                    $effects['special_effects'],
                    $skillEffects['special_effects']
                );
            }
        }
        
        return $effects;
    }
    
    private function getItemMovementEffects(Character $character): array
    {
        $activeEffects = ActiveEffect::where('character_id', $character->id)
                                   ->where('is_active', true)
                                   ->where('remaining_duration', '>', 0)
                                   ->get();
        
        $effects = [
            'dice_bonus' => 0,
            'extra_dice' => 0,
            'movement_multiplier' => 1.0,
            'special_effects' => [],
        ];
        
        foreach ($activeEffects as $activeEffect) {
            $effectData = $activeEffect->getMovementEffects();
            
            $effects['dice_bonus'] += $effectData['dice_bonus'];
            $effects['extra_dice'] += $effectData['extra_dice'];
            $effects['movement_multiplier'] += $effectData['movement_multiplier'];
            
            if (!empty($effectData['special_effects'])) {
                $effects['special_effects'] = array_merge(
                    $effects['special_effects'],
                    $effectData['special_effects']
                );
            }
        }
        
        return $effects;
    }
    
    private function mergeEffects(array ...$effectArrays): array
    {
        $merged = [
            'dice_bonus' => 0,
            'extra_dice' => 0,
            'movement_multiplier' => 1.0,
            'special_effects' => [],
        ];
        
        foreach ($effectArrays as $effects) {
            $merged['dice_bonus'] += $effects['dice_bonus'] ?? 0;
            $merged['extra_dice'] += $effects['extra_dice'] ?? 0;
            $merged['movement_multiplier'] *= $effects['movement_multiplier'] ?? 1.0;
            
            if (!empty($effects['special_effects'])) {
                $merged['special_effects'] = array_merge(
                    $merged['special_effects'],
                    $effects['special_effects']
                );
            }
        }
        
        return $merged;
    }
    
    public function calculateActualMovement(int $baseDiceTotal, int $characterId = null): array
    {
        $movementEffects = $this->calculateMovementEffects($characterId);
        
        $finalMovement = (int) round($baseDiceTotal * $movementEffects['movement_multiplier']);
        
        return [
            'base_movement' => $baseDiceTotal,
            'movement_multiplier' => $movementEffects['movement_multiplier'],
            'final_movement' => $finalMovement,
            'effects_applied' => $movementEffects,
        ];
    }
    
    public function rollDiceWithEffects(int $characterId = null): array
    {
        $diceResult = $this->calculateDiceRoll($characterId);
        $movementResult = $this->calculateActualMovement($diceResult['total'], $characterId);
        
        return [
            'dice' => $diceResult,
            'movement' => $movementResult,
            'final_steps' => $movementResult['final_movement'],
        ];
    }
    
    public function getMovementInfo(int $characterId = null): array
    {
        $effects = $this->calculateMovementEffects($characterId);
        
        $baseDiceCount = 2;
        $totalDiceCount = $baseDiceCount + $effects['extra_dice'];
        
        return [
            'base_dice_count' => $baseDiceCount,
            'extra_dice' => $effects['extra_dice'],
            'total_dice_count' => $totalDiceCount,
            'dice_bonus' => $effects['dice_bonus'],
            'movement_multiplier' => $effects['movement_multiplier'],
            'special_effects' => $effects['special_effects'],
            'min_possible_movement' => (int) round(($totalDiceCount + $effects['dice_bonus']) * $effects['movement_multiplier']),
            'max_possible_movement' => (int) round((($totalDiceCount * 6) + $effects['dice_bonus']) * $effects['movement_multiplier']),
        ];
    }
}