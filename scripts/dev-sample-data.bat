@echo off
REM Adds 30 days of SAMPLE analytics data (source = simulated) for the admin dashboard and Metabase.
REM docs/05-local-development.md section 5. Developer tool only.

set ROOT=%~dp0..
cd /d "%ROOT%\services\event-service"

php artisan simulate:history --days=30
