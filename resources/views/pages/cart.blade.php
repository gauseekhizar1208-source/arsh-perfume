@extends('layouts.app')
@section('robots', 'noindex, nofollow')

@section('title', 'Your Cart | Arsh Perfume')

@section('content')
<div class="container-x py-12">
    <h1 class="section-title">Your Cart</h1>

    @if($summary['is_empty'])
        <div class="card mt-10 p-12 text-center">
            <p class="font-display text-xl">Your cart is empty.</p>
            <a href="{{ route('shop') }}" class="btn-gold mt-6">Continue shopping</a>
        </div>
    @else
        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_380px]">
            <ul class="divide-y divide-line">
                @foreach($summary['lines'] as $line)
                    <li class="flex gap-5 py-6">
                        <a href="{{ $line['url'] ?? '#' }}" class="h-28 w-28 shrink-0 overflow-hidden border border-line bg-panel">
                            <img src="{{ $line['image_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        </a>
                        <div class="flex flex-1 flex-col gap-2">
                            <div class="flex justify-between gap-4">
                                <div>
                                    <p class="font-display">{{ $line['name'] }}</p>
                                    <p class="text-xs text-muted">{{ $line['variant_name'] }}</p>
                                </div>
                                <p class="text-gold">{{ money($line['line_total']) }}</p>
                            </div>
                            <p class="text-xs text-muted">{{ money($line['unit_price']) }} each</p>
                            @if($line['issue'])<p class="alert-error py-2 text-xs">{{ $line['issue'] }}. Please update or remove this item to check out.</p>@endif

                            <div class="mt-auto flex flex-wrap items-center gap-4">
                                @if($line['item']->exists && $line['variant'])
                                <form method="post" action="{{ route('cart.items.update', $line['item']) }}" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <label for="q-{{ $line['item']->id }}" class="sr-only">Quantity</label>
                                    <input id="q-{{ $line['item']->id }}" name="quantity" type="number" min="0" max="{{ max(1, $line['max_quantity']) }}" value="{{ $line['quantity'] }}" class="input w-20 py-2">
                                    <button class="btn-ghost btn-sm">Update</button>
                                </form>
                                @endif
                                <form method="post" action="{{ route('cart.items.destroy', $line['item']) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-muted hover:text-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="card h-fit space-y-5 p-6" aria-labelledby="sum">
                <h2 id="sum" class="font-display text-lg">Order Summary</h2>

                <form method="post" action="{{ route('cart.coupon.apply') }}" class="space-y-2">
                    @csrf
                    <label for="coupon" class="label">Coupon code</label>
                    <div class="flex gap-2">
                        <input id="coupon" name="code" class="input" value="{{ $summary['coupon']?->code }}" placeholder="ENTER CODE" autocomplete="off">
                        <button class="btn-ghost btn-sm">Apply</button>
                    </div>
                    @error('code')<p class="field-error">{{ $message }}</p>@enderror
                    @if(session('coupon_error'))<p class="field-error">{{ session('coupon_error') }}</p>@endif
                    @if($summary['coupon'])
                        <p class="text-xs text-ok">Coupon {{ $summary['coupon']->code }} applied.
                            <button form="remove-coupon" class="ml-2 text-muted underline">Remove</button></p>
                    @endif
                </form>
                <form id="remove-coupon" method="post" action="{{ route('cart.coupon.remove') }}">@csrf @method('DELETE')</form>

                @php($t = $summary['totals'])
                <dl class="space-y-2 border-t border-line pt-5 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ money($t['subtotal']) }}</dd></div>
                    @if($t['discount_paise'] > 0)
                        <div class="flex justify-between text-ok"><dt>Discount</dt><dd>&minus;{{ money($t['discount']) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd>{{ $t['shipping_paise'] > 0 ? money($t['shipping']) : 'Free' }}</dd></div>
                    @if($t['free_shipping_remaining'])
                        <p class="text-xs text-muted">Add {{ money($t['free_shipping_remaining']) }} more for free shipping.</p>
                    @endif
                    @if($t['tax_enabled'])
                        <div class="flex justify-between"><dt class="text-muted">{{ $t['prices_include_tax'] ? 'GST included' : 'GST' }}</dt><dd>{{ money($t['tax']) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-line pt-3 text-base"><dt>Total payable</dt><dd class="text-gold">{{ money($t['total']) }}</dd></div>
                </dl>

                @if(count($summary['issues']))
                    <p class="alert-error text-xs">Some items need attention before checkout.</p>
                    <a href="{{ route('cart.index') }}" class="btn-ghost w-full">Review cart</a>
                @else
                    <a href="{{ route('checkout.show') }}" class="btn-gold w-full">Proceed to Checkout</a>
                @endif
                <a href="{{ route('shop') }}" class="block text-center text-xs text-muted hover:text-gold">Continue shopping</a>
            </aside>
        </div>
    @endif
</div>
@endsection
