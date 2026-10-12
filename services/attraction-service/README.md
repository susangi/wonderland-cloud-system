# attraction-service

Rides and activities catalogue for Wonderland (design 6.1). Database: `attraction_db`.

## Run locally

```
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001
```

## Endpoints

| Method | Path | Auth |
|---|---|---|
| GET | /up | Public (Kubernetes probes) |
| GET | /api/health | Public |
| GET | /api/attractions?category=&thrill_level= | Public (admin may add `include_retired=1`) |
| GET | /api/attractions/{id} | Public |
| POST | /api/attractions | Admin |
| PUT | /api/attractions/{id} | Admin |
| PATCH | /api/attractions/{id}/retire | Admin |
| PATCH | /api/attractions/{id}/activate | Admin |

Local login headers: `X-Local-User: local-admin-1` and `X-Local-Groups: admins` (only with `APP_ENV=local`, `AUTH_MODE=local`).

## Tests

```
php artisan test
```
