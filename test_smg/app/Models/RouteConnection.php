<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteConnection extends Model
{
    protected $table = 'route_connections';
    protected $fillable = [
        'source_location_id',
        'target_location_id',
        'connection_type',
        'position',
        'direction',
    ];

    protected $casts = [
        'position' => 'integer',
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
}