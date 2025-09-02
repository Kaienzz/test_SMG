@extends('admin.layouts.app')
@section('title', '調合レシピ管理')
@section('subtitle', 'レシピ一覧と管理')

@php($canEdit = $adminUser && ($adminUser->admin_level === 'super' || app(\App\Services\Admin\AdminPermissionService::class)->hasPermission($adminUser, 'items.edit')))

@section('content')
<div class="admin-content-container">
  <div class="admin-card" style="margin-bottom:1rem;">
    <div class="admin-card-body">
      <form method="GET" action="{{ route('admin.compounding.recipes.index') }}" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="検索: 名前/キー" class="admin-form-input"/>
        <label style="display:flex;gap:.5rem;align-items:center;">
          <input type="checkbox" name="active" value="1" {{ isset($filters['active']) && $filters['active'] ? 'checked' : '' }}/> 有効のみ
        </label>
        <button class="admin-btn admin-btn-primary admin-btn-sm" type="submit">検索</button>
        @if($canEdit)
          <a href="{{ route('admin.compounding.recipes.create') }}" class="admin-btn admin-btn-success admin-btn-sm">新規作成</a>
        @endif
      </form>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-body" style="padding:0;">
      <div class="admin-table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>名前</th>
              <th>キー</th>
              <th>成果物</th>
              <th>成功率</th>
              <th>必要Lv</th>
              <th>有効</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($recipes as $r)
              <tr>
                <td>{{ $r->id }}</td>
                <td>{{ $r->name }}</td>
                <td><code>{{ $r->recipe_key }}</code></td>
                <td>{{ optional(\App\Models\Item::find($r->product_item_id))->name }} × {{ $r->product_quantity }}</td>
                <td>{{ $r->success_rate }}%</td>
                <td>{{ $r->required_skill_level }}</td>
                <td>{!! $r->is_active ? '<span class="admin-badge admin-badge-success">有効</span>' : '<span class="admin-badge admin-badge-secondary">無効</span>' !!}</td>
                <td style="text-align:right;">
                  @if($canEdit)
                    <a class="admin-btn admin-btn-xs admin-btn-secondary" href="{{ route('admin.compounding.recipes.edit', $r->id) }}">編集</a>
                    <form method="POST" action="{{ route('admin.compounding.recipes.destroy', $r->id) }}" style="display:inline-block;" onsubmit="return confirm('削除しますか？');">
                      @csrf
                      @method('DELETE')
                      <button class="admin-btn admin-btn-xs admin-btn-danger" type="submit">削除</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center">データがありません。</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:1rem;">{{ $recipes->links() }}</div>
    </div>
  </div>
</div>
@endsection
