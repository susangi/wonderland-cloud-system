# event-service

Local ticket copy, gate check-in, ride scans, visit history, analytics and the sample data simulator for Wonderland (design 6.5, 12.2). Database: `event_db`.

## Run locally

```
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8005
```

Needs attraction-service (8001) for ride names (`ATTRACTION_URL`). Check-in works without booking-service (local `tickets` copy).

## Endpoints

| Method | Path | Auth |
|---|---|---|
| GET | /up | Public (Kubernetes probes) |
| GET | /api/health | Public |
| POST | /api/internal/events/purchase | Internal (`X-Internal-Token`) – booking-service outbox |
| POST | /api/checkins | Admin |
| POST | /api/ride-scans | Admin |
| GET | /api/visits/me | User |
| GET | /api/analytics/summary?from=&to= | Admin |

## Simulator (sample data, always `source = 'simulated'`, references start with `SIM-`)

```
php artisan simulate:history --days=30
php artisan simulate:tickets-today --count=50
php artisan simulate:burst --count=2000 --concurrency=50 --base-url=<api url> --token=<admin access token>
```

Without `--token`, `simulate:burst` sends the local admin headers (laptop only).

## Tests

```
php artisan test
```
