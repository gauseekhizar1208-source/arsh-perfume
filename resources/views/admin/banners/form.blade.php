@extends('layouts.admin')

@php($isNew = ! $banner->exists)
@section('title', $isNew ? 'New banner' : 'Edit banner')
@section('heading', $isNew ? 'New banner' : 'Edit banner')

@section('content')
<form method="post" enctype="multipart/form-data" class="card grid max-w-3xl gap-5 p-6 sm:grid-cols-2"
      action="{{ $isNew ? route('admin.banners.store') : route('admin.banners.update', $banner) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless
    <div class="sm:col-span-2"><label class="label" for="title">Title</label><input id="title" name="title" class="input" value="{{ old('title', $banner->title) }}" required maxlength="120"></div>
    <div class="sm:col-span-2"><label class="label" for="subtitle">Subtitle</label><input id="subtitle" name="subtitle" class="input" value="{{ old('subtitle', $banner->subtitle) }}" maxlength="250"></div>
    <div><label class="label" for="position">Position</label>
        <select id="position" name="position" class="input">@foreach(\App\Models\Banner::POSITIONS as $k => $l)<option value="{{ $k }}" @selected(old('position', $banner->position) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div><label class="label" for="sort_order">Sort order</label><input id="sort_order" name="sort_order" type="number" min="0" class="input" value="{{ old('sort_order', $banner->sort_order ?? 0) }}"></div>
    <div><label class="label" for="button_text">Button text</label><input id="button_text" name="button_text" class="input" value="{{ old('button_text', $banner->button_text) }}" maxlength="60"></div>
    <div><label class="label" for="link_url">Link (path like /shop, or https://)</label><input id="link_url" name="link_url" class="input" value="{{ old('link_url', $banner->link_url) }}" maxlength="255"></div>
    <div><label class="label" for="starts_at">Show from (optional)</label><input id="starts_at" name="starts_at" type="datetime-local" class="input" value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i')) }}"></div>
    <div><label class="label" for="ends_at">Show until (optional)</label><input id="ends_at" name="ends_at" type="datetime-local" class="input" value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="sm:col-span-2">
        <label class="label" for="image">Image (min 800 px wide, JPG/PNG/WebP, max 3 MB)</label>
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="input">
        @if($banner->image_url)<img src="{{ $banner->image_url }}" alt="" class="mt-3 h-32 border border-line object-cover">@endif
    </div>
    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="accent-[#C8A24D]" @checked(old('is_active', $banner->is_active))> Active</label>
    <div class="flex gap-3 sm:col-span-2"><button class="btn-gold">Save banner</button><a href="{{ route('admin.banners.index') }}" class="btn-ghost">Cancel</a></div>
</form>
@endsection
