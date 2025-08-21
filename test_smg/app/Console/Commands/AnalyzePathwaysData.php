<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Route;
use App\Models\DungeonDesc;
use App\Models\MonsterSpawnList;

class AnalyzePathwaysData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:analyze-pathways-data {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze current pathways data for migration planning';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Pathways Data Analysis ===');
        $this->newLine();

        // Basic counts
        $this->analyzeBasicCounts();
        $this->newLine();

        // Detailed analysis by category
        $this->analyzeByCategory();
        $this->newLine();

        // Dungeon relationship analysis
        $this->analyzeDungeonRelationships();
        $this->newLine();

        // Data quality analysis
        $this->analyzeDataQuality();
        $this->newLine();

        // Recommendations
        $this->provideRecommendations();

        return Command::SUCCESS;
    }

    private function analyzeBasicCounts()
    {
        $this->info('📊 Basic Data Counts');
        $this->line('─────────────────────');

        $totalLocations = Route::count();
        $totalDungeonDescs = DungeonDesc::count();
        $totalSpawns = MonsterSpawnList::count();

        $categories = Route::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $this->table(
            ['Category', 'Count'],
            collect($categories)->map(function ($count, $category) {
                return [ucfirst($category), $count];
            })->toArray()
        );

        $this->info("Total Routes: {$totalLocations}");
        $this->info("Total DungeonDescs: {$totalDungeonDescs}");
        $this->info("Total MonsterSpawns: {$totalSpawns}");
    }

    private function analyzeByCategory()
    {
        $this->info('📍 Category Analysis');
        $this->line('─────────────────────');

        // Roads analysis
        $this->analyzeRoads();
        $this->newLine();

        // Dungeons analysis
        $this->analyzeDungeons();
        $this->newLine();

        // Towns analysis
        $this->analyzeTowns();
    }

    private function analyzeRoads()
    {
        $this->info('🛣️  Roads Analysis');
        
        $roads = Route::roads()->get();
        
        if ($roads->isEmpty()) {
            $this->warn('No roads found');
            return;
        }

        $stats = [
            'total' => $roads->count(),
            'active' => $roads->where('is_active', true)->count(),
            'with_spawns' => $roads->filter(fn($road) => $road->hasMonsterSpawns())->count(),
            'avg_length' => round($roads->avg('length') ?? 0, 1),
            'avg_encounter_rate' => round(($roads->avg('encounter_rate') ?? 0) * 100, 1),
        ];

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Roads', $stats['total']],
                ['Active Roads', $stats['active']],
                ['With Monster Spawns', $stats['with_spawns']],
                ['Average Length', $stats['avg_length']],
                ['Average Encounter Rate', $stats['avg_encounter_rate'] . '%'],
            ]
        );

        // Difficulty distribution
        $difficulties = $roads->groupBy('difficulty')
            ->map(fn($group) => $group->count())
            ->toArray();

        if (!empty($difficulties)) {
            $this->info('Difficulty Distribution:');
            foreach ($difficulties as $difficulty => $count) {
                $this->line("  • {$difficulty}: {$count}");
            }
        }
    }

    private function analyzeDungeons()
    {
        $this->info('🏴‍☠️ Dungeons Analysis');
        
        $dungeons = Route::dungeons()->get();
        
        if ($dungeons->isEmpty()) {
            $this->warn('No dungeons found');
            return;
        }

        $stats = [
            'total' => $dungeons->count(),
            'active' => $dungeons->where('is_active', true)->count(),
            'with_dungeon_id' => $dungeons->whereNotNull('dungeon_id')->count(),
            'with_spawns' => $dungeons->filter(fn($dungeon) => $dungeon->hasMonsterSpawns())->count(),
            'with_floors' => $dungeons->whereNotNull('floors')->count(),
        ];

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Dungeons', $stats['total']],
                ['Active Dungeons', $stats['active']],
                ['With Dungeon ID', $stats['with_dungeon_id']],
                ['With Monster Spawns', $stats['with_spawns']],
                ['With Floor Data', $stats['with_floors']],
            ]
        );

        // Show individual dungeons
        $this->info('Individual Dungeon Details:');
        foreach ($dungeons->take(10) as $dungeon) {
            $this->line("  • {$dungeon->id}: {$dungeon->name} (dungeon_id: " . ($dungeon->dungeon_id ?? 'null') . ")");
        }
    }

    private function analyzeTowns()
    {
        $this->info('🏘️  Towns Analysis');
        
        $towns = Route::towns()->get();
        
        if ($towns->isEmpty()) {
            $this->warn('No towns found');
            return;
        }

        $stats = [
            'total' => $towns->count(),
            'active' => $towns->where('is_active', true)->count(),
            'with_services' => $towns->whereNotNull('services')->count(),
        ];

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Towns', $stats['total']],
                ['Active Towns', $stats['active']],
                ['With Services', $stats['with_services']],
            ]
        );
    }

    private function analyzeDungeonRelationships()
    {
        $this->info('🔗 Dungeon Relationships Analysis');
        $this->line('─────────────────────────────────');

        $dungeonDescs = DungeonDesc::all();
        $dungeonLocations = Route::dungeons()->get();

        $this->info("DungeonDesc entries: {$dungeonDescs->count()}");
        $this->info("Dungeon Routes: {$dungeonLocations->count()}");

        if ($dungeonDescs->isNotEmpty()) {
            $this->info('DungeonDesc Details:');
            foreach ($dungeonDescs as $desc) {
                $associatedFloors = Route::where('dungeon_id', $desc->dungeon_id)->count();
                $this->line("  • {$desc->dungeon_id}: {$desc->dungeon_name} ({$associatedFloors} floors)");
            }
        }

        // Orphaned dungeons (without dungeon_id)
        $orphanedDungeons = $dungeonLocations->whereNull('dungeon_id');
        if ($orphanedDungeons->isNotEmpty()) {
            $this->warn("Found {$orphanedDungeons->count()} orphaned dungeon locations:");
            foreach ($orphanedDungeons->take(5) as $dungeon) {
                $this->line("  • {$dungeon->id}: {$dungeon->name}");
            }
        }
    }

    private function analyzeDataQuality()
    {
        $this->info('🔍 Data Quality Analysis');
        $this->line('─────────────────────────');

        $issues = [];

        // Check for missing required fields
        $locationsWithoutName = Route::whereNull('name')->orWhere('name', '')->count();
        if ($locationsWithoutName > 0) {
            $issues[] = "Locations without name: {$locationsWithoutName}";
        }

        $locationsWithoutCategory = Route::whereNull('category')->orWhere('category', '')->count();
        if ($locationsWithoutCategory > 0) {
            $issues[] = "Locations without category: {$locationsWithoutCategory}";
        }

        // Check for inconsistent data
        $dungeonsWithoutFloors = Route::dungeons()->whereNull('floors')->count();
        if ($dungeonsWithoutFloors > 0) {
            $issues[] = "Dungeons without floor data: {$dungeonsWithoutFloors}";
        }

        $roadsWithoutLength = Route::roads()->whereNull('length')->count();
        if ($roadsWithoutLength > 0) {
            $issues[] = "Roads without length: {$roadsWithoutLength}";
        }

        // Check for orphaned data
        $orphanedDungeons = Route::dungeons()->whereNull('dungeon_id')->count();
        if ($orphanedDungeons > 0) {
            $issues[] = "Dungeon locations without dungeon_id: {$orphanedDungeons}";
        }

        if (empty($issues)) {
            $this->info('✅ No major data quality issues found');
        } else {
            $this->warn('❌ Data quality issues found:');
            foreach ($issues as $issue) {
                $this->line("  • {$issue}");
            }
        }
    }

    private function provideRecommendations()
    {
        $this->info('💡 Migration Recommendations');
        $this->line('─────────────────────────────');

        $recommendations = [];

        // Check orphaned dungeons
        $orphanedDungeons = Route::dungeons()->whereNull('dungeon_id')->count();
        if ($orphanedDungeons > 0) {
            $recommendations[] = "Create DungeonDesc entries for {$orphanedDungeons} orphaned dungeons";
            $recommendations[] = "Update dungeon_id fields to establish proper relationships";
        }

        // Check spawn coverage
        $locationsWithoutSpawns = Route::whereDoesntHave('monsterSpawns')->count();
        if ($locationsWithoutSpawns > 0) {
            $recommendations[] = "Consider adding monster spawns to {$locationsWithoutSpawns} locations";
        }

        // General recommendations
        $recommendations[] = "Create backup before any migration";
        $recommendations[] = "Test migration on development environment first";
        $recommendations[] = "Use transaction-based migration for data safety";

        if (empty($recommendations)) {
            $this->info('✅ Data appears ready for new system');
        } else {
            $this->warn('📋 Recommended actions:');
            foreach ($recommendations as $i => $recommendation) {
                $this->line("  " . ($i + 1) . ". {$recommendation}");
            }
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Review the analysis above');
        $this->line('2. Create data migration plan based on findings');
        $this->line('3. Implement migration scripts with proper testing');
        $this->line('4. Validate migrated data before production deployment');
    }
}