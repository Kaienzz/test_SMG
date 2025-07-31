# テスト戦略書

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgプロジェクトにおける包括的なテスト戦略を定義し、品質保証とリリース安定性を確保する。

## 目次

1. [テスト戦略概要](#テスト戦略概要)
2. [テストレベル定義](#テストレベル定義)
3. [ユニットテスト](#ユニットテスト)
4. [統合テスト](#統合テスト)
5. [機能テスト](#機能テスト)
6. [E2Eテスト](#e2eテスト)
7. [パフォーマンステスト](#パフォーマンステスト)
8. [セキュリティテスト](#セキュリティテスト)
9. [テスト環境管理](#テスト環境管理)
10. [CI/CD統合](#ci-cd統合)
11. [品質メトリクス](#品質メトリクス)

## テスト戦略概要

### テストピラミッド
```
                /\
               /  \
              / E2E \
             /______\
            /        \
           /Integration\
          /____________\
         /              \
        /   Unit Tests   \
       /__________________\
```

### テスト原則
1. **高速フィードバック**: 開発者が迅速に結果を得られる
2. **信頼性**: テストが安定して実行される
3. **保守性**: テストコードが理解しやすく修正しやすい
4. **網羅性**: 重要な機能とエッジケースをカバー
5. **自動化**: 手動テストを最小限に抑制

### テスト配分比率
- ユニットテスト: 70%
- 統合テスト: 20%
- E2Eテスト: 10%

## テストレベル定義

### 1. ユニットテスト
- **対象**: 個別のクラス・メソッド
- **実行速度**: 高速（< 1ms）
- **依存関係**: モック・スタブを使用
- **実行頻度**: コード変更毎

### 2. 統合テスト
- **対象**: 複数コンポーネント間の連携
- **実行速度**: 中程度（< 100ms）
- **依存関係**: 実際のデータベース使用
- **実行頻度**: プルリクエスト毎

### 3. 機能テスト
- **対象**: API エンドポイント
- **実行速度**: 中程度（< 500ms）
- **依存関係**: テスト用データベース
- **実行頻度**: プルリクエスト毎

### 4. E2Eテスト
- **対象**: ユーザージャーニー全体
- **実行速度**: 低速（数秒〜数分）
- **依存関係**: ブラウザ自動化
- **実行頻度**: デプロイ前

## ユニットテスト

### 1. テスト対象クラス
```php
<?php

namespace Tests\Unit\Domain\Character;

use PHPUnit\Framework\TestCase;
use App\Domain\Character\CharacterSkills;
use App\Enums\SkillType;

class CharacterSkillsTest extends TestCase
{
    public function test_can_create_character_skills_with_initial_values(): void
    {
        $skills = new CharacterSkills([
            SkillType::ATTACK->value => 10,
            SkillType::DEFENSE->value => 8,
        ]);
        
        $this->assertEquals(10, $skills->getSkill(SkillType::ATTACK));
        $this->assertEquals(8, $skills->getSkill(SkillType::DEFENSE));
        $this->assertEquals(0, $skills->getSkill(SkillType::AGILITY));
    }
    
    public function test_calculates_character_level_correctly(): void
    {
        $skills = new CharacterSkills([
            SkillType::ATTACK->value => 25,
            SkillType::DEFENSE->value => 15,
            SkillType::AGILITY->value => 10,
        ]);
        
        $this->assertEquals(50, $skills->getTotalSkillLevel());
        $this->assertEquals(6, $skills->getCharacterLevel()); // 50/10 + 1 = 6
    }
    
    public function test_skill_increase_works_correctly(): void
    {
        $skills = new CharacterSkills();
        
        $skills->increaseSkill(SkillType::ATTACK, 5);
        
        $this->assertEquals(5, $skills->getSkill(SkillType::ATTACK));
    }
    
    public function test_throws_exception_when_skill_exceeds_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Skill level cannot exceed 100');
        
        $skills = new CharacterSkills();
        $skills->increaseSkill(SkillType::ATTACK, 101);
    }
    
    public function test_can_reset_all_skills(): void
    {
        $skills = new CharacterSkills([
            SkillType::ATTACK->value => 25,
            SkillType::DEFENSE->value => 15,
        ]);
        
        $skills->resetAllSkills();
        
        $this->assertEquals(0, $skills->getSkill(SkillType::ATTACK));
        $this->assertEquals(0, $skills->getSkill(SkillType::DEFENSE));
        $this->assertEquals(0, $skills->getTotalSkillLevel());
    }
}
```

### 2. サービスクラステスト
```php
<?php

namespace Tests\Unit\Application\Services;

use PHPUnit\Framework\TestCase;
use Mockery;
use App\Application\Services\GameStateManager;
use App\Domain\Character\CharacterRepository;
use App\Domain\Location\LocationService;
use App\Models\Character;
use App\Enums\LocationType;

class GameStateManagerTest extends TestCase
{
    private GameStateManager $gameStateManager;
    private $characterRepository;
    private $locationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->characterRepository = Mockery::mock(CharacterRepository::class);
        $this->locationService = Mockery::mock(LocationService::class);
        
        $this->gameStateManager = new GameStateManager(
            $this->characterRepository,
            $this->locationService
        );
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_roll_dice_returns_correct_result(): void
    {
        $character = Mockery::mock(Character::class);
        $character->shouldReceive('getDiceCount')->andReturn(3);
        $character->shouldReceive('getDiceBonus')->andReturn(2);
        $character->shouldReceive('getMovementMultiplier')->andReturn(1.0);
        
        $result = $this->gameStateManager->rollDice($character);
        
        $this->assertCount(3, $result->getDiceRolls());
        $this->assertGreaterThanOrEqual(3, $result->getBaseTotal());
        $this->assertLessThanOrEqual(18, $result->getBaseTotal());
        $this->assertEquals(2, $result->getBonus());
        $this->assertGreaterThanOrEqual(5, $result->getFinalMovement());
    }
    
    public function test_move_character_updates_position(): void
    {
        $character = Mockery::mock(Character::class);
        $character->shouldReceive('getGamePosition')->andReturn(50);
        $character->shouldReceive('getLocationType')->andReturn(LocationType::ROAD);
        $character->shouldReceive('setGamePosition')->with(55)->once();
        
        $this->characterRepository
            ->shouldReceive('save')
            ->with($character)
            ->once();
        
        $this->locationService
            ->shouldReceive('getCurrentLocation')
            ->with($character)
            ->andReturn(null);
        
        $request = Mockery::mock();
        $request->shouldReceive('input')->with('direction')->andReturn('right');
        $request->shouldReceive('input')->with('steps')->andReturn(5);
        
        $result = $this->gameStateManager->moveCharacter($character, $request);
        
        $this->assertEquals(55, $result->getPosition());
    }
    
    public function test_cannot_move_character_in_town(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move in town');
        
        $character = Mockery::mock(Character::class);
        $character->shouldReceive('getLocationType')->andReturn(LocationType::TOWN);
        
        $request = Mockery::mock();
        $request->shouldReceive('input')->with('direction')->andReturn('right');
        $request->shouldReceive('input')->with('steps')->andReturn(5);
        
        $this->gameStateManager->moveCharacter($character, $request);
    }
}
```

### 3. DTOテスト
```php
<?php

namespace Tests\Unit\Application\DTOs;

use PHPUnit\Framework\TestCase;
use App\Application\DTOs\DiceResultDto;

class DiceResultDtoTest extends TestCase
{
    public function test_can_create_dice_result_dto(): void
    {
        $diceRolls = [6, 4, 3];
        $baseTotal = 13;
        $bonus = 2;
        $finalMovement = 15;
        
        $dto = new DiceResultDto($diceRolls, $baseTotal, $bonus, $finalMovement);
        
        $this->assertEquals($diceRolls, $dto->diceRolls);
        $this->assertEquals($baseTotal, $dto->baseTotal);
        $this->assertEquals($bonus, $dto->bonus);
        $this->assertEquals($finalMovement, $dto->finalMovement);
    }
    
    public function test_to_array_returns_correct_structure(): void
    {
        $dto = new DiceResultDto([6, 4, 3], 13, 2, 15);
        $array = $dto->toArray();
        
        $expected = [
            'dice_rolls' => [6, 4, 3],
            'base_total' => 13,
            'bonus' => 2,
            'final_movement' => 15,
        ];
        
        $this->assertEquals($expected, $array);
    }
}
```

### 4. テストデータファクトリー
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Character;
use App\Enums\LocationType;

class CharacterFactory extends Factory
{
    protected $model = Character::class;
    
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->name(),
            'hp' => 100,
            'max_hp' => 100,
            'sp' => 50,
            'max_sp' => 50,
            'game_position' => $this->faker->numberBetween(0, 100),
            'location_type' => $this->faker->randomElement(LocationType::cases()),
            'skills' => [
                'attack' => $this->faker->numberBetween(1, 20),
                'defense' => $this->faker->numberBetween(1, 20),
                'agility' => $this->faker->numberBetween(1, 20),
                'gathering' => $this->faker->numberBetween(1, 10),
                'crafting' => $this->faker->numberBetween(1, 10),
            ],
            'inventory' => [],
        ];
    }
    
    public function inTown(): static
    {
        return $this->state([
            'location_type' => LocationType::TOWN,
            'game_position' => 0,
        ]);
    }
    
    public function onRoad(): static
    {
        return $this->state([
            'location_type' => LocationType::ROAD,
            'game_position' => $this->faker->numberBetween(1, 99),
        ]);
    }
    
    public function atRoadEnd(): static
    {
        return $this->state([
            'location_type' => LocationType::ROAD,
            'game_position' => 100,
        ]);
    }
    
    public function withHighSkills(): static
    {
        return $this->state([
            'skills' => [
                'attack' => $this->faker->numberBetween(80, 100),
                'defense' => $this->faker->numberBetween(80, 100),
                'agility' => $this->faker->numberBetween(80, 100),
                'gathering' => $this->faker->numberBetween(80, 100),
                'crafting' => $this->faker->numberBetween(80, 100),
            ],
        ]);
    }
}
```

## 統合テスト

### 1. データベース統合テスト
```php
<?php

namespace Tests\Integration\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Character;
use App\Enums\LocationType;

class CharacterModelTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_have_character(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(Character::class, $user->character);
        $this->assertEquals($character->id, $user->character->id);
    }
    
    public function test_character_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $character->user);
        $this->assertEquals($user->id, $character->user->id);
    }
    
    public function test_get_or_create_character_creates_new_character(): void
    {
        $user = User::factory()->create();
        
        $this->assertNull($user->character);
        
        $character = $user->getOrCreateCharacter();
        
        $this->assertInstanceOf(Character::class, $character);
        $this->assertEquals($user->id, $character->user_id);
        $this->assertEquals(LocationType::TOWN, $character->location_type);
    }
    
    public function test_get_or_create_character_returns_existing_character(): void
    {
        $user = User::factory()->create();
        $existingCharacter = Character::factory()->create(['user_id' => $user->id]);
        
        $character = $user->getOrCreateCharacter();
        
        $this->assertEquals($existingCharacter->id, $character->id);
    }
    
    public function test_character_level_calculation(): void
    {
        $character = Character::factory()->create([
            'skills' => [
                'attack' => 25,
                'defense' => 15,
                'agility' => 10,
                'gathering' => 0,
                'crafting' => 0,
            ]
        ]);
        
        $this->assertEquals(50, $character->getSkillSet()->getTotalLevel());
        $this->assertEquals(6, $character->getLevel()); // 50/10 + 1 = 6
    }
    
    public function test_inventory_slots_return_correct_structure(): void
    {
        $character = Character::factory()->create([
            'inventory' => [
                0 => ['item_id' => 1, 'quantity' => 5, 'quality' => 2],
                1 => ['item_id' => null, 'quantity' => 0, 'quality' => null],
            ]
        ]);
        
        $slots = $character->getInventorySlots();
        
        $this->assertCount(30, $slots);
        $this->assertEquals(1, $slots[0]->itemId);
        $this->assertEquals(5, $slots[0]->quantity);
        $this->assertNull($slots[1]->itemId);
        $this->assertTrue($slots[1]->isEmpty());
    }
}
```

### 2. リポジトリ統合テスト
```php
<?php

namespace Tests\Integration\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Infrastructure\Repositories\EloquentCharacterRepository;
use App\Models\Character;
use App\Models\User;
use App\Enums\LocationType;

class EloquentCharacterRepositoryTest extends TestCase
{
    use RefreshDatabase;
    
    private EloquentCharacterRepository $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentCharacterRepository();
    }
    
    public function test_can_find_character_by_id(): void
    {
        $character = Character::factory()->create();
        
        $found = $this->repository->findById($character->id);
        
        $this->assertNotNull($found);
        $this->assertEquals($character->id, $found->id);
    }
    
    public function test_returns_null_when_character_not_found(): void
    {
        $found = $this->repository->findById(999);
        
        $this->assertNull($found);
    }
    
    public function test_can_save_character(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->make(['user_id' => $user->id]);
        
        $saved = $this->repository->save($character);
        
        $this->assertNotNull($saved->id);
        $this->assertDatabaseHas('characters', [
            'id' => $saved->id,
            'user_id' => $user->id,
        ]);
    }
    
    public function test_can_find_characters_by_location_type(): void
    {
        Character::factory()->count(3)->inTown()->create();
        Character::factory()->count(2)->onRoad()->create();
        
        $townCharacters = $this->repository->findByLocationType(LocationType::TOWN);
        $roadCharacters = $this->repository->findByLocationType(LocationType::ROAD);
        
        $this->assertCount(3, $townCharacters);
        $this->assertCount(2, $roadCharacters);
    }
}
```

## 機能テスト

### 1. APIエンドポイントテスト
```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Character;
use App\Enums\LocationType;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;
    
    private User $user;
    private Character $character;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->character = Character::factory()->create([
            'user_id' => $this->user->id,
            'location_type' => LocationType::ROAD,
            'game_position' => 50,
        ]);
    }
    
    public function test_roll_dice_returns_valid_response(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/roll-dice');
        
        $response->assertOk()
            ->assertJsonStructure([
                'dice_rolls',
                'base_total',
                'bonus',
                'final_movement',
            ]);
        
        $data = $response->json();
        $this->assertIsArray($data['dice_rolls']);
        $this->assertIsInt($data['base_total']);
        $this->assertIsInt($data['bonus']);
        $this->assertIsInt($data['final_movement']);
        $this->assertGreaterThan(0, $data['final_movement']);
    }
    
    public function test_move_character_updates_position(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/move', [
                'direction' => 'right',
                'steps' => 10,
            ]);
        
        $response->assertOk()
            ->assertJsonStructure([
                'position',
                'currentLocation',
                'nextLocation',
                'canMoveToNext',
                'encounter',
            ]);
        
        $this->character->refresh();
        $this->assertEquals(60, $this->character->game_position);
    }
    
    public function test_move_validates_direction(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/move', [
                'direction' => 'invalid',
                'steps' => 10,
            ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['direction']);
    }
    
    public function test_move_validates_steps_range(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/move', [
                'direction' => 'right',
                'steps' => 50, // 最大30を超える
            ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['steps']);
    }
    
    public function test_cannot_move_in_town(): void
    {
        $this->character->update(['location_type' => LocationType::TOWN]);
        
        $response = $this->actingAs($this->user)
            ->postJson('/game/move', [
                'direction' => 'right',
                'steps' => 10,
            ]);
        
        $response->assertBadRequest()
            ->assertJson([
                'error' => 'Cannot move in town',
            ]);
    }
    
    public function test_move_to_next_location(): void
    {
        $this->character->update(['game_position' => 100]);
        
        $response = $this->actingAs($this->user)
            ->postJson('/game/move-to-next');
        
        $response->assertOk()
            ->assertJsonStructure([
                'position',
                'currentLocation',
                'nextLocation',
            ]);
        
        $this->character->refresh();
        $this->assertEquals(LocationType::TOWN, $this->character->location_type);
        $this->assertEquals(0, $this->character->game_position);
    }
    
    public function test_reset_game_state(): void
    {
        $this->character->update([
            'game_position' => 75,
            'location_type' => LocationType::ROAD,
        ]);
        
        $response = $this->actingAs($this->user)
            ->postJson('/game/reset');
        
        $response->assertOk();
        
        $this->character->refresh();
        $this->assertEquals(0, $this->character->game_position);
        $this->assertEquals(LocationType::TOWN, $this->character->location_type);
    }
    
    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/game/roll-dice');
        
        $response->assertUnauthorized();
    }
}
```

### 2. 認証機能テスト
```php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        
        $response->assertOk();
    }
    
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();
        
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }
    
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();
        
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        
        $this->assertGuest();
    }
    
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post('/logout');
        
        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
```

## E2Eテスト

### 1. Laravelデスクテスト設定
```php
<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;

class GameplayTest extends DuskTestCase
{
    public function test_user_can_play_basic_game_flow(): void
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/game')
                ->assertSee('町にいます')
                ->clickLink('森の道に移動する')
                ->assertSee('道を歩いています')
                ->click('#roll-dice')
                ->waitForText('最終移動距離', 5)
                ->click('#move-right')
                ->waitUntilMissing('#movement-controls', 5)
                ->assertSee('位置');
        });
    }
    
    public function test_dice_rolling_and_movement(): void
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/game')
                ->clickLink('森の道に移動する')
                ->click('#roll-dice')
                ->waitForText('最終移動距離', 5)
                ->assertVisible('#move-left')
                ->assertVisible('#move-right')
                ->click('#move-right')
                ->waitUntilMissing('#movement-controls', 5);
        });
    }
    
    public function test_battle_encounter(): void
    {
        // モンスターエンカウントのテスト
        $this->markTestIncomplete('Battle system not yet implemented');
    }
    
    public function test_gathering_system(): void
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/game')
                ->clickLink('森の道に移動する')
                ->click('#gathering-btn')
                ->waitForText('採集', 10)
                ->acceptDialog()
                ->assertSee('採集');
        });
    }
    
    public function test_responsive_design(): void
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->resize(375, 667) // iPhone SE サイズ
                ->visit('/game')
                ->assertVisible('.game-container')
                ->resize(768, 1024) // iPad サイズ
                ->refresh()
                ->assertVisible('.game-container')
                ->resize(1920, 1080) // デスクトップサイズ
                ->refresh()
                ->assertVisible('.game-container');
        });
    }
}
```

### 2. JavaScriptテスト
```javascript
// tests/js/gameManager.test.js
import { GameManager, DiceManager, MovementManager } from '../../../public/js/game.js';

describe('GameManager', () => {
    let gameManager;
    let mockGameData;
    
    beforeEach(() => {
        mockGameData = {
            character: {
                game_position: 50,
                location_type: 'road'
            },
            currentLocation: {
                name: '森の道',
                type: 'road'
            },
            nextLocation: {
                name: '山の町',
                type: 'town'
            }
        };
        
        // DOM要素のモック
        document.body.innerHTML = `
            <div id="current-location"></div>
            <div id="location-type"></div>
            <div id="next-location-info" class="hidden">
                <strong></strong>
                <button></button>
            </div>
            <div id="movement-controls" class="hidden"></div>
            <div id="dice-result" class="hidden"></div>
        `;
        
        gameManager = new GameManager(mockGameData);
    });
    
    test('initializes with correct game data', () => {
        expect(gameManager.gameData).toEqual(mockGameData);
        expect(gameManager.currentSteps).toBe(0);
    });
    
    test('updates game display correctly', () => {
        const data = {
            currentLocation: { name: '新しい場所' },
            position: 75,
            location_type: 'road'
        };
        
        gameManager.updateGameDisplay(data);
        
        expect(document.getElementById('current-location').textContent).toBe('新しい場所');
        expect(gameManager.gameData.character.game_position).toBe(75);
    });
    
    test('shows next location button when can move', () => {
        const nextLocation = { name: '次の場所' };
        
        gameManager.updateNextLocationDisplay(nextLocation, true);
        
        const nextLocationInfo = document.getElementById('next-location-info');
        expect(nextLocationInfo.classList.contains('hidden')).toBe(false);
        expect(nextLocationInfo.querySelector('strong').textContent).toBe('次の場所');
    });
    
    test('hides next location button when cannot move', () => {
        const nextLocation = { name: '次の場所' };
        
        gameManager.updateNextLocationDisplay(nextLocation, false);
        
        const nextLocationInfo = document.getElementById('next-location-info');
        expect(nextLocationInfo.classList.contains('hidden')).toBe(true);
    });
});

describe('DiceManager', () => {
    let diceManager;
    let gameManager;
    
    beforeEach(() => {
        global.fetch = jest.fn();
        gameManager = { currentSteps: 0, gameData: { character: { location_type: 'road' } } };
        diceManager = new DiceManager(gameManager);
        
        document.body.innerHTML = `
            <meta name="csrf-token" content="test-token">
            <button id="roll-dice"></button>
            <div id="all-dice"></div>
            <span id="base-total"></span>
            <span id="bonus"></span>
            <span id="final-movement"></span>
            <div id="dice-result" class="hidden"></div>
            <div id="dice-total" class="hidden"></div>
            <div id="movement-controls" class="hidden"></div>
        `;
    });
    
    test('rolls dice and updates display', async () => {
        const mockResponse = {
            dice_rolls: [6, 4, 2],
            base_total: 12,
            bonus: 3,
            final_movement: 15
        };
        
        global.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve(mockResponse)
        });
        
        await diceManager.rollDice();
        
        expect(document.getElementById('base-total').textContent).toBe('12');
        expect(document.getElementById('bonus').textContent).toBe('3');
        expect(document.getElementById('final-movement').textContent).toBe('15');
        expect(gameManager.currentSteps).toBe(15);
    });
    
    test('disables button during dice roll', () => {
        const rollButton = document.getElementById('roll-dice');
        
        global.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                dice_rolls: [6, 4, 2],
                base_total: 12,
                bonus: 3,
                final_movement: 15
            })
        });
        
        diceManager.rollDice();
        
        expect(rollButton.disabled).toBe(true);
    });
});
```

## パフォーマンステスト

### 1. データベースクエリテスト
```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Character;
use App\Models\User;

class DatabasePerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_character_creation_performance(): void
    {
        $users = User::factory()->count(100)->create();
        
        $startTime = microtime(true);
        
        foreach ($users as $user) {
            $user->getOrCreateCharacter();
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 100ユーザーのキャラクター作成が1秒以内
        $this->assertLessThan(1.0, $executionTime);
    }
    
    public function test_bulk_character_query_performance(): void
    {
        Character::factory()->count(1000)->create();
        
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        
        $characters = Character::with('user')
            ->where('location_type', 'road')
            ->get();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $queryCount = count(DB::getQueryLog());
        
        // N+1問題がないことを確認
        $this->assertLessThanOrEqual(2, $queryCount);
        // 1000レコードの取得が100ms以内
        $this->assertLessThan(0.1, $executionTime);
    }
    
    public function test_api_response_time(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)
            ->getJson('/game');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertOk();
        // API応答時間が500ms以内
        $this->assertLessThan(0.5, $executionTime);
    }
}
```

### 2. メモリ使用量テスト
```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Character;

class MemoryUsageTest extends TestCase
{
    public function test_memory_usage_for_large_dataset(): void
    {
        $initialMemory = memory_get_usage();
        
        // 大量のキャラクターデータを処理
        $characters = Character::factory()->count(10000)->make();
        
        foreach ($characters as $character) {
            $character->getLevel();
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // メモリ使用量が50MB以内
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
    }
}
```

## セキュリティテスト

### 1. 認証・認可テスト
```php
<?php

namespace Tests\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Character;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_cannot_access_other_users_character(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $character2 = Character::factory()->create(['user_id' => $user2->id]);
        
        $response = $this->actingAs($user1)
            ->getJson("/api/characters/{$character2->id}");
        
        $response->assertForbidden();
    }
    
    public function test_unauthorized_user_cannot_roll_dice(): void
    {
        $response = $this->postJson('/game/roll-dice');
        
        $response->assertUnauthorized();
    }
    
    public function test_csrf_protection_on_state_changing_requests(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/game/move', [
                'direction' => 'right',
                'steps' => 5,
            ], [
                'Content-Type' => 'application/json',
                // CSRFトークンなし
            ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    }
}
```

### 2. 入力検証テスト
```php
<?php

namespace Tests\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class InputValidationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_sql_injection_protection(): void
    {
        $user = User::factory()->create();
        
        $maliciousInput = "'; DROP TABLE characters; --";
        
        $response = $this->actingAs($user)
            ->postJson('/game/move', [
                'direction' => $maliciousInput,
                'steps' => 5,
            ]);
        
        $response->assertUnprocessable();
        
        // テーブルが存在することを確認
        $this->assertDatabaseHasTable('characters');
    }
    
    public function test_xss_protection(): void
    {
        $user = User::factory()->create();
        
        $maliciousInput = '<script>alert("XSS")</script>';
        
        $response = $this->actingAs($user)
            ->postJson('/api/characters', [
                'name' => $maliciousInput,
            ]);
        
        $response->assertUnprocessable();
    }
    
    public function test_mass_assignment_protection(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/characters', [
                'name' => 'Test Character',
                'user_id' => 999, // 他のユーザーIDを指定
                'hp' => 9999, // 通常設定できない値
            ]);
        
        if ($response->isOk()) {
            $character = $response->json('data');
            $this->assertEquals($user->id, $character['user_id']);
            $this->assertNotEquals(9999, $character['hp']);
        }
    }
}
```

## テスト環境管理

### 1. テストデータベース設定
```php
// config/database.php
'testing' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
],

// phpunit.xml
<env name="DB_CONNECTION" value="testing"/>
<env name="APP_ENV" value="testing"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

### 2. テストヘルパー
```php
<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Character;
use Illuminate\Foundation\Testing\TestCase;

trait GameTestHelpers
{
    protected function createUserWithCharacter(array $characterAttributes = []): array
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(
            array_merge(['user_id' => $user->id], $characterAttributes)
        );
        
        return [$user, $character];
    }
    
    protected function actAsGameUser(array $characterAttributes = []): TestCase
    {
        [$user, $character] = $this->createUserWithCharacter($characterAttributes);
        
        return $this->actingAs($user);
    }
    
    protected function assertCharacterPosition(Character $character, int $expectedPosition): void
    {
        $character->refresh();
        $this->assertEquals($expectedPosition, $character->game_position);
    }
    
    protected function mockSuccessfulApiCall(string $url, array $responseData): void
    {
        Http::fake([
            $url => Http::response($responseData, 200)
        ]);
    }
}
```

## CI/CD統合

### 1. GitHub Actions テスト設定
```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.2, 8.3]
        
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
          
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, dom, fileinfo, mysql
        coverage: xdebug
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-interaction
      
    - name: Copy environment file
      run: cp .env.testing .env
      
    - name: Generate key
      run: php artisan key:generate
      
    - name: Run migrations
      run: php artisan migrate
      
    - name: Run unit tests
      run: php artisan test --testsuite=Unit --coverage-clover=coverage.xml
      
    - name: Run feature tests
      run: php artisan test --testsuite=Feature
      
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
```

### 2. 並列テスト実行
```yaml
# .github/workflows/parallel-tests.yml
name: Parallel Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        test-suite: [Unit, Feature, Integration]
        
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.2
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-interaction
      
    - name: Run ${{ matrix.test-suite }} tests
      run: php artisan test --testsuite=${{ matrix.test-suite }}
```

## 品質メトリクス

### 1. コードカバレッジ目標
- **全体カバレッジ**: 80%以上
- **ユニットテスト**: 90%以上
- **重要ビジネスロジック**: 95%以上

### 2. テスト品質指標
```php
<?php

namespace Tests\Metrics;

use PHPUnit\Framework\TestCase;

class QualityMetricsTest extends TestCase
{
    public function test_code_coverage_meets_minimum_threshold(): void
    {
        $coverageData = $this->getCoverageData();
        
        $this->assertGreaterThanOrEqual(80, $coverageData['overall']);
        $this->assertGreaterThanOrEqual(90, $coverageData['unit_tests']);
    }
    
    public function test_test_execution_time_is_acceptable(): void
    {
        $executionTime = $this->getTestExecutionTime();
        
        // 全テストが5分以内で完了
        $this->assertLessThan(300, $executionTime);
    }
    
    private function getCoverageData(): array
    {
        // カバレッジデータを取得するロジック
        return ['overall' => 85, 'unit_tests' => 92];
    }
    
    private function getTestExecutionTime(): int
    {
        // テスト実行時間を取得するロジック
        return 120; // 秒
    }
}
```

### 3. 継続的品質監視
```bash
#!/bin/bash
# scripts/quality-check.sh

echo "Running quality checks..."

# テスト実行
php artisan test --coverage-text --min=80

# 静的解析
./vendor/bin/phpstan analyse app tests

# コードスタイル
./vendor/bin/php-cs-fixer fix --dry-run --diff

# セキュリティチェック
composer audit

echo "Quality checks completed."
```

## まとめ

### テスト戦略の要点
1. **自動化重視**: 手動テストを最小限に抑制
2. **高速フィードバック**: 開発者が迅速に結果を得られる
3. **品質保証**: バグの早期発見と修正
4. **継続的改善**: メトリクスに基づく改善

### 実装順序
1. ユニットテスト基盤構築
2. 機能テスト実装
3. 統合テスト追加
4. E2Eテスト整備
5. パフォーマンス・セキュリティテスト

このテスト戦略により、test_smgプロジェクトの品質と安定性を確保できます。