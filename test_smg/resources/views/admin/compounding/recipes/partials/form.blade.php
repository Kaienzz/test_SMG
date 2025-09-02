@php
  $itemsMap = collect($items)->pluck('name','id');
  $recipeData = isset($recipe) ? $recipe : null;
  $ingredients = old('ingredients', isset($recipeData) ? $recipeData->ingredients->map(fn($i)=>['item_id'=>$i->item_id,'quantity'=>$i->quantity])->toArray() : [['item_id'=>'','quantity'=>'']]);
  $locations = old('locations', isset($recipeData) ? $recipeData->locations->pluck('location_id')->toArray() : []);
@endphp

@if ($errors->any())
  <div class="admin-alert admin-alert-danger" style="margin-bottom:1rem;">
    <ul style="margin:0; padding-left:1rem;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="admin-card">
  <div class="admin-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <div>
      <label class="admin-form-label">レシピ名</label>
      <input class="admin-form-input" type="text" name="name" value="{{ old('name', $recipeData->name ?? '') }}" required />
    </div>
    <div>
      <label class="admin-form-label">キー</label>
      <input class="admin-form-input" type="text" name="recipe_key" value="{{ old('recipe_key', $recipeData->recipe_key ?? '') }}" required />
    </div>
    <div>
      <label class="admin-form-label">成果物アイテム</label>
      <select name="product_item_id" class="admin-form-input" required>
        <option value="">-- 選択 --</option>
        @foreach($items as $it)
          <option value="{{ $it['id'] }}" {{ (string)old('product_item_id', $recipeData->product_item_id ?? '') === (string)$it['id'] ? 'selected' : '' }}>{{ $it['name'] }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="admin-form-label">成果物個数</label>
      <input class="admin-form-input" type="number" min="1" name="product_quantity" value="{{ old('product_quantity', $recipeData->product_quantity ?? 1) }}" />
    </div>
    <div>
      <label class="admin-form-label">必要スキルLv</label>
      <input class="admin-form-input" type="number" min="1" name="required_skill_level" value="{{ old('required_skill_level', $recipeData->required_skill_level ?? 1) }}" />
    </div>
    <div>
      <label class="admin-form-label">成功率(%)</label>
      <input class="admin-form-input" type="number" min="1" max="100" name="success_rate" value="{{ old('success_rate', $recipeData->success_rate ?? 100) }}" />
    </div>
    <div>
      <label class="admin-form-label">SPコスト</label>
      <input class="admin-form-input" type="number" min="0" name="sp_cost" value="{{ old('sp_cost', $recipeData->sp_cost ?? 15) }}" />
    </div>
    <div>
      <label class="admin-form-label">基礎EXP</label>
      <input class="admin-form-input" type="number" min="0" name="base_exp" value="{{ old('base_exp', $recipeData->base_exp ?? 100) }}" />
    </div>
    <div style="grid-column:1/-1;">
      <label class="admin-form-label">説明/メモ</label>
      <textarea class="admin-form-input" name="notes" rows="3">{{ old('notes', $recipeData->notes ?? '') }}</textarea>
    </div>
    <div>
      <label class="admin-form-label">有効</label>
      <input type="checkbox" name="is_active" value="1" {{ old('is_active', $recipeData->is_active ?? true) ? 'checked' : '' }} />
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-body">
    <h3 style="margin-top:0;">材料</h3>
    <div id="ingredients-list">
      @foreach($ingredients as $idx => $ing)
        <div class="ingredient-row" style="display:flex;gap:.5rem;margin-bottom:.5rem;">
          <select name="ingredients[{{ $idx }}][item_id]" class="admin-form-input" style="min-width:260px;">
            <option value="">-- アイテム --</option>
            @foreach($items as $it)
              <option value="{{ $it['id'] }}" {{ (string)($ing['item_id'] ?? '') === (string)$it['id'] ? 'selected' : '' }}>{{ $it['name'] }}</option>
            @endforeach
          </select>
          <input type="number" name="ingredients[{{ $idx }}][quantity]" class="admin-form-input" placeholder="数量" min="1" value="{{ $ing['quantity'] ?? '' }}" style="width:120px;"/>
          <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="this.parentElement.remove();">削除</button>
        </div>
      @endforeach
    </div>
    <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="addIngredientRow();">材料を追加</button>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-body">
    <h3 style="margin-top:0;">町割当（location_id文字列）</h3>
    <div id="locations-list">
      @foreach($locations as $i => $loc)
        <div class="location-row" style="display:flex;gap:.5rem;margin-bottom:.5rem;">
          <input type="text" name="locations[{{ $i }}]" class="admin-form-input" placeholder="例: town_a" value="{{ $loc }}"/>
          <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="this.parentElement.remove();">削除</button>
        </div>
      @endforeach
    </div>
    <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="addLocationRow();">町を追加</button>
  </div>
</div>

<div style="margin-top:1rem;display:flex;gap:.5rem;">
  <button type="submit" class="admin-btn admin-btn-primary">保存</button>
  <a href="{{ route('admin.compounding.recipes.index') }}" class="admin-btn admin-btn-secondary">戻る</a>
  @if(isset($recipeData))
  <form method="POST" action="{{ route('admin.compounding.recipes.destroy', $recipeData->id) }}" onsubmit="return confirm('削除しますか？');">
    @csrf
    @method('DELETE')
    <button type="submit" class="admin-btn admin-btn-danger">削除</button>
  </form>
  @endif
  @csrf
</div>

<script>
let ingredientIndex = {{ count($ingredients) }};
function addIngredientRow(){
  const c = document.getElementById('ingredients-list');
  const div = document.createElement('div');
  div.className = 'ingredient-row';
  div.style = 'display:flex;gap:.5rem;margin-bottom:.5rem;';
  div.innerHTML = `
    <select name="ingredients[${ingredientIndex}][item_id]" class="admin-form-input" style="min-width:260px;">
      <option value="">-- アイテム --</option>
      ${@json($items).map(it=>`<option value="${it.id}">${it.name}</option>`).join('')}
    </select>
    <input type="number" name="ingredients[${ingredientIndex}][quantity]" class="admin-form-input" placeholder="数量" min="1" style="width:120px;"/>
    <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="this.parentElement.remove();">削除</button>
  `;
  c.appendChild(div);
  ingredientIndex++;
}

let locationIndex = {{ count($locations) }};
function addLocationRow(){
  const c = document.getElementById('locations-list');
  const div = document.createElement('div');
  div.className = 'location-row';
  div.style = 'display:flex;gap:.5rem;margin-bottom:.5rem;';
  div.innerHTML = `
    <input type="text" name="locations[${locationIndex}]" class="admin-form-input" placeholder="例: town_a"/>
    <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="this.parentElement.remove();">削除</button>
  `;
  c.appendChild(div);
  locationIndex++;
}
</script>
