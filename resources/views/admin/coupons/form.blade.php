@extends('layouts.admin')

@php($isNew = ! $coupon->exists)
@section('title', $isNew ? 'New coupon' : 'Edit coupon')
@section('heading', $isNew ? 'New coupon' : 'Edit '.$coupon->code)

@section('content')
<form method="post" class="card grid max-w-4xl gap-5 p-6 sm:grid-cols-2"
      action="{{ $isNew ? route('admin.coupons.store') : route('admin.coupons.update', $coupon) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless
    <div><label class="label" for="code">Code</label><input id="code" name="code" class="input font-mono uppercase" value="{{ old('code', $coupon->code) }}" required maxlength="50"></div>
    <div><label class="label" for="description">Internal description</label><input id="description" name="description" class="input" value="{{ old('description', $coupon->description) }}" maxlength="200"></div>
    <div><label class="label" for="type">Discount type</label>
        <select id="type" name="type" class="input">
            <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percentage (%)</option>
            <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed amount (₹)</option>
        </select></div>
    <div><label class="label" for="value">Value</label><input id="value" name="value" type="number" step="0.01" min="0.01" class="input" value="{{ old('value', $coupon->value) }}" required></div>
    <div><label class="label" for="min_order_amount">Minimum order (₹)</label><input id="min_order_amount" name="min_order_amount" type="number" step="0.01" min="0" class="input" value="{{ old('min_order_amount', $coupon->min_order_amount) }}"></div>
    <div><label class="label" for="max_discount">Maximum discount (₹)</label><input id="max_discount" name="max_discount" type="number" step="0.01" min="0" class="input" value="{{ old('max_discount', $coupon->max_discount) }}"></div>
    <div><label class="label" for="starts_at">Starts</label><input id="starts_at" name="starts_at" type="datetime-local" class="input" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"></div>
    <div><label class="label" for="expires_at">Expires</label><input id="expires_at" name="expires_at" type="datetime-local" class="input" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}"></div>
    <div><label class="label" for="usage_limit">Total usage limit</label><input id="usage_limit" name="usage_limit" type="number" min="1" class="input" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="Unlimited"></div>
    <div><label class="label" for="per_user_limit">Uses per customer</label><input id="per_user_limit" name="per_user_limit" type="number" min="1" class="input" value="{{ old('per_user_limit', $coupon->per_user_limit) }}" placeholder="Unlimited"></div>

    <fieldset class="sm:col-span-2">
        <legend class="label">Restrict to products (leave empty for all)</legend>
        <select name="product_ids[]" multiple size="6" class="input">
            @foreach($products as $p)<option value="{{ $p->id }}" @selected(in_array($p->id, (array) old('product_ids', $selectedProducts)))>{{ $p->name }}</option>@endforeach
        </select>
    </fieldset>
    <fieldset class="sm:col-span-2">
        <legend class="label">Restrict to categories (leave empty for all)</legend>
        <select name="category_ids[]" multiple size="4" class="input">
            @foreach($categories as $c)<option value="{{ $c->id }}" @selected(in_array($c->id, (array) old('category_ids', $selectedCategories)))>{{ $c->name }}</option>@endforeach
        </select>
    </fieldset>

    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="accent-[#C8A24D]" @checked(old('is_active', $coupon->is_active))> Active</label>
    <div class="flex gap-3 sm:col-span-2"><button class="btn-gold">{{ $isNew ? 'Create coupon' : 'Save coupon' }}</button><a href="{{ route('admin.coupons.index') }}" class="btn-ghost">Cancel</a></div>
</form>
@endsection
