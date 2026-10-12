@echo off
REM Starts everything on the laptop (docs/05-local-development.md section 5). Developer tool only.
REM MySQL runs in Docker. Each service and the frontend open in their own Command Prompt window.
REM Close a window to "stop" that service (useful for the failure demos).

set ROOT=%~dp0..
cd /d "%ROOT%"

echo Starting MySQL (Docker, port 3307)...
docker compose up -d mysql

start "attraction-service :8001" cmd /k "cd /d "%ROOT%\services\attraction-service" && php artisan serve --port=8001"
start "pricing-service :8002" cmd /k "cd /d "%ROOT%\services\pricing-service" && php artisan serve --port=8002"
start "booking-service :8003" cmd /k "cd /d "%ROOT%\services\booking-service" && php artisan serve --port=8003"
start "payment-service :8004" cmd /k "cd /d "%ROOT%\services\payment-service" && php artisan serve --port=8004"
start "event-service :8005" cmd /k "cd /d "%ROOT%\services\event-service" && php artisan serve --port=8005"

REM Runs outbox:flush every minute and bookings:expire-pending every 5 minutes.
start "booking scheduler" cmd /k "cd /d "%ROOT%\services\booking-service" && php artisan schedule:work"

if exist "%ROOT%\frontend\package.json" (
    start "frontend :5173" cmd /k "cd /d "%ROOT%\frontend" && npm run dev"
)

echo.
echo Started. Open http://localhost:5173
echo Health checks: http://127.0.0.1:8001/api/health ... http://127.0.0.1:8005/api/health
