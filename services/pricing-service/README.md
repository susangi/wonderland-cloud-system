# pricing-service

Ticket tiers and prices for Wonderland (design 6.2). Database: `pricing_db`.

## Run locally

```
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8002
```

## Endpoints

| Method | Path | Auth |
|---|---|---|
| GET | /up | Public (Kubernetes probes) |
| GET | /api/health | Public |
| GET | /api/tiers | Public (admin may add `include_inactive=1`) |
| GET | /api/tiers/{id} | Public (also used by booking-service) |
| POST | /api/tiers | Admin |
| PUT | /api/tiers/{id} | Admin (name, min_guests, max_guests, price, is_active) |

Seed tiers (from the brief): Individual 1–1 USD 40, Couple 2–2 USD 75, Family 1–4 USD 140, Group 1–5 USD 165.

## Tests

```
php artisan test
```
