<?php

namespace Tests\Feature\Admin;

use App\Models\DungeonDesc;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminDungeonControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $adminUser;
    private User $readOnlyUser;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーを作成
        $this->adminUser = User::factory()->create();
        $this->readOnlyUser = User::factory()->create();

        // 権限を設定（実際の権限システムに応じて調整）
        $this->adminUser->givePermissionTo(['locations.view', 'locations.edit', 'locations.delete']);
        $this->readOnlyUser->givePermissionTo(['locations.view']);
    }

    /** @test */
    public function it_displays_dungeon_index_page()
    {
        // テストデータ作成
        $dungeons = DungeonDesc::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.dungeons.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dungeons.index');
        $response->assertViewHas('dungeons');
        
        // ダンジョン名が表示されているかチェック
        foreach ($dungeons as $dungeon) {
            $response->assertSee($dungeon->dungeon_name);
        }
    }

    /** @test */
    public function it_can_search_dungeons()
    {
        // テストデータ作成
        $dungeon1 = DungeonDesc::factory()->create(['dungeon_name' => 'テストダンジョン1']);
        $dungeon2 = DungeonDesc::factory()->create(['dungeon_name' => '別のダンジョン']);

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.dungeons.index', ['search' => 'テスト']));

        $response->assertStatus(200);
        $response->assertSee($dungeon1->dungeon_name);
        $response->assertDontSee($dungeon2->dungeon_name);
    }

    /** @test */
    public function it_can_show_inactive_dungeons_when_toggled()
    {
        // アクティブ・非アクティブのダンジョンを作成
        $activeDungeon = DungeonDesc::factory()->create(['is_active' => true, 'dungeon_name' => 'アクティブダンジョン']);
        $inactiveDungeon = DungeonDesc::factory()->create(['is_active' => false, 'dungeon_name' => '非アクティブダンジョン']);

        // デフォルト（アクティブのみ）
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.dungeons.index'));
        
        $response->assertSee($activeDungeon->dungeon_name);
        $response->assertDontSee($inactiveDungeon->dungeon_name);

        // 非アクティブを含める
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.dungeons.index', ['include_inactive' => true]));
        
        $response->assertSee($activeDungeon->dungeon_name);
        $response->assertSee($inactiveDungeon->dungeon_name);
    }

    /** @test */
    public function it_displays_dungeon_details_page()
    {
        $dungeon = DungeonDesc::factory()->create();

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.dungeons.show', $dungeon->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dungeons.show');
        $response->assertViewHas('dungeon');
        $response->assertSee($dungeon->dungeon_name);
    }

    /** @test */
    public function it_can_create_a_new_dungeon()
    {
        $dungeonData = [
            'dungeon_id' => 'test_dungeon_001',
            'dungeon_name' => 'テストダンジョン',
            'dungeon_desc' => 'これはテスト用のダンジョンです。'
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.dungeons.store'), $dungeonData);

        $response->assertStatus(302); // リダイレクト
        $this->assertDatabaseHas('dungeons_desc', $dungeonData);
    }

    /** @test */
    public function it_can_update_dungeon()
    {
        $dungeon = DungeonDesc::factory()->create();
        $updatedData = [
            'dungeon_name' => '更新されたダンジョン名',
            'dungeon_desc' => '更新された説明',
            'is_active' => false
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put(route('admin.dungeons.update', $dungeon->id), $updatedData);

        $response->assertStatus(302); // リダイレクト
        $this->assertDatabaseHas('dungeons_desc', [
            'id' => $dungeon->id,
            'dungeon_name' => '更新されたダンジョン名',
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_delete_dungeon_and_detach_floors()
    {
        $dungeon = DungeonDesc::factory()->create();
        
        // 関連するフロアを作成
        $floors = Route::factory()->count(3)->create([
            'category' => 'dungeon',
            'dungeon_id' => $dungeon->dungeon_id
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->delete(route('admin.dungeons.destroy', $dungeon->id));

        $response->assertStatus(302); // リダイレクト
        
        // ダンジョンが削除されているかチェック
        $this->assertDatabaseMissing('dungeons_desc', ['id' => $dungeon->id]);
        
        // フロアは削除されず、dungeon_idがnullになっているかチェック
        foreach ($floors as $floor) {
            $this->assertDatabaseHas('routes', [
                'id' => $floor->id,
                'dungeon_id' => null
            ]);
        }
    }

    /** @test */
    public function it_can_create_floor_for_dungeon()
    {
        $dungeon = DungeonDesc::factory()->create();
        $floorData = [
            'id' => 'test_floor_001',
            'name' => 'テストフロア',
            'description' => 'テスト用のフロア',
            'length' => 100,
            'difficulty' => 'normal',
            'encounter_rate' => 0.3
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.dungeons.floors.store', $dungeon->id), $floorData);

        $response->assertStatus(302); // リダイレクト
        
        $this->assertDatabaseHas('routes', [
            'id' => 'test_floor_001',
            'name' => 'テストフロア',
            'category' => 'dungeon',
            'dungeon_id' => $dungeon->dungeon_id
        ]);
    }

    /** @test */
    public function it_can_attach_orphan_floors_to_dungeon()
    {
        $dungeon = DungeonDesc::factory()->create();
        
        // オーファンフロア（dungeon_id = null）を作成
        $orphanFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        $floorIds = $orphanFloors->pluck('id')->toArray();

        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('admin.dungeons.attach-floors', $dungeon->id), [
                             'floor_ids' => $floorIds
                         ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // フロアがダンジョンに紐づいているかチェック
        foreach ($orphanFloors as $floor) {
            $this->assertDatabaseHas('routes', [
                'id' => $floor->id,
                'dungeon_id' => $dungeon->dungeon_id
            ]);
        }
    }

    /** @test */
    public function it_displays_orphan_floors_page()
    {
        // オーファンフロアを作成
        $orphanFloor = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => null,
            'name' => 'オーファンフロア'
        ]);

        // 親不在フロアを作成（存在しない dungeon_id を参照）
        $missingParentFloor = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => 'non_existent_dungeon'
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.dungeons.orphans'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dungeons.orphans');
        $response->assertSee($orphanFloor->name);
    }

    /** @test */
    public function it_can_attach_orphans_to_existing_parent()
    {
        $dungeon = DungeonDesc::factory()->create();
        $orphanFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.dungeons.process-orphans'), [
                             'action' => 'attach_to_existing',
                             'target_dungeon_id' => $dungeon->id,
                             'floor_ids' => $orphanFloors->pluck('id')->toArray()
                         ]);

        $response->assertStatus(302); // リダイレクト
        $response->assertSessionHas('success');

        // フロアがダンジョンに紐づいているかチェック
        foreach ($orphanFloors as $floor) {
            $this->assertDatabaseHas('routes', [
                'id' => $floor->id,
                'dungeon_id' => $dungeon->dungeon_id
            ]);
        }
    }

    /** @test */
    public function it_can_create_new_parent_and_attach_orphans()
    {
        $orphanFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        $newDungeonData = [
            'new_dungeon_id' => 'new_test_dungeon',
            'new_dungeon_name' => '新しいテストダンジョン',
            'new_dungeon_desc' => '新規作成されたダンジョン'
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.dungeons.process-orphans'), array_merge([
                             'action' => 'create_new_parent',
                             'floor_ids' => $orphanFloors->pluck('id')->toArray()
                         ], $newDungeonData));

        $response->assertStatus(302); // リダイレクト
        $response->assertSessionHas('success');

        // 新しいダンジョンが作成されているかチェック
        $this->assertDatabaseHas('dungeons_desc', [
            'dungeon_id' => 'new_test_dungeon',
            'dungeon_name' => '新しいテストダンジョン'
        ]);

        $newDungeon = DungeonDesc::where('dungeon_id', 'new_test_dungeon')->first();

        // フロアが新しいダンジョンに紐づいているかチェック
        foreach ($orphanFloors as $floor) {
            $this->assertDatabaseHas('routes', [
                'id' => $floor->id,
                'dungeon_id' => $newDungeon->dungeon_id
            ]);
        }
    }

    /** @test */
    public function it_denies_access_without_proper_permissions()
    {
        $dungeon = DungeonDesc::factory()->create();

        // locations.view 権限がない場合
        $noPermUser = User::factory()->create();
        
        $response = $this->actingAs($noPermUser)
                         ->get(route('admin.dungeons.index'));
        
        $response->assertStatus(403); // Forbidden

        // locations.edit 権限がない場合
        $response = $this->actingAs($this->readOnlyUser)
                         ->post(route('admin.dungeons.store'), [
                             'dungeon_id' => 'test',
                             'dungeon_name' => 'test'
                         ]);
        
        $response->assertStatus(403); // Forbidden

        // locations.delete 権限がない場合
        $response = $this->actingAs($this->readOnlyUser)
                         ->delete(route('admin.dungeons.destroy', $dungeon->id));
        
        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function it_validates_required_fields_for_dungeon_creation()
    {
        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.dungeons.store'), []);

        $response->assertStatus(302); // バリデーションエラーでリダイレクト
        $response->assertSessionHasErrors(['dungeon_id', 'dungeon_name']);
    }

    /** @test */
    public function it_validates_floor_attachment_request()
    {
        $dungeon = DungeonDesc::factory()->create();

        // 空の floor_ids
        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('admin.dungeons.attach-floors', $dungeon->id), [
                             'floor_ids' => []
                         ]);

        $response->assertStatus(422); // バリデーションエラー
        $response->assertJsonValidationErrors(['floor_ids']);

        // 存在しない floor_ids
        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('admin.dungeons.attach-floors', $dungeon->id), [
                             'floor_ids' => ['non_existent_floor']
                         ]);

        $response->assertStatus(422); // バリデーションエラー
        $response->assertJsonValidationErrors(['floor_ids.0']);
    }

    /** @test */
    public function it_handles_404_for_non_existent_dungeon()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.dungeons.show', 99999));

        $response->assertStatus(302); // リダイレクト
        $response->assertSessionHas('error');
    }
}