# payment-service

Mock payment provider for Wonderland (design 6.4). Database: `payment_db`.

## Run locally

```
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8004
```

## Endpoints

| Method | Path | Auth |
|---|---|---|
| GET | /up | Public (Kubernetes probes) |
| GET | /api/health | Public |
| POST | /api/internal/payments | Internal (`X-Internal-Token`) – called by booking-service |
| GET | /api/payments/{payment_id} | Admin |

## Mock rules

- Card must be 16 digits and pass the Luhn check, else 422.
- `4242…` → SUCCESS. `4000…` → FAILED ("Card declined"). Any other valid card → SUCCESS.
- `PAYMENT_SIMULATE_DOWN=true` → every payment returns 503 (circuit breaker demo).
- `PAYMENT_DELAY_MS=2000` → waits 2 seconds before answering.
- Only the last 4 digits are stored. The full card number and CVC are never saved or logged.

## Tests

```
php artisan test
```
