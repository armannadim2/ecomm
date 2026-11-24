# eComm (backend)

Laravel application for the eComm store: admin panel (Filament), storefront and the `/api/v1` API for the mobile app.

Project documentation (architecture, decisions, phase notes) lives outside this repository in `D:\Projects\eComm\docs`.

## Quick start (Laragon)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Admin panel: `/admin`. Local test accounts are listed in `database/seeders/LocalDemoSeeder.php` (local environment only).

## Tests

```bash
./vendor/bin/pest
./vendor/bin/pint --test
```
