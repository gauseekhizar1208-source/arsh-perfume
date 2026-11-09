@extends('layouts.app')

@section('title', 'Track Your Order | Arsh Perfume')

@section('content')
<div class="container-x service-page">
    <x-page-heading title="Track Your Order" eyebrow="YOUR FRAGRANCE JOURNEY" description="Follow your order, from our fragrance house to your doorstep." />
    <div class="tracking-layout">
    <div class="tracking-content">

    <form method="post" action="{{ route('track-order.lookup') }}" class="card tracking-form grid gap-5">
        @csrf
        <div><label class="label" for="order_number">Order number</label><input id="order_number" name="order_number" class="input" placeholder="AP..." value="{{ old('order_number') }}" required aria-describedby="order-number-help"><p id="order-number-help" class="mt-2 text-xs text-muted">Find this in your order confirmation.</p>@error('order_number')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="email">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
        <button class="btn-gold">Track my order &rarr;</button>
    </form>

    @if(isset($notFound) && $notFound)
        <p class="alert-error mt-8 max-w-2xl">We could not find an order matching those details. Check the order number and email, or <a class="underline" href="{{ route('contact') }}">contact us</a>.</p>
    @endif

    @if($order)
        <section class="card mt-10 max-w-3xl space-y-8 p-6 sm:p-8" aria-labelledby="ord-h">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 id="ord-h" class="font-display text-xl">{{ $order->order_number }}</h2>
                    <p class="text-xs text-muted">Placed {{ $order->placed_at?->format('d M Y, h:i A') }}</p>
                </div>
                <span class="{{ $order->status === 'cancelled' || $order->status === 'returned' ? 'badge-bad' : 'badge-gold' }}">{{ $order->status_label }}</span>
            </header>

            @if(! in_array($order->status, ['cancelled', 'returned']))
                <ol class="grid grid-cols-3 gap-2 text-center text-[11px] uppercase tracking-wider sm:grid-cols-6" aria-label="Progress">
                    @php($steps = $order->timelineSteps())
                    @foreach($steps as $i => $step)
                        @php($reached = array_search($order->status, $steps, true) >= $i)
                        <li class="{{ $reached ? 'text-gold' : 'text-muted/50' }}">
                            <span class="mx-auto mb-2 block h-1 {{ $reached ? 'bg-gold' : 'bg-line' }}"></span>
                            {{ config("arsh.order_statuses.$step") }}
                        </li>
                    @endforeach
                </ol>
            @endif

            @if($order->tracking_number)
                <p class="text-sm">Courier: <span class="text-white">{{ $order->courier_name ?: '—' }}</span> &middot; Tracking number: <span class="text-white">{{ $order->tracking_number }}</span>
                    @if($order->tracking_url) &middot; <a class="text-gold hover:underline" href="{{ $order->tracking_url }}" rel="noopener" target="_blank">Track with courier</a>@endif
                </p>
            @endif

            <ol class="space-y-3 border-l border-line pl-5 text-sm">
                @foreach($order->statusHistories->reverse() as $h)
                    <li>
                        <p class="font-medium">{{ config("arsh.order_statuses.{$h->to_status}", $h->to_status) }}</p>
                        <p class="text-xs text-muted">{{ $h->created_at->format('d M Y, h:i A') }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if($myOrders->isNotEmpty())
        <section class="mt-12" aria-labelledby="mine">
            <h2 id="mine" class="font-display text-lg">Your recent orders</h2>
            <ul class="mt-4 divide-y divide-line card">
                @foreach($myOrders as $o)
                    <li class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                        <span>{{ $o->order_number }} &middot; {{ $o->status_label }}</span>
                        <a class="text-gold hover:underline" href="{{ route('account.orders.show', $o) }}">View</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
    </div>
    <aside class="tracking-help"><p class="eyebrow">GOOD TO KNOW</p><h2>Find your order.</h2><p>Use your order number and the same email address you entered at checkout.</p><p>Looking for delivery information?</p><a href="{{ route('pages.shipping') }}">Read our shipping policy &rarr;</a><p>Need a hand with your order?</p><a href="{{ route('contact') }}">Contact customer care &rarr;</a></aside>
    </div>
</div>
@endsection
