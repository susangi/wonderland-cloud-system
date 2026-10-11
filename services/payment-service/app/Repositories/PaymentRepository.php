<?php

namespace App\Repositories;

use App\Models\Payment;

// All database queries for payments are kept here.
class PaymentRepository
{
    public function create(array $attributes): Payment
    {
        return Payment::create($attributes);
    }

    public function findByPaymentId(string $paymentId): ?Payment
    {
        return Payment::where('payment_id', $paymentId)->first();
    }
}
