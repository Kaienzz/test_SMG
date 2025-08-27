@extends('admin.layouts.app')

@section('title', '町編集: ' . $town->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">町編集: {{ $town->name }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.towns.show', $town->id) }}" class="btn btn-outline-info me-2">
                <i class="fas fa-eye"></i> 詳細表示
            </a>
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
                    <form action="{{ route('admin.towns.update', $town->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="id" class="form-label">町ID</label>
                            <input type="text" class="form-control-plaintext" id="id" value="{{ $town->id }}" readonly>
                            <div class="form-text">IDは変更できません</div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">町名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $town->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $town->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">サービス</label>
                            @php
                                $currentServices = old('services', $town->services ?? []);
                            @endphp
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="services[]" value="general_store" id="service_general_store"
                                       {{ in_array('general_store', $currentServices) ? 'checked' : '' }}>
                                <label class="form-check-label" for="service_general_store">雑貨屋</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="services[]" value="inn" id="service_inn"
                                       {{ in_array('inn', $currentServices) ? 'checked' : '' }}>
                                <label class="form-check-label" for="service_inn">宿屋</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="services[]" value="bank" id="service_bank"
                                       {{ in_array('bank', $currentServices) ? 'checked' : '' }}>
                                <label class="form-check-label" for="service_bank">銀行</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('is_active') is-invalid @enderror" 
                                       type="checkbox" name="is_active" id="is_active" value="1" 
                                       {{ old('is_active', $town->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">有効</label>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.towns.show', $town->id) }}" class="btn btn-secondary me-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">更新</button>
                        </div>
                    </form>
                </div>
            </div>
            
            {{-- 接続管理セクション --}}
            @include('admin.shared._route_connections', [
                'location' => $town,
                'form_prefix' => 'connections'
            ])
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">町情報</h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">作成日時</dt>
                        <dd class="col-sm-8">{{ $town->created_at->format('Y-m-d H:i:s') }}</dd>
                        <dt class="col-sm-4">更新日時</dt>
                        <dd class="col-sm-8">{{ $town->updated_at->format('Y-m-d H:i:s') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection