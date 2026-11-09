@extends('layouts.app')

@section('title', 'Checkout | Arsh Perfume')

@section('content')
@php($t = $summary['totals'])
<div class="container-x py-12">
    <h1 class="section-title">Checkout</h1>

    <form method="post" action="{{ route('checkout.store') }}" class="mt-10 grid gap-10 lg:grid-cols-[1fr_380px]" novalidate>
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

        <div class="space-y-10">
            <section class="card space-y-6 p-6 sm:p-8" aria-labelledby="contact-h">
                <h2 id="contact-h" class="font-display text-lg">Contact</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label class="label" for="name">Full name</label><input id="name" name="name" class="input @error('name') input-error @enderror" value="{{ old('name', auth()->user()?->name ?? '') }}" required autocomplete="name">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="input @error('email') input-error @enderror" value="{{ old('email', auth()->user()?->email ?? '') }}" required autocomplete="email">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="phone">Mobile number</label><input id="phone" name="phone" type="tel" class="input @error('phone') input-error @enderror" value="{{ old('phone', auth()->user()?->phone ?? '') }}" required autocomplete="tel" inputmode="tel">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                </div>
            </section>

            <section class="card space-y-6 p-6 sm:p-8" aria-labelledby="addr-h" x-data="{ pick: '' }">
                <h2 id="addr-h" class="font-display text-lg">Delivery address</h2>

                @if($addresses->isNotEmpty())
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($addresses as $a)
                            <button type="button" class="border border-line p-4 text-left text-sm hover:border-gold"
                                    data-address="{{ json_encode(['address_line1' => $a->address_line1, 'address_line2' => $a->address_line2, 'city' => $a->city, 'state' => $a->state, 'postal_code' => $a->postal_code, 'phone' => $a->phone]) }}"
                                    onclick="fillAddress(this)">
                                <span class="font-medium">{{ $a->label ?: 'Saved address' }}</span>
                                <span class="mt-1 block text-muted">{{ $a->oneLine() }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="label" for="address_line1">Address line 1</label><input id="address_line1" name="address_line1" class="input" value="{{ old('address_line1') }}" required autocomplete="address-line1">@error('address_line1')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label class="label" for="address_line2">Address line 2 (optional)</label><input id="address_line2" name="address_line2" class="input" value="{{ old('address_line2') }}" autocomplete="address-line2"></div>
                    <div><label class="label" for="city">City</label><input id="city" name="city" class="input" value="{{ old('city') }}" required autocomplete="address-level2">@error('city')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="state">State</label>
                        <select id="state" name="state" class="input" required autocomplete="address-level1">
                            <option value="">Select state</option>
                            @foreach($states as $s)<option value="{{ $s }}" @selected(old('state') === $s)>{{ $s }}</option>@endforeach
                        </select>@error('state')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="label" for="postal_code">PIN code</label><input id="postal_code" name="postal_code" inputmode="numeric" maxlength="6" class="input" value="{{ old('postal_code') }}" required autocomplete="postal-code">@error('postal_code')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label class="label" for="notes">Delivery notes (optional)</label><textarea id="notes" name="notes" rows="2" class="input" maxlength="500">{{ old('notes') }}</textarea></div>
                </div>
            </section>

            <section class="card space-y-4 p-6 sm:p-8" aria-labelledby="pay-h">
                <h2 id="pay-h" class="font-display text-lg">How your order is placed</h2>
                <input type="hidden" name="payment_method" value="whatsapp">
                <div class="flex items-start gap-3 border border-line p-4">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="#25D366" aria-hidden="true" class="mt-0.5 shrink-0"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.945C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.82 9.82 0 001.516 5.256l-.999 3.648 3.472-.903zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    <div class="space-y-1">
                        <span class="block font-medium">Order on WhatsApp</span>
                        <span class="block text-xs text-muted">No online payment needed. When you place the order, WhatsApp opens with your order details ready to send to us. We confirm price, delivery and payment with you directly on chat.</span>
                    </div>
                </div>
            </section>
        </div>

        <aside class="card h-fit space-y-5 p-6 lg:sticky lg:top-28" aria-labelledby="os-h">
            <h2 id="os-h" class="font-display text-lg">Order summary</h2>
            <ul class="divide-y divide-line text-sm">
                @foreach($summary['lines'] as $line)
                    <li class="flex justify-between gap-3 py-3">
                        <span>{{ $line['name'] }} <span class="text-muted">({{ $line['variant_name'] }}) &times; {{ $line['quantity'] }}</span></span>
                        <span>{{ money($line['line_total']) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-2 border-t border-line pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ money($t['subtotal']) }}</dd></div>
                @if($t['discount_paise'] > 0)<div class="flex justify-between text-ok"><dt>Coupon {{ $summary['coupon']?->code }}</dt><dd>&minus;{{ money($t['discount']) }}</dd></div>@endif
                <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd>{{ $t['shipping_paise'] > 0 ? money($t['shipping']) : 'Free' }}</dd></div>
                <div class="flex justify-between border-t border-line pt-3 text-base"><dt>Order total</dt><dd class="text-gold">{{ money($t['total']) }}</dd></div>
            </dl>
            <button class="btn-gold flex w-full items-center justify-center gap-2" @disabled(count($summary['issues']))>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.945C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.82 9.82 0 001.516 5.256l-.999 3.648 3.472-.903zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                Place order on WhatsApp
            </button>
            @if(count($summary['issues']))<p class="alert-error text-xs">Some items in your cart are unavailable. <a class="underline" href="{{ route('cart.index') }}">Review cart</a>.</p>@endif
            <p class="text-xs text-muted">By placing your order you agree to our <a class="text-gold hover:underline" href="{{ route('pages.terms') }}">terms</a> and <a class="text-gold hover:underline" href="{{ route('pages.cancellation') }}">cancellation policy</a>.</p>
        </aside>
    </form>
</div>

<script>
    function fillAddress(btn) {
        const d = JSON.parse(btn.dataset.address);
        ['address_line1', 'address_line2', 'city', 'state', 'postal_code', 'phone'].forEach(k => {
            const el = document.getElementById(k) || document.querySelector(`[name="${k}"]`);
            if (el) el.value = d[k] ?? '';
        });
    }
</script>
@endsection
