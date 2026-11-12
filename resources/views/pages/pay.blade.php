@extends('layouts.app')
@section('robots', 'noindex, nofollow')

@section('title', 'Complete Payment | Arsh Perfume')

@section('content')
<div class="container-x py-16">
    <div class="card mx-auto max-w-lg space-y-6 p-8 text-center">
        <h1 class="section-title">Complete your payment</h1>
        <p class="text-sm text-muted">Order <span class="text-white">{{ $order->order_number }}</span> &middot; Amount <span class="text-gold">{{ money($order->grand_total) }}</span></p>

        <p id="pay-status" class="text-sm text-muted" role="status" aria-live="polite">Opening secure payment window&hellip;</p>
        <button id="pay-btn" type="button" class="btn-gold w-full" hidden>Pay {{ money($order->grand_total) }}</button>
        @if($mode === 'simulated')
            <p class="alert-info text-left text-xs">Test mode: no real money is charged. Add Razorpay test keys in .env to use Razorpay's sandbox.</p>
        @endif
        <a href="{{ route('checkout.show') }}" class="block text-xs text-muted hover:text-gold">Cancel and go back</a>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const verifyUrl = @js(route('checkout.verify', $order));
        const failedUrl = @js(route('checkout.failed', $order));
        const simulateUrl = @js(route('checkout.simulate', $order));
        const mode = @js($mode);
        const status = document.getElementById('pay-status');
        const btn = document.getElementById('pay-btn');

        const post = (url, body) => fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body || {}),
            credentials: 'same-origin',
        }).then(r => r.json().then(j => ({ ok: r.ok, body: j })));

        const finish = (res) => {
            if (res.ok && res.body.ok) { window.location = res.body.redirect; return; }
            status.textContent = res.body.message || 'Payment could not be verified. If money was deducted it will be refunded automatically.';
            btn.hidden = false;
        };

        // Simulated gateway: runs the same server-side verification as the real gateway.
        if (mode === 'simulated') {
            btn.hidden = false;
            btn.textContent = 'Simulate successful payment';
            btn.addEventListener('click', () => post(simulateUrl).then(finish));
            status.textContent = 'Test mode ready.';
            return;
        }

        if (mode !== 'live') {
            status.textContent = 'Online payment is not available. Please go back and choose Cash on Delivery.';
            return;
        }

        const open = () => {
            if (!window.Razorpay) { status.textContent = 'Loading payment window…'; return setTimeout(open, 300); }
            const rzp = new window.Razorpay({
                key: @js($keyId),
                amount: {{ (int) $amountPaise }},
                currency: 'INR',
                name: @js(setting('business_name', 'Arsh Perfume')),
                description: @js('Order '.$order->order_number),
                order_id: @js($payment->provider_order_id),
                prefill: { name: @js($order->name), email: @js($order->email), contact: @js($order->phone) },
                theme: { color: '#C8A24D' },
                handler: (resp) => {
                    status.textContent = 'Verifying your payment…';
                    post(verifyUrl, {
                        razorpay_order_id: resp.razorpay_order_id,
                        razorpay_payment_id: resp.razorpay_payment_id,
                        razorpay_signature: resp.razorpay_signature,
                    }).then(finish);
                },
                modal: { ondismiss: () => { status.textContent = 'Payment window closed. You can try again.'; btn.hidden = false; } },
            });
            rzp.on('payment.failed', (r) => {
                post(failedUrl, { code: r.error.code, description: r.error.description });
                status.textContent = 'Payment failed: ' + r.error.description + '. You can try again.';
                btn.hidden = false;
            });
            btn.hidden = false;
            status.textContent = 'Ready to pay.';
            btn.onclick = () => rzp.open();
            rzp.open();
        };
        open();
    })();
</script>
@endsection
