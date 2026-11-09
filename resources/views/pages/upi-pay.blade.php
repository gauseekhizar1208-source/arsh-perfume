@extends('layouts.app')
@section('title', 'Pay by UPI QR | Arsh Perfume')
@section('content')
<div class="container-x upi-page">
    <header class="upi-heading"><p class="eyebrow">ONE LAST STEP</p><h1 class="section-title">A scan away from <em>yours.</em></h1><p>Order {{ $order->order_number }} &middot; UPI payment</p></header>
    <div class="upi-payment-grid">
        <section class="card upi-qr-panel" aria-labelledby="upi-qr-title">
            <span class="upi-status-pill">{{ !empty($payment->meta['customer_reference']) ? 'Awaiting manual verification' : 'Awaiting payment' }}</span>
            <h2 id="upi-qr-title">Scan &amp; pay</h2><p class="upi-amount">{{ money($order->grand_total) }}</p>
            @if(empty($payment->meta['customer_reference']))
                <div class="upi-qr-wrap"><canvas data-upi-qr data-payment-uri="{{ $paymentUri }}" role="img" aria-label="UPI QR to pay {{ money($order->grand_total) }} to {{ $upiDetails['payee_name'] }}"></canvas><p data-qr-status role="status">Generating your payment QR&hellip;</p></div>
                <div class="upi-qr-actions"><a href="{{ $paymentUri }}" class="btn-gold">Open UPI app &nearr;</a><a data-qr-download download="{{ $order->order_number }}-upi.png" class="btn-outline" hidden>Save QR</a></div>
                <p class="upi-help">Scan with your UPI app. On the same phone, save the QR and choose the image from your UPI app's scanner.</p>
                <noscript><p class="upi-help">Use the UPI ID below to pay this amount, or open the UPI app link.</p></noscript>
            @else
                <div class="upi-submitted"><span aria-hidden="true">&#10003;</span><h3>Reference received</h3><p>Your reference has been submitted for review. Please do not pay again.</p></div>
            @endif
            <dl class="upi-payee"><div><dt>Paying to</dt><dd>{{ $upiDetails['payee_name'] }}</dd></div><div><dt>UPI ID</dt><dd class="break-all">{{ $upiDetails['upi_id'] }}</dd></div></dl>
        </section>
        <section class="card upi-reference-panel" aria-labelledby="upi-reference-title"><p class="eyebrow">AFTER YOU PAY</p><h2 id="upi-reference-title">Share your payment reference.</h2><p>After a successful payment, copy the transaction reference or UTR from your UPI app and submit it here.</p>
            <form method="post" action="{{ route('checkout.upi.reference', $order) }}" class="space-y-5">@csrf<div><label for="reference" class="label">Transaction reference / UTR</label><input id="reference" name="reference" class="input" required pattern="[A-Za-z0-9]{6,64}" minlength="6" maxlength="64" autocomplete="off" value="{{ old('reference', $payment->meta['customer_reference'] ?? '') }}" aria-describedby="reference-help @error('reference') reference-error @enderror"><p id="reference-help" class="upi-help">Enter the exact reference shown on the successful payment receipt (6&ndash;64 letters or digits).</p>@error('reference')<p id="reference-error" class="field-error">{{ $message }}</p>@enderror</div><button type="submit" class="btn-gold w-full">{{ !empty($payment->meta['customer_reference']) ? 'Update reference' : 'Submit payment reference' }}</button></form>
            <div class="upi-verification-note"><strong>What happens next?</strong><p>We check that the full amount reached our receiving account. Your order stays pending until we verify it. Submitting a reference does not automatically confirm payment.</p></div><a href="{{ route('order.success', ['order' => $order->order_number]) }}" class="text-link">View order details &rarr;</a>
        </section>
    </div>
</div>
@endsection
