@extends('account.layout')

@section('account')
<h1 class="section-title">Wishlist</h1>
@if($products->isEmpty())
    <p class="mt-6 text-sm text-muted">Your wishlist is empty. Tap the heart on any fragrance to save it here.</p>
@else
    <div class="mt-8 grid grid-cols-2 gap-5 xl:grid-cols-3">
        @foreach($products as $product)
            <div class="space-y-2">
                <x-product-card :product="$product" />
                <form method="post" action="{{ route('wishlist.toggle', $product) }}">@csrf<button class="text-xs text-muted hover:text-danger">Remove from wishlist</button></form>
            </div>
        @endforeach
    </div>
@endif
@endsection
