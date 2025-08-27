@extends('admin.layouts.app')

@section('title', '町作成')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">町作成</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.towns.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">町情報</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.towns.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="id" class="form-label">町ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('id') is-invalid @enderror" 
                                   id="id" name="id" value="{{ old('id') }}" required>
                            <div class="form-text">英数字、アンダースコア、ハイフンが使用できます</div>
                            @error('id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">町名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 接続管理セクション（初回作成時は新規のみ） --}}
                        @include('admin.shared._route_connections', [
                            'location' => null,
                            'form_prefix' => 'connections'
                        ])

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('is_active') is-invalid @enderror" 
                                       type="checkbox" name="is_active" id="is_active" value="1" 
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">有効</label>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.towns.index') }}" class="btn btn-secondary me-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">作成</button>
                        </div>
                    </form>
                </div>
            </div>
            
            
        </div>
    </div>
</div>
@endsection