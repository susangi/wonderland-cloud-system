# booking-service

Time slots, bookings, payment flow, circuit breaker and transactional outbox for Wonderland (design 6.3, 7.2, 7.3). Database: `booking_db`.

## Run locally

```
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8003
php artisan schedule:work      (second window: outbox:flush every minute, bookings:expire-pending every 5 minutes)
```

Needs pricing-service (8002), payment-service (8004) and event-service (8005). URLs come from `PRICING_URL`, `PAYMENT_URL`, `EVENT_URL`.

## Endpoints

| Method | Path | Auth |
|---|---|---|
| GET | /up | Public (Kubernetes probes) |
| GET | /api/health | Public (includes `payment_circuit`) |
| GET | /api/time-slots?date=YYYY-MM-DD | Public |
| POST | /api/bookings | User |
| POST | /api/bookings/{reference}/pay | User (owner) |
| GET | /api/bookings/me | User |
| GET | /api/bookings/{reference} | User (owner) or Admin |
| GET | /api/admin/bookings?date= | Admin |

## Commands

| Command | Schedule |
|---|---|
| `php artisan outbox:flush` | every minute |
| `php artisan bookings:expire-pending` | every 5 minutes |

## Tests

```
php artisan test
```
