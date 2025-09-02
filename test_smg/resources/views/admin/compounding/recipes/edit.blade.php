@extends('admin.layouts.app')
@section('title', '調合レシピ編集')
@section('subtitle', $recipe->name)

@section('content')
<div class="admin-content-container">
  <form method="POST" action="{{ route('admin.compounding.recipes.update', $recipe->id) }}" class="admin-form">
    @csrf
    @method('PUT')
    @include('admin.compounding.recipes.partials.form', ['mode' => 'edit'])
  </form>
</div>
@endsection
