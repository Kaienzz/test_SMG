<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RouteConnection;
use App\Models\Route;

class RouteConnectionDataMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting RouteConnection data migration...');
        
        // Get all existing connections
        $connections = DB::table('route_connections')->get();
        
        $this->command->info("Found {$connections->count()} existing connections to migrate");
        
        // Create backup before migration
        $this->createBackup();
        
        // Process each connection
        foreach ($connections as $connection) {
            $this->migrateConnection($connection);
        }
        
        $this->command->info('Data migration completed successfully!');
    }
    
    private function createBackup()
    {
        $timestamp = now()->format('YmdHis');
        $backupTable = "route_connections_backup_{$timestamp}";
        
        DB::statement("CREATE TABLE {$backupTable} AS SELECT * FROM route_connections");
        $this->command->info("Backup created: {$backupTable}");
    }
    
    private function migrateConnection($connection)
    {
        // Direction to action_label mapping
        $actionLabelMapping = [
            '北' => 'move_north',
            '南' => 'move_south', 
            '東' => 'move_east',
            '西' => 'move_west',
            'north' => 'move_north',
            'south' => 'move_south',
            'east' => 'move_east', 
            'west' => 'move_west'
        ];
        
        // Get location categories for source and target
        $sourceLocation = Route::find($connection->source_location_id);
        $targetLocation = Route::find($connection->target_location_id);
        
        if (!$sourceLocation || !$targetLocation) {
            $this->command->warn("Skipping connection {$connection->id}: Missing location data");
            return;
        }
        
        // Determine source_position and target_position
        $sourcePosition = $this->getSourcePosition($connection, $sourceLocation);
        $targetPosition = $this->getTargetPosition($connection, $targetLocation);
        
        // Determine action_label
        $actionLabel = $this->getActionLabel($connection, $actionLabelMapping, $targetLocation);
        
        if ($connection->connection_type === 'bidirectional') {
            // Split bidirectional into two separate connections
            $this->createUnidirectionalConnections($connection, $sourcePosition, $targetPosition, $actionLabel, $sourceLocation, $targetLocation);
        } else {
            // Update existing connection
            $this->updateConnection($connection->id, $sourcePosition, $targetPosition, $actionLabel);
        }
    }
    
    private function getSourcePosition($connection, $sourceLocation)
    {
        if ($sourceLocation->category === 'town') {
            return null; // Towns don't have positions
        }
        
        // For roads/dungeons, use existing position or default to appropriate value
        return $connection->position ?? ($connection->connection_type === 'start' ? 0 : 100);
    }
    
    private function getTargetPosition($connection, $targetLocation)
    {
        if ($targetLocation->category === 'town') {
            return null; // Towns don't have positions
        }
        
        // For roads/dungeons, determine entry/exit point
        return $connection->connection_type === 'start' ? 0 : 100;
    }
    
    private function getActionLabel($connection, $mapping, $targetLocation)
    {
        // Check if direction maps to a basic movement
        if ($connection->direction && isset($mapping[$connection->direction])) {
            return $mapping[$connection->direction];
        }
        
        // Determine based on target category
        if ($targetLocation->category === 'dungeon') {
            return 'enter_dungeon';
        }
        
        // Default to null (will use default text)
        return null;
    }
    
    private function createUnidirectionalConnections($connection, $sourcePosition, $targetPosition, $actionLabel, $sourceLocation, $targetLocation)
    {
        // Create A -> B connection
        $this->updateConnection($connection->id, $sourcePosition, $targetPosition, $actionLabel);
        
        // Create B -> A connection  
        $reverseActionLabel = $this->getReverseActionLabel($actionLabel, $sourceLocation);
        $reverseSourcePos = $targetPosition;
        $reverseTargetPos = $sourcePosition;
        
        DB::table('route_connections')->insert([
            'source_location_id' => $connection->target_location_id,
            'target_location_id' => $connection->source_location_id,
            'connection_type' => 'bidirectional_reverse', // Temporary marker
            'position' => $connection->position,
            'direction' => $connection->direction,
            'source_position' => $reverseSourcePos,
            'target_position' => $reverseTargetPos,
            'action_label' => $reverseActionLabel,
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->command->info("Split bidirectional connection {$connection->id} into two unidirectional connections");
    }
    
    private function getReverseActionLabel($actionLabel, $sourceLocation)
    {
        $reverseMapping = [
            'move_north' => 'move_south',
            'move_south' => 'move_north',
            'move_east' => 'move_west', 
            'move_west' => 'move_east',
            'enter_dungeon' => 'exit_dungeon'
        ];
        
        if ($actionLabel && isset($reverseMapping[$actionLabel])) {
            return $reverseMapping[$actionLabel];
        }
        
        if ($sourceLocation->category === 'dungeon') {
            return 'exit_dungeon';
        }
        
        return null;
    }
    
    private function updateConnection($id, $sourcePosition, $targetPosition, $actionLabel)
    {
        DB::table('route_connections')
            ->where('id', $id)
            ->update([
                'source_position' => $sourcePosition,
                'target_position' => $targetPosition,
                'action_label' => $actionLabel,
                'is_enabled' => true,
                'updated_at' => now()
            ]);
    }
}
