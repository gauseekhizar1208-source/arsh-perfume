@extends('layouts.app')
@section('robots', 'noindex, nofollow')

@section('title', 'Create Account | Arsh Perfume')

@section('content')
<div class="container-x py-16">
    <form method="post" action="{{ route('register') }}" class="card mx-auto max-w-md space-y-5 p-8" novalidate>
        @csrf
        <h1 class="section-title text-center">Create account</h1>
        <x-google-sign-in />
        <div><label class="label" for="name">Full name</label><input id="name" name="name" class="input" value="{{ old('name') }}" required autocomplete="name">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="email">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="phone">Mobile (optional)</label><input id="phone" name="phone" class="input" value="{{ old('phone') }}" autocomplete="tel">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="password">Password</label><input id="password" name="password" type="password" class="input" required autocomplete="new-password">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password"></div>
        <p class="text-xs text-muted">At least 8 characters, with letters and numbers.</p>
        <button class="btn-gold w-full">Create account</button>
        <p class="text-center text-sm text-muted">Already registered? <a class="text-gold hover:underline" href="{{ route('login') }}">Sign in</a></p>
    </form>
</div>
@endsection
