<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteConnection extends Model
{
    protected $table = 'route_connections';
    protected $fillable = [
        'source_location_id',
        'target_location_id',
        'connection_type', // Legacy field - will be removed later
        'position', // Legacy field - will be removed later  
        'direction', // Legacy field - will be removed later
        'source_position',
        'target_position',
        'edge_type',
        'is_enabled',
        'action_label',
        'keyboard_shortcut',
    ];

    protected $casts = [
        'position' => 'integer', // Legacy field
        'source_position' => 'integer',
        'target_position' => 'integer', 
        'is_enabled' => 'boolean',
    ];

    // Relationships
    public function sourceLocation()
    {
        return $this->belongsTo(Route::class, 'source_location_id');
    }

    public function targetLocation()
    {
        return $this->belongsTo(Route::class, 'target_location_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('connection_type', $type);
    }

    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeStartConnections($query)
    {
        return $query->where('connection_type', 'start');
    }

    public function scopeEndConnections($query)
    {
        return $query->where('connection_type', 'end');
    }

    public function scopeTownConnections($query)
    {
        return $query->where('connection_type', 'town_connection');
    }

    public function scopeBranchConnections($query)
    {
        return $query->where('connection_type', 'branch');
    }
    
    // New scopes for enhanced functionality
    public function scopeBySourcePosition($query, $position)
    {
        return $query->where('source_position', $position);
    }
    
    public function scopeByTargetPosition($query, $position)
    {
        return $query->where('target_position', $position);
    }
    
    public function scopeByKeyboardShortcut($query, $shortcut)
    {
        return $query->where('keyboard_shortcut', $shortcut);
    }
    
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
    
    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }
    
    public function scopeByEdgeType($query, $edgeType)
    {
        return $query->where('edge_type', $edgeType);
    }
    
    public function scopeByActionLabel($query, $actionLabel)
    {
        return $query->where('action_label', $actionLabel);
    }
    
    // Helper method to check if connection should be visible at current position
    public function shouldShowAtPosition($currentPosition)
    {
        if ($this->source_position === null) {
            return true; // Town connections are always visible
        }
        
        // Apply position-based visibility rules
        if ($this->source_position === 0) {
            return $currentPosition <= 0;
        }
        
        if ($this->source_position === 100) {
            return $currentPosition >= 100;
        }
        
        return $currentPosition === $this->source_position;
    }
}