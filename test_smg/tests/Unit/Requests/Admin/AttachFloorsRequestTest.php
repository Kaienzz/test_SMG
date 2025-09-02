<?php

namespace Tests\Unit\Requests\Admin;

use App\Http\Requests\Admin\AttachFloorsRequest;
use App\Models\DungeonDesc;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AttachFloorsRequestTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private AttachFloorsRequest $request;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['locations.edit']);
        $this->actingAs($this->user);

        $this->request = new AttachFloorsRequest();
    }

    /** @test */
    public function it_authorizes_user_with_locations_edit_permission()
    {
        $this->assertTrue($this->request->authorize());
    }

    /** @test */
    public function it_denies_user_without_locations_edit_permission()
    {
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);

        $request = new AttachFloorsRequest();
        
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function it_validates_required_floor_ids()
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('floor_ids'));
    }

    /** @test */
    public function it_validates_floor_ids_as_array()
    {
        $validator = Validator::make([
            'floor_ids' => 'not_an_array'
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('floor_ids'));
    }

    /** @test */
    public function it_validates_minimum_floor_ids_count()
    {
        $validator = Validator::make([
            'floor_ids' => []
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('floor_ids'));
    }

    /** @test */
    public function it_validates_floor_ids_existence()
    {
        $validator = Validator::make([
            'floor_ids' => ['non_existent_floor_id']
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('floor_ids.0'));
    }

    /** @test */
    public function it_validates_floor_ids_are_dungeon_category()
    {
        // ダンジョンカテゴリーではないルートを作成
        $nonDungeonRoute = Route::factory()->create([
            'category' => 'town' // ダンジョン以外
        ]);

        $validator = Validator::make([
            'floor_ids' => [$nonDungeonRoute->id]
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('floor_ids.0'));
    }

    /** @test */
    public function it_passes_validation_with_valid_dungeon_floor_ids()
    {
        $dungeonFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon'
        ]);

        $validator = Validator::make([
            'floor_ids' => $dungeonFloors->pluck('id')->toArray()
        ], $this->request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_provides_custom_error_messages()
    {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('floor_ids.required', $messages);
        $this->assertArrayHasKey('floor_ids.array', $messages);
        $this->assertArrayHasKey('floor_ids.min', $messages);
        $this->assertArrayHasKey('floor_ids.*.exists', $messages);

        // メッセージが日本語であることを確認
        $this->assertStringContainsString('フロア', $messages['floor_ids.required']);
    }

    /** @test */
    public function it_provides_custom_attributes()
    {
        $attributes = $this->request->attributes();

        $this->assertArrayHasKey('floor_ids', $attributes);
        $this->assertArrayHasKey('floor_ids.*', $attributes);
        $this->assertEquals('フロアID', $attributes['floor_ids']);
    }

    /** @test */
    public function validate_floors_method_returns_floor_details()
    {
        $dungeonFloors = Route::factory()->count(2)->create([
            'category' => 'dungeon',
            'dungeon_id' => null // オーファンフロア
        ]);

        // リクエストデータを設定
        $this->request->merge([
            'floor_ids' => $dungeonFloors->pluck('id')->toArray()
        ]);

        // バリデーションを実行
        $validator = Validator::make($this->request->all(), $this->request->rules());
        $this->assertFalse($validator->fails());

        $result = $this->request->validateFloors();

        $this->assertArrayHasKey('floors', $result);
        $this->assertArrayHasKey('validation_errors', $result);
        $this->assertArrayHasKey('warnings', $result);

        // フロア情報が正しく取得されているかチェック
        $this->assertEquals(2, $result['floors']->count());
        $this->assertEquals(0, count($result['validation_errors']));
        $this->assertEquals(0, count($result['warnings'])); // オーファンなので警告なし
    }

    /** @test */
    public function validate_floors_method_detects_floors_with_existing_parent()
    {
        $parent = DungeonDesc::factory()->create();
        
        $floorWithParent = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => $parent->dungeon_id
        ]);

        $orphanFloor = Route::factory()->create([
            'category' => 'dungeon',
            'dungeon_id' => null
        ]);

        // リクエストデータを設定
        $this->request->merge([
            'floor_ids' => [$floorWithParent->id, $orphanFloor->id]
        ]);

        $result = $this->request->validateFloors();

        $this->assertEquals(2, $result['floors']->count());
        $this->assertEquals(1, count($result['warnings'])); // 既存の親に紐づいているフロアの警告

        $warning = $result['warnings'][0];
        $this->assertEquals($floorWithParent->id, $warning['floor_id']);
        $this->assertEquals($floorWithParent->name, $warning['floor_name']);
        $this->assertEquals($parent->dungeon_name, $warning['current_parent']);
    }

    /** @test */
    public function it_handles_json_validation_failure_correctly()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // JSONリクエストをシミュレート
        $request = new AttachFloorsRequest();
        $request->headers->set('Accept', 'application/json');
        $request->merge(['floor_ids' => []]);

        $validator = Validator::make($request->all(), $request->rules());

        // 意図的にバリデーション失敗を起こしてfailedValidationメソッドをテスト
        $request->failedValidation($validator);
    }
}