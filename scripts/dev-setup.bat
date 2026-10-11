@echo off
REM One time local setup (docs/05-local-development.md section 5). Developer tool only, not for deployment.
REM For each service: composer install, create .env, make APP_KEY, run migrations and seeders.
REM Then npm install for the frontend. Safe to run again.

setlocal enabledelayedexpansion
set ROOT=%~dp0..
cd /d "%ROOT%"

echo === Starting MySQL (Docker, port 3307) ===
docker compose up -d mysql || goto :error

echo === Waiting until MySQL is ready ===
:wait_mysql
docker exec wonderland-mysql mysqladmin ping -h 127.0.0.1 -uroot -proot --silent >nul 2>&1
if errorlevel 1 (
    timeout /t 2 /nobreak >nul
    goto :wait_mysql
)

for %%S in (attraction-service pricing-service booking-service payment-service event-service) do (
    echo.
    echo === %%S ===
    pushd "%ROOT%\services\%%S"

    call composer install --no-interaction || goto :error

    if not exist .env (
        copy .env.example .env >nul
        echo Created .env from .env.example
    )

    REM Make a new APP_KEY only when it is still empty, so existing keys are kept.
    findstr /R /C:"^APP_KEY=$" .env >nul && php artisan key:generate

    php artisan migrate --seed --force || goto :error
    popd
)

if exist "%ROOT%\frontend\package.json" (
    echo.
    echo === frontend ===
    pushd "%ROOT%\frontend"
    if not exist .env.local if exist .env.example copy .env.example .env.local >nul
    call npm install || goto :error
    popd
)

echo.
echo Setup finished. Now run scripts\dev-start.bat
exit /b 0

:error
echo.
echo Setup stopped because a step failed. Read the error above.
exit /b 1
