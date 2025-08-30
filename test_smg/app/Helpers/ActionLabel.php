<?php

namespace App\Helpers;

class ActionLabel
{
    /**
     * Get Japanese text for action label
     *
     * @param string|null $actionLabel
     * @param string|null $targetLocationName
     * @return string
     */
    public static function getActionLabelText(?string $actionLabel, ?string $targetLocationName = null): string
    {
        if (!$actionLabel) {
            return $targetLocationName ? "{$targetLocationName}に移動する" : '移動する';
        }

        return match($actionLabel) {
            'turn_right' => '右折する',
            'turn_left' => '左折する',
            'move_north' => '北に移動する',
            'move_south' => '南に移動する',
            'move_west' => '西に移動する',
            'move_east' => '東に移動する',
            'enter_dungeon' => $targetLocationName ? "{$targetLocationName}に入る" : 'ダンジョンに入る',
            'exit_dungeon' => $targetLocationName ? "{$targetLocationName}から出る" : 'ダンジョンから出る',
            default => $targetLocationName ? "{$targetLocationName}に移動する" : '移動する'
        };
    }
    
    /**
     * Get keyboard shortcut display text
     *
     * @param string|null $keyboardShortcut
     * @return string|null
     */
    public static function getKeyboardShortcutDisplay(?string $keyboardShortcut): ?string
    {
        if (!$keyboardShortcut) {
            return null;
        }

        return match($keyboardShortcut) {
            'up' => '↑',
            'down' => '↓',
            'left' => '←',
            'right' => '→',
            default => strtoupper($keyboardShortcut)
        };
    }
    
    /**
     * Get all available action labels
     *
     * @return array
     */
    public static function getAllActionLabels(): array
    {
        return [
            'turn_right' => '右折する',
            'turn_left' => '左折する',
            'move_north' => '北に移動する',
            'move_south' => '南に移動する',
            'move_west' => '西に移動する',
            'move_east' => '東に移動する',
            'enter_dungeon' => 'ダンジョンに入る',
            'exit_dungeon' => 'ダンジョンから出る'
        ];
    }
    
    /**
     * Get all available keyboard shortcuts
     *
     * @return array
     */
    public static function getAllKeyboardShortcuts(): array
    {
        return [
            'up' => '↑ (上)',
            'down' => '↓ (下)',
            'left' => '← (左)',
            'right' => '→ (右)'
        ];
    }
    
    /**
     * Get suggested action label based on direction
     *
     * @param string|null $direction
     * @param string|null $targetCategory
     * @return string|null
     */
    public static function getSuggestedActionLabel(?string $direction, ?string $targetCategory = null): ?string
    {
        // Handle dungeon targets
        if ($targetCategory === 'dungeon') {
            return 'enter_dungeon';
        }
        
        // Handle direction-based suggestions
        if (!$direction) {
            return null;
        }
        
        $directionMappings = [
            '北' => 'move_north',
            '南' => 'move_south',
            '東' => 'move_east',
            '西' => 'move_west',
            'north' => 'move_north',
            'south' => 'move_south',
            'east' => 'move_east',
            'west' => 'move_west'
        ];
        
        return $directionMappings[$direction] ?? null;
    }
    
    /**
     * Get suggested keyboard shortcut based on action label
     *
     * @param string|null $actionLabel
     * @return string|null
     */
    public static function getSuggestedKeyboardShortcut(?string $actionLabel): ?string
    {
        if (!$actionLabel) {
            return null;
        }
        
        $shortcutMappings = [
            'move_north' => 'up',
            'move_south' => 'down',
            'move_east' => 'right',
            'move_west' => 'left',
            'turn_right' => 'right',
            'turn_left' => 'left'
        ];
        
        return $shortcutMappings[$actionLabel] ?? null;
    }
    
    /**
     * Validate action label
     *
     * @param string $actionLabel
     * @return bool
     */
    public static function isValidActionLabel(string $actionLabel): bool
    {
        $validLabels = [
            'turn_right', 'turn_left',
            'move_north', 'move_south', 'move_west', 'move_east',
            'enter_dungeon', 'exit_dungeon'
        ];
        
        return in_array($actionLabel, $validLabels, true);
    }
    
    /**
     * Validate keyboard shortcut
     *
     * @param string $shortcut
     * @return bool
     */
    public static function isValidKeyboardShortcut(string $shortcut): bool
    {
        $validShortcuts = ['up', 'down', 'left', 'right'];
        
        return in_array($shortcut, $validShortcuts, true);
    }
    
    /**
     * Get opposite action label for bidirectional connections
     *
     * @param string|null $actionLabel
     * @return string|null
     */
    public static function getOppositeActionLabel(?string $actionLabel): ?string
    {
        if (!$actionLabel) {
            return null;
        }
        
        $oppositeMapping = [
            'turn_right' => 'turn_left',
            'turn_left' => 'turn_right',
            'move_north' => 'move_south',
            'move_south' => 'move_north',
            'move_west' => 'move_east',
            'move_east' => 'move_west',
            'enter_dungeon' => 'exit_dungeon',
            'exit_dungeon' => 'enter_dungeon'
        ];
        
        return $oppositeMapping[$actionLabel] ?? null;
    }
    
    /**
     * Get opposite keyboard shortcut for bidirectional connections
     *
     * @param string|null $keyboardShortcut
     * @return string|null
     */
    public static function getOppositeKeyboardShortcut(?string $keyboardShortcut): ?string
    {
        if (!$keyboardShortcut) {
            return null;
        }
        
        $oppositeMapping = [
            'up' => 'down',
            'down' => 'up',
            'left' => 'right',
            'right' => 'left'
        ];
        
        return $oppositeMapping[$keyboardShortcut] ?? null;
    }
    
    /**
     * Get opposite edge type for bidirectional connections
     *
     * @param string|null $edgeType
     * @return string|null
     */
    public static function getOppositeEdgeType(?string $edgeType): ?string
    {
        if (!$edgeType) {
            return null;
        }
        
        $oppositeMapping = [
            'exit' => 'enter',
            'enter' => 'exit',
            'normal' => 'normal',
            'branch' => 'branch',
            'portal' => 'portal'
        ];
        
        return $oppositeMapping[$edgeType] ?? $edgeType;
    }
}