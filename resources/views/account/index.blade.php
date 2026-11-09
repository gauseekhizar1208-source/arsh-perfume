@extends('account.layout')

@section('account')
<h1 class="section-title">Hello, {{ $user->name }}</h1>
<div class="mt-6 grid gap-4 sm:grid-cols-2">
    <div class="card p-5"><p class="label">Total purchases</p><p class="font-display text-2xl text-gold">{{ money($lifetime) }}</p></div>
    <div class="card p-5"><p class="label">Orders</p><p class="font-display text-2xl">{{ $user->orders()->count() }}</p></div>
</div>

<section class="mt-10" aria-labelledby="recent">
    <h2 id="recent" class="font-display text-lg">Recent orders</h2>
    @if($recentOrders->isEmpty())
        <p class="mt-3 text-sm text-muted">No orders yet. <a class="text-gold hover:underline" href="{{ route('shop') }}">Start shopping</a>.</p>
    @else
        <ul class="card mt-4 divide-y divide-line">
            @foreach($recentOrders as $o)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                    <span>{{ $o->order_number }} &middot; {{ $o->placed_at?->format('d M Y') }} &middot; {{ money($o->grand_total) }}</span>
                    <span class="flex items-center gap-4"><span class="badge-gold">{{ $o->status_label }}</span><a class="text-gold hover:underline" href="{{ route('account.orders.show', $o) }}">View</a></span>
                </li>
            @endforeach
        </ul>
    @endif
</section>

<section class="mt-10 grid gap-8 lg:grid-cols-2" id="profile">
    <form method="post" action="{{ route('account.profile.update') }}" class="card space-y-4 p-6">
        @csrf @method('PATCH')
        <h2 class="font-display text-lg">Profile</h2>
        <div><label class="label" for="p-name">Name</label><input id="p-name" name="name" class="input" value="{{ old('name', $user->name) }}" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="p-email">Email</label><input id="p-email" class="input opacity-60" value="{{ $user->email }}" disabled></div>
        <div><label class="label" for="p-phone">Phone</label><input id="p-phone" name="phone" class="input" value="{{ old('phone', $user->phone) }}"></div>
        <button class="btn-gold btn-sm">Save profile</button>
    </form>

    <form method="post" action="{{ route('account.password.update') }}" class="card space-y-4 p-6">
        @csrf @method('PUT')
        <h2 class="font-display text-lg">{{ $user->password_login_enabled ? 'Change password' : 'Add email sign-in' }}</h2>
        @if($user->password_login_enabled)
            <div><label class="label" for="cp">Current password</label><input id="cp" name="current_password" type="password" class="input" required autocomplete="current-password">@error('current_password')<p class="field-error">{{ $message }}</p>@enderror</div>
        @else
            <p class="text-sm text-muted">You sign in with Google. Optionally add a password to also sign in with your email address.</p>
        @endif
        <div><label class="label" for="np">New password</label><input id="np" name="password" type="password" class="input" required autocomplete="new-password">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="npc">Confirm new password</label><input id="npc" name="password_confirmation" type="password" class="input" required autocomplete="new-password"></div>
        <p class="text-xs text-muted">At least 8 characters with letters and numbers.</p>
        <button class="btn-gold btn-sm">{{ $user->password_login_enabled ? 'Change password' : 'Set password' }}</button>
    </form>
</section>

<section class="card mt-8 space-y-4 p-6" aria-labelledby="google-connection-title">
    <h2 id="google-connection-title" class="font-display text-lg">Google sign-in</h2>
    @if($user->google_id)
        <p class="text-sm text-ok">Google is connected to your account.</p>
        <p class="text-xs text-muted">Use Continue with Google to sign in with your connected Google account.</p>
    @elseif(app(\App\Services\GoogleSignInService::class)->isAvailable())
        <p class="text-sm text-muted">Connect the Google account that uses {{ $user->email }}. Confirm your current password first.</p>
        <form method="post" action="{{ route('account.google.connect') }}" class="max-w-md space-y-4">@csrf<div><label for="google-password" class="label">Current password</label><input id="google-password" type="password" name="google_password" required autocomplete="current-password" class="input">@error('google_password')<p class="field-error">{{ $message }}</p>@enderror</div><button type="submit" class="btn-outline">Connect Google account</button></form>
    @else
        <p class="text-sm text-muted">Google sign-in is not available yet.</p>
    @endif
</section>

<section class="mt-10" aria-labelledby="addr">
    <h2 id="addr" class="font-display text-lg">Address book</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @foreach($user->addresses as $a)
            <div class="card p-5 text-sm">
                <p class="font-medium">{{ $a->label ?: 'Address' }} @if($a->is_default)<span class="badge-gold ml-2">Default</span>@endif</p>
                <p class="mt-2 text-muted">{{ $a->name }} &middot; {{ $a->phone }}</p>
                <p class="text-muted">{{ $a->oneLine() }}</p>
                <form method="post" action="{{ route('account.addresses.destroy', $a) }}" class="mt-3">@csrf @method('DELETE')<button class="text-xs text-muted hover:text-danger">Remove</button></form>
            </div>
        @endforeach
    </div>

    <details class="card mt-6 p-6">
        <summary class="cursor-pointer font-medium">Add a new address</summary>
        <form method="post" action="{{ route('account.addresses.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            @csrf
            <div><label class="label" for="a-label">Label</label><input id="a-label" name="label" class="input" placeholder="Home"></div>
            <div><label class="label" for="a-name">Full name</label><input id="a-name" name="name" class="input" required></div>
            <div><label class="label" for="a-phone">Phone</label><input id="a-phone" name="phone" class="input" required></div>
            <div><label class="label" for="a-pin">PIN code</label><input id="a-pin" name="postal_code" class="input" maxlength="6" required></div>
            <div class="sm:col-span-2"><label class="label" for="a-l1">Address line 1</label><input id="a-l1" name="address_line1" class="input" required></div>
            <div class="sm:col-span-2"><label class="label" for="a-l2">Address line 2</label><input id="a-l2" name="address_line2" class="input"></div>
            <div><label class="label" for="a-city">City</label><input id="a-city" name="city" class="input" required></div>
            <div><label class="label" for="a-state">State</label>
                <select id="a-state" name="state" class="input" required>
                    <option value="">Select</option>
                    @foreach(config('arsh.states') as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_default" value="1" class="accent-[#C8A24D]"> Make this my default address</label>
            <div class="sm:col-span-2"><button class="btn-gold btn-sm">Save address</button></div>
        </form>
    </details>
</section>
@endsection
