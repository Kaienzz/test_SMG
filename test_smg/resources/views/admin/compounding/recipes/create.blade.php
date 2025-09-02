@extends('admin.layouts.app')
@section('title', '調合レシピ作成')
@section('subtitle', '新規レシピ')

@section('content')
<div class="admin-content-container">
  <form method="POST" action="{{ route('admin.compounding.recipes.store') }}" class="admin-form">
    @csrf
    @include('admin.compounding.recipes.partials.form', ['mode' => 'create'])
  </form>
</div>
@endsection
