<?php

use App\Models\Notification;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

if (!function_exists('paystackKey')) {
    function paystackKey($type = 'public')
    {
        // Fetch Paystack credentials from the database
        $paystack = PaymentMethod::where('slug', 'paystack')->first();

        if (!$paystack) {
            return null; // Return null if not found
        }

        return $paystack->mode === 'live'
            ? ($type === 'public' ? $paystack->live_public_key : $paystack->live_secret_key)
            : ($type === 'public' ? $paystack->test_public_key : $paystack->test_secret_key);
    }
}

if (!function_exists('store_notification')) {
    
    function store_notification(int $userId, string $title, string $content, string $type = 'info', string $category = 'system', int $createdBy = null ): Notification {
        return Notification::create([
            'user_id'    => $userId,
            'title'      => $title,
            'content'    => $content,
            'type'       => $type,
            'category'   => $category,
            'created_by' => $createdBy ?? null,
            'reference'  => strtoupper(Str::random(10)) . '-' . time(),
        ]);
    }
}

