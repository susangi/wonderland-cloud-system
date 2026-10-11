<?php

namespace App\Repositories;

use App\Models\OutboxEvent;
use Illuminate\Database\Eloquent\Collection;

// All database queries for outbox events are kept here.
class OutboxEventRepository
{
    public function create(string $type, array $payload): OutboxEvent
    {
        return OutboxEvent::create(['type' => $type, 'payload' => $payload]);
    }

    // Events not sent yet that still have tries left. Oldest first, so events arrive in order.
    public function listUnsent(int $limit): Collection
    {
        return OutboxEvent::whereNull('sent_at')
            ->where('attempts', '<', OutboxEvent::MAX_ATTEMPTS)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function markSent(OutboxEvent $event): void
    {
        $event->update([
            'attempts' => $event->attempts + 1,
            'sent_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markFailed(OutboxEvent $event, string $error): void
    {
        $event->update([
            'attempts' => $event->attempts + 1,
            'last_error' => $error,
        ]);
    }
}
