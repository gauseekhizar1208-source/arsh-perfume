@extends('layouts.app')
@section('robots', 'noindex, nofollow')

@section('title', 'Choose a New Password | Arsh Perfume')

@section('content')
<div class="container-x py-16">
    <form method="post" action="{{ route('password.store') }}" class="card mx-auto max-w-md space-y-5 p-8">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <h1 class="section-title text-center">New password</h1>
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="input" value="{{ old('email', $email) }}" required autocomplete="username">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="password">New password</label><input id="password" name="password" type="password" class="input" required autocomplete="new-password">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password"></div>
        <button class="btn-gold w-full">Reset password</button>
    </form>
</div>
@endsection
