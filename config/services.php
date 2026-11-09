<?php

$razorpayKey = env('RAZORPAY_KEY_ID');
$razorpaySecret = env('RAZORPAY_KEY_SECRET');

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost:8000'), '/').'/auth/google/callback'),
    ],
    /*
     * Razorpay. Secrets live ONLY in .env - never in the database or in frontend code.
     *
     * "live" mode  : RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET are set (use rzp_test_* keys for gateway test mode).
     * "simulated"  : no keys are set AND the app is not in production. A built-in fake gateway lets you test the
     *                full checkout locally. The simulated gateway is impossible to enable in production.
     * "disabled"   : no keys in production -> online payment is not offered at checkout.
     */
    'razorpay' => [
        'key_id' => $razorpayKey,
        'key_secret' => $razorpaySecret,
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'api_base' => 'https://api.razorpay.com/v1',
        'currency' => 'INR',
        'simulate' => empty($razorpayKey) && empty($razorpaySecret) && env('APP_ENV', 'production') !== 'production',
    ],
];
