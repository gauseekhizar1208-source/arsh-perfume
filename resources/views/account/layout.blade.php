@extends('layouts.app')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container-x grid gap-10 py-12 lg:grid-cols-[220px_1fr]">
    <nav class="card h-fit p-3 text-sm" aria-label="Account">
        <p class="px-3 pb-3 pt-2 font-display text-base">My Account</p>
        <ul class="space-y-1">
            @foreach([
                ['account', 'Overview'], ['account.orders.index', 'Orders'], ['wishlist.index', 'Wishlist'],
                ['account', 'Profile &amp; addresses'],
            ] as [$route, $label])
                <li><a href="{{ route($route) }}#{{ $loop->last ? 'profile' : '' }}" class="block rounded-sm px-3 py-2 {{ request()->routeIs($route) ? 'bg-gold/15 text-gold' : 'text-muted hover:text-white' }}">{!! $label !!}</a></li>
            @endforeach
            <li>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="block w-full rounded-sm px-3 py-2 text-left text-muted hover:text-white">Sign out</button></form>
            </li>
        </ul>
    </nav>
    <div class="min-w-0">
        @yield('account')
    </div>
</div>
@endsection
