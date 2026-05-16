@echo off
cd /d c:\Users\DELL\lostlink\backend

if not exist vendor (
    echo Installing dependencies...
    C:\php8\php.exe composer.phar install --no-security-blocking
)

echo Starting backend at http://localhost:8080
C:\php8\php.exe -S localhost:8080 -t public
pause
