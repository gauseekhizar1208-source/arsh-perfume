@extends('layouts.app')

@section('title', 'Sign In | Arsh Perfume')

@section('content')
<div class="container-x py-16">
    <form method="post" action="{{ route('login') }}" class="card mx-auto max-w-md space-y-5 p-8" novalidate>
        @csrf
        <h1 class="section-title text-center">Sign in</h1>
        <x-google-sign-in />
        @if(session('status'))<p class="alert-ok">{{ session('status') }}</p>@endif
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" name="email" type="email" class="input @error('email') input-error @enderror" value="{{ old('email') }}" required autofocus autocomplete="email" @error('email') aria-describedby="email-err" @enderror>
            @error('email')<p id="email-err" class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input id="password" name="password" type="password" class="input" required autocomplete="current-password">
        </div>
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-muted"><input type="checkbox" name="remember" value="1" class="accent-[#C8A24D]"> Remember me</label>
            <a href="{{ route('password.request') }}" class="text-gold hover:underline">Forgot password?</a>
        </div>
        <button class="btn-gold w-full">Sign in</button>
        <p class="text-center text-sm text-muted">New here? <a class="text-gold hover:underline" href="{{ route('register') }}">Create an account</a></p>
    </form>
</div>
@endsection
