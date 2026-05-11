============================================================
FRESHWATER - РЪКОВОДСТВО ЗА РАБОТА С ПРИЛОЖЕНИЕТО
============================================================

Freshwater е ecommerce приложение, съставено от Laravel backend,
Filament admin panel и отделен React frontend.

Backend частта отговаря за:
- продукти и категории;
- количка;
- checkout;
- потребители и authentication;
- поръчки;
- доставки и Econt интеграция;
- имейли и queue jobs;
- admin панел чрез Filament.

Frontend частта е отделно React приложение, което комуникира с backend-а
през API endpoints.


============================================================
1. ТЕХНОЛОГИИ
============================================================

Backend:
- PHP 8.2+
- Laravel 12
- Filament 4
- Laravel Sanctum
- MySQL
- Database queue
- Econt API integration

Frontend:
- React
- API communication with Laravel backend


============================================================
2. СТРУКТУРА НА ПРОЕКТА
============================================================

Примерна структура:

backend/freshwater
    Laravel API + Filament admin panel

frontend
    React frontend application

React frontend-ът може да бъде на отделна машина или отделен хостинг.
Laravel backend-ът трябва да бъде достъпен през публичен API domain.


============================================================
3. BACKEND SETUP
============================================================

Изисквания:
- PHP 8.2 или по-нова версия
- Composer
- MySQL
- Web server: Nginx, Apache, Laragon или друг
- Node/npm само ако Laravel проектът ще build-ва локални Vite assets

Инсталиране на PHP dependencies:

    composer install

Създаване на .env файл:

    cp .env.example .env

Генериране на Laravel application key:

    php artisan key:generate

Миграции:

    php artisan migrate

Seed при нужда:

    php artisan db:seed

Storage link, ако се използват качени снимки или файлове:

    php artisan storage:link


============================================================
4. ВАЖНИ BACKEND .ENV НАСТРОЙКИ
============================================================

Локална среда:

    APP_ENV=local
    APP_DEBUG=true
    APP_URL=http://freshwater.test

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=freshwater
    DB_USERNAME=root
    DB_PASSWORD=

    FRONTEND_URLS=http://localhost:3000

    QUEUE_CONNECTION=database
    CACHE_STORE=database
    SESSION_DRIVER=file

Production среда:

    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://api.your-domain.com

    SESSION_ENCRYPT=true
    SESSION_SECURE_COOKIE=true

    FRONTEND_URLS=https://your-react-domain.com

    MAIL_MAILER=smtp
    MAIL_HOST=real-smtp-host
    MAIL_PORT=real-smtp-port
    MAIL_USERNAME=real-smtp-user
    MAIL_PASSWORD=real-smtp-password
    MAIL_FROM_ADDRESS=real-email@your-domain.com

    ECONT_ENABLED=true
    ECONT_USERNAME=real-econt-user
    ECONT_PASSWORD=real-econt-password

Важно:
- APP_DEBUG трябва да е false в production.
- SESSION_SECURE_COOKIE=true изисква HTTPS.
- FRONTEND_URLS трябва да съдържа реалния React domain.
- Econt credentials трябва да са production credentials, не dev.


============================================================
5. СТАРТИРАНЕ НА BACKEND ЛОКАЛНО
============================================================

С Laravel development server:

    php artisan serve

Или чрез Laragon/local virtual host:

    http://freshwater.test

Admin панелът е достъпен на:

    /admin

Само потребители с подходяща роля могат да достъпват admin панела.

Разрешени admin роли:
- admin
- superadmin


============================================================
6. QUEUE WORKER
============================================================

Приложението използва queued jobs за background действия като:
- имейли;
- shipping jobs;
- Econt-related jobs;
- tracking notifications.

Локално:

    php artisan queue:work

Production:

    php artisan queue:work --tries=3

В production queue worker-ът трябва да се управлява от Supervisor,
systemd или hosting platform process manager.


============================================================
7. API OVERVIEW
============================================================

Base API path:

    /api

Products:

    GET /api/products

Cart:

    GET    /api/cart
    POST   /api/cart/add/{product}
    PATCH  /api/cart/update/{product}
    DELETE /api/cart/delete/{product}
    DELETE /api/cart

Cart session id може да бъде подаден като:

    session_id
    sessionId
    X-Cart-Session-Id header

Пример:

    /api/cart?session_id=fw-cart-id
    /api/cart?sessionId=fw-cart-id

Auth:

    POST /api/register
    POST /api/login
    GET  /api/me
    POST /api/logout

Authenticated requests използват Bearer token:

    Authorization: Bearer {token}

Checkout:

    POST /api/checkout
    POST /api/checkout/calculate-shipping
    GET  /api/checkout/econt-offices?city=Sofia

Поддържани payment methods:

    cod
    bank_transfer

Поддържани shipping methods:

    address
    office
    apm


============================================================
8. БИЗНЕС ЛОГИКА
============================================================

Цени:

Frontend-ът не контролира цените при checkout.

Frontend-ът изпраща:
- product_id
- quantity

Backend-ът изчислява:
- item price;
- item total;
- subtotal;
- shipping price;
- final total.

Това предпазва checkout-а от манипулирани клиентски цени.


Stock:

Stock се проверява backend-side чрез StockService.

Ако stock tracking е включен:
- backend проверява наличност;
- quantity се намалява при checkout;
- при недостатъчна наличност checkout-ът се прекъсва.

Ако stock tracking е изключен:
- quantity check се пропуска;
- продуктът може да се поръчва без ограничение по наличност.


Guest cart:

Guest cart се идентифицира чрез frontend cart session id.

Backend-ът поддържа:
- session_id;
- sessionId;
- X-Cart-Session-Id.

При login guest cart може да бъде merge-нат към user cart.


Orders:

При създаване на поръчка могат да се задействат:
- order confirmation email;
- admin notification;
- shipping calculation;
- Econt shipment flow;
- tracking email jobs.

Тези процеси зависят от правилно настроени queue worker, mail и Econt credentials.


============================================================
9. FRONTEND SETUP
============================================================

React frontend-ът е отделно приложение.

Във frontend environment трябва да има API URL към Laravel backend-а.

Локално:

    VITE_API_URL=http://freshwater.test/api

Production:

    VITE_API_URL=https://api.your-domain.com/api

Инсталиране:

    npm install

Стартиране локално:

    npm run dev

Production build:

    npm run build

Build output-ът се deploy-ва към frontend hosting средата.

Ако React frontend-ът е на отделна машина, Laravel backend-ът не е нужно
да build-ва React frontend-а.


============================================================
10. LARAVEL VITE ASSETS
============================================================

Laravel проектът може да има локални Vite assets в:

    resources/css
    resources/js

Ако Blade страници използват @vite(...), тогава backend deploy-ът може да
изисква:

    npm install
    npm run build

Ако Laravel служи само като API + Filament admin, а React е отделно приложение,
този build може да не е критичен за публичния frontend.


============================================================
11. PRODUCTION DEPLOYMENT CHECKLIST
============================================================

Backend:

    composer install --no-dev --optimize-autoloader
    php artisan migrate --force
    php artisan storage:link
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

Tests:

    php artisan test

Queue:

    php artisan queue:work --tries=3

Frontend:

    npm install
    npm run build

Production smoke test:
- register;
- login;
- guest cart;
- cart merge after login;
- checkout;
- stock decrease;
- shipping estimate;
- Econt offices;
- admin login;
- order management;
- email sending;
- queue jobs.


============================================================
12. TROUBLESHOOTING
============================================================

Config changes не се отразяват:

    php artisan optimize:clear
    php artisan config:cache

Route cache проблем:

    php artisan route:clear
    php artisan route:cache

View cache проблем:

    php artisan view:clear
    php artisan view:cache

Queue jobs не се изпълняват:

    php artisan queue:work

Липсват качени снимки:

    php artisan storage:link

Frontend не може да достъпи API:

Провери:
- FRONTEND_URLS;
- APP_URL;
- CORS config;
- HTTPS;
- API base URL във frontend-а.

Admin panel е недостъпен:

Провери дали потребителят има роля:
- admin;
- superadmin.

Checkout връща грешка:

Провери:
- storage/logs/laravel.log;
- product stock;
- payment_method;
- shipping_method;
- Econt credentials;
- queue worker.


============================================================
13. ТЕКУЩО СЪСТОЯНИЕ
============================================================

Към момента backend логиката е подготвена за production/staging проба,
ако environment и инфраструктурата са правилно настроени.

Покрити важни случаи:
- guest cart session handling;
- session_id и sessionId aliases;
- guest cart merge при login;
- backend-side checkout price calculation;
- backend-side stock reservation;
- ограничени payment methods;
- duplicate email validation;
- generic error response при неочаквани checkout грешки.

Преди реален production launch задължително провери:
- production .env;
- реален SMTP;
- реални Econt credentials;
- HTTPS;
- queue worker;
- storage link;
- успешен ръчен checkout;
- успешен admin workflow.


============================================================
14. КРАТКО ОБОБЩЕНИЕ
============================================================

Backend-ът е Laravel API + Filament admin.
Frontend-ът е отделно React приложение.
Двете части комуникират през /api endpoints.

Основните production рискове не са вече в checkout логиката, а в:
- правилна environment конфигурация;
- mail/Econt credentials;
- queue worker;
- deployment процес;
- smoke testing след качване.

============================================================
END OF GUIDE
============================================================


