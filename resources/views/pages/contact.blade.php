@extends('layouts.app')

@section('title', 'Contact Us | Arsh Perfume')
@section('meta_description', 'Get in touch with Arsh Perfume for orders, fragrance advice, bulk enquiries or help with delivery.')

@section('content')
<div class="container-x service-page">
    <x-page-heading title="Contact Us" eyebrow="LET?S TALK FRAGRANCE" description="A question about your order, or help finding your next favourite scent? We?re here for you." />
    <div class="contact-layout">
    <div class="contact-details">
        <h2>We?d love to hear from you.</h2>
        <div class="mt-8 space-y-4 text-sm text-muted">
            @if(setting('business_address'))<p class="whitespace-pre-line"><span class="label block">Address</span>{{ setting('business_address') }}</p>@endif
            @if(setting('contact_email'))<p><span class="label block">Email</span><a class="text-white hover:text-gold" href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></p>@endif
            @if(setting('contact_phone'))<p><span class="label block">Phone</span><a class="text-white hover:text-gold" href="tel:{{ setting('contact_phone') }}">{{ setting('contact_phone') }}</a></p>@endif
            @if(setting('whatsapp_number'))<p><span class="label block">WhatsApp</span><a class="text-white hover:text-gold" target="_blank" rel="noopener" href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp_number')) }}">{{ setting('whatsapp_number') }}</a></p>@endif
            @if(setting('business_hours'))<p><span class="label block">Hours</span>{{ setting('business_hours') }}</p>@endif
        </div>
    </div>

    <form method="post" action="{{ route('contact.store') }}" class="card contact-form space-y-5" novalidate>
        @csrf
        <h2>Send us a message</h2>
        <p class="form-intro">Tell us how we can help. Name, email and message are required.</p>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="label" for="c-name">Name</label><input id="c-name" name="name" class="input" required value="{{ old('name', auth()->user()?->name) }}" autocomplete="name">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="c-email">Email</label><input id="c-email" name="email" type="email" class="input" required value="{{ old('email', auth()->user()?->email) }}" autocomplete="email">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="c-phone">Phone (optional)</label><input id="c-phone" name="phone" type="tel" class="input" value="{{ old('phone') }}" autocomplete="tel">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="c-subject">Subject</label><input id="c-subject" name="subject" class="input" value="{{ old('subject') }}">@error('subject')<p class="field-error">{{ $message }}</p>@enderror</div>
        </div>
        <div><label class="label" for="c-message">Message</label><textarea id="c-message" name="message" rows="5" class="input" required minlength="10">{{ old('message') }}</textarea>@error('message')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div class="hidden" aria-hidden="true"><label>Leave empty<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <button class="btn-gold">Send message</button>
    </form>
    </div>
    <div class="service-callout"><div><h2>Looking for a quick answer?</h2><p>Explore our frequently asked questions or check your order.</p></div><a class="btn-ghost" href="{{ route('pages.faq') }}">Explore FAQ &rarr;</a></div>
</div>
@endsection
