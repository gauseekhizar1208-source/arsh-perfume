@extends('layouts.admin')

@php($isNew = ! $category->exists)
@section('title', $isNew ? 'New category' : 'Edit category')
@section('heading', $isNew ? 'New category' : 'Edit '.$category->name)

@section('content')
<form method="post" enctype="multipart/form-data" class="card max-w-2xl space-y-5 p-6"
      action="{{ $isNew ? route('admin.categories.store') : route('admin.categories.update', $category) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless
    <div><label class="label" for="name">Name</label><input id="name" name="name" class="input" value="{{ old('name', $category->name) }}" required maxlength="120"></div>
    <div><label class="label" for="slug">URL slug</label><input id="slug" name="slug" class="input" value="{{ old('slug', $category->slug) }}" placeholder="auto from name"></div>
    <div><label class="label" for="description">Description</label><textarea id="description" name="description" rows="4" class="input">{{ old('description', $category->description) }}</textarea></div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div><label class="label" for="sort_order">Sort order</label><input id="sort_order" name="sort_order" type="number" min="0" class="input" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
        <div class="flex items-end"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="accent-[#C8A24D]" @checked(old('is_active', $category->is_active))> Active on storefront</label></div>
    </div>
    <div>
        <label class="label" for="image">Image (optional, JPG/PNG/WebP, max 3 MB)</label>
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="input">
    </div>
    <div class="flex gap-3 pt-2"><button class="btn-gold">{{ $isNew ? 'Create category' : 'Save category' }}</button><a href="{{ route('admin.categories.index') }}" class="btn-ghost">Cancel</a></div>
</form>
@endsection
