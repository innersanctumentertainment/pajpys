# PAJPYS local dev server (same pattern as tactile, compo-compre, quickinvoice)
Set-Location (Split-Path $PSScriptRoot -Parent)
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan serve --host=127.0.0.1 --port=8095
