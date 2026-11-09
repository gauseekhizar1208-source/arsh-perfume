@extends('layouts.app')

@section('title', 'Frequently Asked Questions | Arsh Perfume')

@section('content')
<div class="container-x service-page">
    <x-page-heading title="Frequently Asked Questions" eyebrow="A LITTLE CLARITY" description="Everything you’d like to know about your fragrance and shopping with Arsh." />
    <div class="help-layout">
        <x-help-navigation />
        <div class="faq-content">
        @if($faqs->isEmpty())
            <p class="mt-8 text-sm text-muted">Our FAQ is being prepared. <a class="text-gold hover:underline" href="{{ route('contact') }}">Ask us anything</a> in the meantime.</p>
        @else
            <div class="faq-list space-y-3">
                @foreach($faqs as $faq)
                    <details class="card group p-5">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 font-medium">{{ $faq->question }}<span aria-hidden="true" class="text-gold transition group-open:rotate-45">+</span></summary>
                        <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-muted">{{ $faq->answer }}</p>
                    </details>
                @endforeach
            </div>
        @endif
        <div class="service-callout"><div><h2>Still have a question?</h2><p>We’d love to help you find the answer.</p></div><a href="{{ route('contact') }}" class="btn-ghost">Contact us &rarr;</a></div>
        </div>
    </div>
</div>
@endsection
