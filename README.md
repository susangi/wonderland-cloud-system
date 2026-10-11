# Wonderland Cloud Platform

Cloud native theme park platform built for **SE6020 – Architecting Enterprise Cloud Solutions** (SLIIT MSc IT).

Visitors browse rides, book tickets for a date and time slot, pay with a mock payment service and get a digital ticket with a QR code. Administrators manage rides and ticket prices, record gate check-ins and ride usage, and monitor park activity through an admin view and Metabase dashboards.

## Architecture in short

| Part | Technology |
|---|---|
| Microservices | 5 Laravel 12 apps (PHP 8.2): attraction, pricing, booking, payment, event |
| Database | MySQL 8.4 (Amazon RDS in the cloud), one schema per service |
| Frontend | React (Vite) visitor and admin portals, hosted on AWS Amplify |
| Auth | Amazon Cognito (`admins` group for administrators) |
| Entry point | Amazon API Gateway HTTP API with a Cognito JWT authorizer |
| Runtime | Docker images on Amazon EKS, ingress-nginx, HPA |
| Analytics | Metabase reading the event data |
| IaC and CI/CD | AWS CloudFormation, GitHub Actions with OIDC |

## Repository layout

```
services/            five Laravel microservices
frontend/            React visitor and admin portals
scripts/             local developer scripts and deploy scripts
docker-compose.yml   local MySQL
```

## Run locally (Windows)

Prerequisites: PHP 8.2, Composer 2, Docker Desktop, Node.js LTS. Only MySQL runs in Docker
(port **3307**, because XAMPP MySQL uses 3306). Details: `docs/05-local-development.md`.

1. One time setup (starts MySQL, installs packages, creates `.env` files, runs migrations and seeders):

   ```
   scripts\dev-setup.bat
   ```

2. Start everything (one window per service, the booking scheduler and the frontend):

   ```
   scripts\dev-start.bat
   ```

3. Optional sample data for the admin dashboard (marked `source = simulated`):

   ```
   scripts\dev-sample-data.bat
   ```

| Component | URL |
|---|---|
| attraction-service | http://127.0.0.1:8001 |
| pricing-service | http://127.0.0.1:8002 |
| booking-service | http://127.0.0.1:8003 |
| payment-service | http://127.0.0.1:8004 |
| event-service | http://127.0.0.1:8005 |
| frontend | http://localhost:5173 |

Each service has `/up` (probes) and `/api/health`. Run the tests of a service with `php artisan test` inside its folder.

Check the schemas: `docker exec wonderland-mysql mysql -uroot -proot -e "SHOW DATABASES;"`.
To reset the local database completely: `docker compose down -v`, then `scripts\dev-setup.bat` again.

## Secrets

`.env` files are never committed. Each service has a `.env.example` with safe placeholder values.
