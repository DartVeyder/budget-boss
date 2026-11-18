param(
    [string]$Server = "root@192.168.50.252",
    [string]$RemoteDir = "/var/www/projects/budget-boss",
    [string]$Branch = "main"
)

$ErrorActionPreference = "Stop"

Write-Host "==> 1. Checking git status..." -ForegroundColor Cyan
$status = git status --porcelain --untracked-files=no
if ($status) {
    Write-Host "Uncommitted tracked changes detected:" -ForegroundColor Yellow
    Write-Host $status
    Write-Error "Commit or stash your changes before deploying."
}

Write-Host "==> 2. Pushing '$Branch' to origin..." -ForegroundColor Cyan
git push origin $Branch

Write-Host "==> 3. Deploying to $Server ($RemoteDir)..." -ForegroundColor Cyan
$remoteScript = @"
set -e
cd $RemoteDir
git fetch origin $Branch
git reset --hard origin/$Branch
composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
"@ -replace "`r`n", "`n"

$remoteScript | ssh $Server "tr -d '\r' | bash -s"

Write-Host "==> Deploy completed! http://192.168.50.252" -ForegroundColor Green
