@extends('layouts.admin')

@php($isNew = ! $faq->exists)
@section('title', $isNew ? 'New FAQ' : 'Edit FAQ')
@section('heading', $isNew ? 'New FAQ' : 'Edit FAQ')

@section('content')
<form method="post" class="card max-w-2xl space-y-5 p-6" action="{{ $isNew ? route('admin.faqs.store') : route('admin.faqs.update', $faq) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless
    <div><label class="label" for="question">Question</label><input id="question" name="question" class="input" value="{{ old('question', $faq->question) }}" required maxlength="255"></div>
    <div><label class="label" for="answer">Answer</label><textarea id="answer" name="answer" rows="6" class="input" required maxlength="3000">{{ old('answer', $faq->answer) }}</textarea></div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div><label class="label" for="sort_order">Sort order</label><input id="sort_order" name="sort_order" type="number" min="0" class="input" value="{{ old('sort_order', $faq->sort_order ?? 0) }}"></div>
        <label class="flex items-center gap-2 self-end text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="accent-[#C8A24D]" @checked(old('is_active', $faq->is_active ?? true))> Show on website</label>
    </div>
    <div class="flex gap-3"><button class="btn-gold">Save FAQ</button><a href="{{ route('admin.faqs.index') }}" class="btn-ghost">Cancel</a></div>
</form>
@endsection
