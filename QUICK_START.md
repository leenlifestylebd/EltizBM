# Fulfillo — Quick Start Guide

## Step 1: Install Laravel
```bash
composer create-project laravel/laravel my-fulfillo
cd my-fulfillo
```

## Step 2: Copy all files from this folder INTO your Laravel project
```bash
# Copy app folder
cp -r fulfillo-complete/app/* my-fulfillo/app/

# Copy resources
cp -r fulfillo-complete/resources/* my-fulfillo/resources/

# Copy routes
cp fulfillo-complete/routes/web.php my-fulfillo/routes/web.php

# Copy migration
cp fulfillo-complete/database/migrations/2024_01_01_000001_create_fulfillo_tables.php \
   my-fulfillo/database/migrations/
```

## Step 3: Configure .env
```bash
cp .env.example .env
```
Edit .env:
```
APP_NAME="Fulfillo"
APP_URL=http://localhost:8000
DB_DATABASE=fulfillo
DB_USERNAME=root
DB_PASSWORD=your_password
QUEUE_CONNECTION=database
```

## Step 4: Register Middleware in bootstrap/app.php
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'business' => \App\Http\Middleware\BusinessMiddleware::class,
    ]);
})
```

## Step 5: Register Console Command in routes/console.php or Kernel
```php
// In app/Console/Kernel.php or bootstrap/app.php:
$schedule->command('woo:sync --type=orders')->everyFifteenMinutes();
```

## Step 6: Run
```bash
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

## Step 7: Open browser
```
http://localhost:8000/register
```
Create your account and start using Fulfillo!

---
For full deployment guide see: DEPLOYMENT.md
