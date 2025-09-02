<?php

namespace Tests\Unit\Services\Admin;

use App\Models\DungeonDesc;
use App\Models\Route;
use App\Services\Admin\DungeonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DungeonServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private DungeonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DungeonService::class);
    }

    /** @test */
    public function it_can_search_candidate_floors()
    {
        $dungeon = DungeonDesc::factory()->create();
        
        // 候補フロア（オーファン）を作成
        $orphanFloor = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => null,
            'name' => 'オーファンフロア'
        ]);

        // 他のダンジョンに属するフロア
        $otherDungeon = DungeonDesc::factory()->create();
        $otherFloor = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => $otherDungeon->dungeon_id,
            'name' => '他のフロア'
        ]);

        // 自分のフロア（除外されるべき）
        $ownFloor = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => $dungeon->dungeon_id,
            'name' => '自分のフロア'
        ]);

        // オーファンのみ検索
        $candidates = $this->service->searchCandidateFloors($dungeon->dungeon_id, '', true);
        
        $candidateIds = $candidates->pluck('id')->toArray();
        $this->assertContains($orphanFloor->id, $candidateIds);
        $this->assertNotContains($ownFloor->id, $candidateIds);
        $this->assertNotContains($otherFloor->id, $candidateIds);

        // 他の親に紐づいているフロアも含める
        $candidates = $this->service->searchCandidateFloors($dungeon->dungeon_id, '', false);
        
        $candidateIds = $candidates->pluck('id')->toArray();
        $this->assertContains($orphanFloor->id, $candidateIds);
        $this->assertContains($otherFloor->id, $candidateIds);
        $this->assertNotContains($ownFloor->id, $candidateIds);
    }

    /** @test */
    public function it_can_search_floors_by_name()
    {
        $dungeon = DungeonDesc::factory()->create();
        
        Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => null,
            'name' => 'テストフロア1'
        ]);

        Route::factory()->create([
            'category' => 'dungeon',  
            'dungeon_id' => null,
            'name' => '別のフロア'
        ]);

        $candidates = $this->service->searchCandidateFloors($dungeon->dungeon_id, 'テスト', true);
        
        $this->assertEquals(1, $candidates->count());
        $this->assertEquals('テストフロア1', $candidates->first()->name);
    }

    /** @test */
    public function it_can_attach_floors_to_parent()
    {
        $parent = DungeonDesc::factory()->create();
        
        $floors = Route::factory()->count(3)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        $floorIds = $floors->pluck('id')->toArray();

        $result = $this->service->attachFloorsToParent($parent->dungeon_id, $floorIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['updated_count']);
        $this->assertEquals($parent->id, $result['parent']->id);

        // データベースで確認
        foreach ($floors as $floor) {
            $this->assertDatabaseHas('routes', [
                'id' => $floor->id,
                'dungeon_id' => $parent->dungeon_id
            ]);
        }
    }

    /** @test */
    public function it_handles_invalid_parent_for_floor_attachment()
    {
        $floors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        $result = $this->service->attachFloorsToParent('non_existent_parent', $floors->pluck('id')->toArray());

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_detect_orphan_floors()
    {
        // オーファンフロア（dungeon_id = null）
        $orphanFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        // 親不在フロア（存在しない dungeon_id）
        $missingParentFloors = Route::factory()->count(3)->create([
            'category' => 'dungeon',
            'dungeon_id' => 'non_existent_parent'
        ]);

        // 正常なフロア
        $parent = DungeonDesc::factory()->create();
        $normalFloor = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => $parent->dungeon_id
        ]);

        $result = $this->service->detectOrphanFloors();

        $this->assertEquals(2, $result['orphan_floors']->count());
        $this->assertEquals(3, $result['missing_parent_floors']->count());
        $this->assertEquals(5, $result['total_issues']);

        // オーファンフロアのIDが含まれているかチェック
        $orphanIds = $result['orphan_floors']->pluck('id')->toArray();
        foreach ($orphanFloors as $floor) {
            $this->assertContains($floor->id, $orphanIds);
        }

        // 親不在フロアのIDが含まれているかチェック
        $missingIds = $result['missing_parent_floors']->pluck('id')->toArray();
        foreach ($missingParentFloors as $floor) {
            $this->assertContains($floor->id, $missingIds);
        }
    }

    /** @test */
    public function it_can_attach_orphans_to_existing_parent()
    {
        $parent = DungeonDesc::factory()->create();
        $orphanFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        $floorIds = $orphanFloors->pluck('id')->toArray();

        $result = $this->service->attachOrphansToExistingParent($parent->id, $floorIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['updated_count']);
        $this->assertEquals($parent->id, $result['parent']->id);

        // データベースで確認
        foreach ($orphanFloors as $floor) {
            $this->assertDatabaseHas('routes', [
                'id' => $floor->id,
                'dungeon_id' => $parent->dungeon_id
            ]);
        }
    }

    /** @test */
    public function it_can_create_parent_and_attach_orphans()
    {
        $orphanFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        $parentData = [
            'dungeon_id' => 'new_test_dungeon',
            'dungeon_name' => '新しいテストダンジョン',
            'dungeon_desc' => 'テスト用の説明'
        ];

        $floorIds = $orphanFloors->pluck('id')->toArray();

        $result = $this->service->createParentAndAttachOrphans($parentData, $floorIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['updated_count']);

        // 新しい親が作成されているかチェック
        $this->assertDatabaseHas('dungeons_desc', [
            'dungeon_id' => 'new_test_dungeon',
            'dungeon_name' => '新しいテストダンジョン',
            'is_active' => true
        ]);

        $newParent = DungeonDesc::where('dungeon_id', 'new_test_dungeon')->first();

        // フロアが新しい親に紐づいているかチェック
        foreach ($orphanFloors as $floor) {
            $this->assertDatabaseHas('routes', [
                'id' => $floor->id,
                'dungeon_id' => $newParent->dungeon_id
            ]);
        }
    }

    /** @test */
    public function it_calculates_dungeon_statistics_correctly()
    {
        // アクティブなダンジョンを作成
        $activeDungeons = DungeonDesc::factory()->count(3)->create(['is_active' => true]);
        
        // 非アクティブなダンジョンを作成
        $inactiveDungeon = DungeonDesc::factory()->create(['is_active' => false]);

        // フロアを作成
        $activeDungeons[0]->floors()->createMany(
            Route::factory()->count(2)->make(['category' => 'dungeon', 'dungeon_id' => $activeDungeons[0]->dungeon_id])->toArray()
        );
        $activeDungeons[1]->floors()->createMany(
            Route::factory()->count(3)->make(['category' => 'dungeon', 'dungeon_id' => $activeDungeons[1]->dungeon_id])->toArray()
        );

        // アクティブのみの統計
        $stats = $this->service->getDungeonStatistics(false);

        $this->assertEquals(3, $stats['total_dungeons']);
        $this->assertEquals(3, $stats['active_dungeons']);
        $this->assertEquals(0, $stats['inactive_dungeons']);
        $this->assertEquals(5, $stats['total_floors']); // 2 + 3
        $this->assertEquals(1, $stats['dungeons_with_no_floors']); // 1つのダンジョンはフロアなし

        // 非アクティブも含む統計
        $stats = $this->service->getDungeonStatistics(true);

        $this->assertEquals(4, $stats['total_dungeons']);
        $this->assertEquals(3, $stats['active_dungeons']);
        $this->assertEquals(1, $stats['inactive_dungeons']);
    }

    /** @test */
    public function it_checks_floor_integrity_correctly()
    {
        // オーファンフロアを作成
        $orphanFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        // 親不在フロアを作成
        $missingParentFloors = Route::factory()->count(1)->create([
            'category' => 'dungeon',
            'dungeon_id' => 'non_existent'
        ]);

        // フロアなしダンジョンを作成
        $emptyDungeon = DungeonDesc::factory()->create();

        // 正常なダンジョンとフロア
        $normalDungeon = DungeonDesc::factory()->create();
        Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => $normalDungeon->dungeon_id
        ]);

        $result = $this->service->checkFloorIntegrity();

        $this->assertEquals(3, $result['total_issues']); // orphan, missing_parent, empty_dungeons
        $this->assertEquals('has_issues', $result['status']);

        // 各問題タイプが検出されているかチェック
        $issueTypes = array_column($result['issues'], 'type');
        $this->assertContains('orphan_floors', $issueTypes);
        $this->assertContains('missing_parent_floors', $issueTypes);
        $this->assertContains('empty_dungeons', $issueTypes);

        // 各問題の数が正しいかチェック
        foreach ($result['issues'] as $issue) {
            switch ($issue['type']) {
                case 'orphan_floors':
                    $this->assertEquals(2, $issue['count']);
                    $this->assertEquals('warning', $issue['severity']);
                    break;
                case 'missing_parent_floors':
                    $this->assertEquals(1, $issue['count']);
                    $this->assertEquals('error', $issue['severity']);
                    break;
                case 'empty_dungeons':
                    $this->assertEquals(1, $issue['count']);
                    $this->assertEquals('info', $issue['severity']);
                    break;
            }
        }
    }

    /** @test */
    public function it_returns_healthy_status_when_no_issues()
    {
        // 正常なダンジョンとフロアのみを作成
        $dungeon = DungeonDesc::factory()->create();
        Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => $dungeon->dungeon_id
        ]);

        $result = $this->service->checkFloorIntegrity();

        $this->assertEquals(0, $result['total_issues']);
        $this->assertEquals('healthy', $result['status']);
        $this->assertEmpty($result['issues']);
    }
}