# First-time local setup for PAJPYS on Windows
Set-Location (Split-Path $PSScriptRoot -Parent)

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    php artisan key:generate
}

if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File -Path database/database.sqlite | Out-Null
}

composer install
npm install
php artisan migrate --seed
npm run build

Write-Host ""
Write-Host "PAJPYS is ready." -ForegroundColor Green
Write-Host "  Start:  .\tools\serve.ps1"
Write-Host "  Open:   http://127.0.0.1:8095"
Write-Host "  Admin:  admin@pajpys.com / password"
Write-Host ""
