@extends('layouts.app')
@section('robots', 'noindex, nofollow')

@section('title', 'Reset Password | Arsh Perfume')

@section('content')
<div class="container-x py-16">
    <form method="post" action="{{ route('password.email') }}" class="card mx-auto max-w-md space-y-5 p-8">
        @csrf
        <h1 class="section-title text-center">Forgot password</h1>
        <p class="text-sm text-muted">Enter your email and we will send a reset link.</p>
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="email">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
        <button class="btn-gold w-full">Send reset link</button>
    </form>
</div>
@endsection
