# Freshwater

Freshwater is a Laravel e-commerce backend for the Freshwater storefront and admin panel. It exposes a JSON API for products, cart, checkout, authentication, contact messages, and orders, and includes a Filament admin panel with Econt shipping integration, transactional emails, and background job processing.

## Features

- Product catalog, categories, pricing, stock, product weight, and shipment dimensions.
- Guest and authenticated cart flows with session-based cart continuity.
- Checkout flow with cash on delivery and bank transfer support.
- Filament admin panel for orders, products, categories, settings, contact messages, and shipments.
- Econt integration for outbound labels, return labels, tracking sync, and label deletion on cancellation.
- Return shipment flow modeled as a separate shipment record, similar to the Eksait implementation.
- Queued shipment creation, tracking emails, order confirmation emails, and admin notifications.
- Bulgarian localization across API messages, admin UI, and email templates.
- Rate limiting on public API endpoints and tightened logging of sensitive operational data.

## Tech Stack

- PHP 8.3
- Laravel 12
- Laravel Sanctum
- Filament 4
- Livewire 3
- Spatie Laravel Permission
- Vite 7
- Tailwind CSS 4
- PHPUnit 11

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm for asset builds
- MySQL or another Laravel-supported database
- Econt credentials, if live shipping is enabled
- SMTP credentials, if real email delivery is enabled

## Installation

Clone the repository and install dependencies:

```bash
git clone <repository-url>
cd freshwater
composer install
npm install
```

Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`, then run migrations:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

The project also includes a setup script:

```bash
composer run setup
```

## Environment Configuration

Important `.env` values:

```env
APP_NAME=Freshwater
APP_URL=http://localhost
BACKEND_URL=http://192.168.1.101:8000

APP_LOCALE=bg
APP_FALLBACK_LOCALE=bg

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=freshwater
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_ADMIN_ADDRESS=admin@freshwater.bg

ECONT_ENABLED=false
ECONT_SANDBOX=true
ECONT_VERIFY_SSL=true
ECONT_BASE_URL=https://demo.econt.com/ee/services
ECONT_TRACK_URL=
ECONT_MAX_PACK_WEIGHT_KG=30
ECONT_CARGO_DIMENSION_FROM_CM=60
ECONT_USERNAME=
ECONT_PASSWORD=
ECONT_SENDER_NAME=Freshwater
ECONT_SENDER_PHONE=
ECONT_SENDER_OFFICE=
ECONT_SENDER_CITY=
ECONT_SENDER_POSTCODE=
ECONT_SENDER_STREET=
ECONT_SENDER_NUM=

BANK_TRANSFER_COMPANY=
BANK_TRANSFER_IBAN=
BANK_TRANSFER_BANK=
BANK_TRANSFER_BIC=
BANK_TRANSFER_CURRENCY=EUR

SEED_DEMO_USERS=false
SEED_DEMO_USERS_PASSWORD=
```

Use one of these values for `ECONT_BASE_URL`:

- `https://demo.econt.com/ee/services` for sandbox
- `https://ee.econt.com/services` for production

For Econt sender configuration:

- Use `ECONT_SENDER_OFFICE` when the store sends from an Econt office.
- If `ECONT_SENDER_OFFICE` is set, sender address fields are ignored for outbound shipments and return shipments back to the store.
- If you send from a physical address, leave `ECONT_SENDER_OFFICE` empty and fill `ECONT_SENDER_CITY`, `ECONT_SENDER_POSTCODE`, `ECONT_SENDER_STREET`, and `ECONT_SENDER_NUM`.
- `ECONT_MAX_PACK_WEIGHT_KG` and `ECONT_CARGO_DIMENSION_FROM_CM` control the `pack` versus `cargo` shipment decision.

After changing Econt or queue-related `.env` values:

```bash
php artisan optimize:clear
php artisan queue:restart
```

## Running Locally

Start the Laravel server, queue worker, and Vite dev server together:

```bash
composer run dev
```

Or run them separately:

```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan queue:work
npm run dev
```

The default backend URL for admin links is `http://192.168.1.101:8000` unless `BACKEND_URL` is set.

## Testing

Run the full test suite:

```bash
php artisan test
```

Run formatting checks:

```bash
./vendor/bin/pint --test
```

Check PHP dependencies for known advisories:

```bash
composer audit
```

## API Overview

Public endpoints include:

```text
GET    /api/products
GET    /api/products/search
GET    /api/home-banner
GET    /api/checkout/payment-methods
GET    /api/checkout/econt-offices
POST   /api/contact
POST   /api/login
POST   /api/register
POST   /api/checkout/calculate-shipping
POST   /api/checkout
```

Cart endpoints:

```text
GET    /api/cart
POST   /api/cart/add/{product}
PATCH  /api/cart/update/{product}
DELETE /api/cart/delete/{product}
DELETE /api/cart
```

Authenticated endpoints:

```text
GET    /api/me
GET    /api/user
POST   /api/logout
GET    /api/orders
```

Guest cart requests support the cart session id through:

- `session_id`
- `sessionId`
- `X-Cart-Session-Id`

## Econt Shipping Flow

Outbound flow:

1. The order moves to `ready_for_shipment`.
2. `OrderReadyForShipment` is dispatched.
3. `CreateEcontShipment` creates the local outbound shipment row.
4. `ShipmentCreated` is dispatched with the created `shipmentId`.
5. `SendShipmentToEcont` maps the payload and creates the Econt label.

Return flow:

1. Admin triggers `Заяви връщане`.
2. `ShipmentReturnService` creates a separate shipment row with `direction = return`.
3. The order moves to `return_requested`.
4. `ShipmentCreated` is dispatched for the return shipment.
5. The same Econt dispatch pipeline creates the return label.

This avoids the older reverse-label approach that depended on extra `return_*` state on the outbound shipment.

## Shipment Type Logic

Shipment type is calculated in `ShipmentMeasurementService`:

- `cargo` when total weight is greater than `ECONT_MAX_PACK_WEIGHT_KG`
- `cargo` when `height`, `width`, or `length` exceeds `ECONT_CARGO_DIMENSION_FROM_CM`
- `pack` when dimensions are missing and weight stays below the limit

Dimensions are stored on products and propagated to shipments during shipment creation.

## Admin Panel

The Filament admin panel is available under `/admin`.

The orders area includes:

- order confirmation for COD and bank transfer orders
- Econt shipment creation
- order cancellation with Econt label deletion
- return request creation with separate return shipment rows
- shipment tracking visibility for outbound and return shipments

Admin access depends on your application permissions and user roles.

## Background Jobs and Events

The application uses queued listeners and jobs for:

- order confirmation emails
- admin order notifications
- Econt shipment creation
- return shipment creation
- shipment tracking emails
- failed shipment notifications
- guest cart cleanup

Use a queue worker in local development and production:

```bash
php artisan queue:work
```

Restart workers after deploying listener, event, or config changes:

```bash
php artisan queue:restart
```

## Useful Commands

```bash
php artisan migrate
php artisan migrate:status
php artisan test
php artisan route:list
php artisan queue:work
php artisan queue:restart
composer audit
npm run build
```

## Project Structure

```text
app/Filament/Resources   Filament resources and admin pages
app/Http/Controllers     API controllers
app/Models               Eloquent models
app/Services             Business services
app/Services/Econt       Econt API adapter and payload mapping
app/Jobs                 Queue jobs
app/Listeners            Event listeners
database/migrations      Database schema
resources/views/emails   Email templates
routes/api.php           JSON API routes
tests/Feature            Feature tests
```

## Production Checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set real `APP_URL`, `BACKEND_URL`, database, mail, and Econt values through environment secrets.
- Run:

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
```

- Cache framework metadata after configuration is final:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- Run at least one queue worker under a supervisor or service manager.
- Do not seed demo users in production unless it is an intentional bootstrap step.

## Operational Notes

- If the queue worker still logs old listener signatures, restart it. Old `laravel.log` entries remain as history and do not mean the current code is still wrong.
- If Econt returns city or postcode validation errors, inspect the customer shipping data and the generated payload.
- If queue restart does not propagate, verify the configured cache and database connection because restart signaling depends on them.

## Security Notes

- Do not commit real mail, Econt, or database credentials.
- Keep `.env.example` as a safe template only.
- Treat guest cart session ids as bearer identifiers.
