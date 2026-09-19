# Kadari Stores

Kadari Stores is a sales-management web app built for a small cereal and
grocery shop in Kenya. It replaces the shop's Excel-based bookkeeping sheet
with a mobile-friendly Progressive Web App (PWA) that the owner can use
directly from a phone browser, with the same calculations the shop already
relies on.

## What the app does

The shop currently tracks everything in a spreadsheet with five parts:
a master item list, stock deliveries, sales, a computed stock balance, and a
dashboard. This app rebuilds that same model as a proper application:

- **Items** — the master catalogue: item name, category (Cereal, Eggs,
  Honey, Milk, Oil, Sugar, Uji, Unga, ...), unit of measure (Kg, L, Tray),
  buying price and selling price per unit.
- **Stock-In** — every delivery recorded against an item, with the buying
  price at the time of delivery (so a later price change never rewrites the
  cost of stock already received).
- **Sales** — every sale recorded against an item, accepting whichever of
  three inputs fits the transaction: quantity sold, pieces sold (for items
  such as eggs sold individually out of a tray), or amount paid. Revenue,
  cost of goods sold and profit are calculated automatically, using the
  buying price that was actually in effect on the date of that sale — so
  editing today's price never changes yesterday's profit.
- **Stock Balance** — live remaining stock per item (stock in minus stock
  sold), its current value, and a status flag (OK / Low / Out of Stock).
- **Dashboard & Reports** — revenue, cost of goods sold, gross profit and
  profit margin, rolled up **daily, monthly and yearly**, plus a breakdown
  by item and by category, and low/out-of-stock alerts.

The goal is the same day-to-day job the spreadsheet does — record a
delivery, record a sale, see what the shop made — just faster, on a phone,
without formulas to accidentally break.

## Design

- A modern, uncluttered interface restricted to three colors (deep green,
  warm amber, and neutral gray/white) plus black and white for text and
  backgrounds — no emojis, no visual clutter.
- Built as an installable PWA: works in a phone's browser, can be added to
  the home screen, and is designed mobile-first since that's how the owner
  will use it day to day.

## Tech stack

- **Laravel** (PHP) for the application and database layer (items, stock
  movements, sales ledger, reporting queries).
- **Livewire** + **Blade** + **Tailwind CSS** for a reactive, modern UI
  without a separate JavaScript frontend to maintain.
- **Vite** for asset bundling. The PWA manifest, icons, service worker, and
  offline page are plain static files in `public/` (no extra build plugin).
- **MySQL/SQLite** for storage (SQLite by default for easy local setup).

## Project structure & branching

Each functional module is developed on its own branch and merged into the
integration branch once working, before everything is finally merged to
`main`:

- `module/setup-auth` — project setup, authentication, base layout/theme
- `module/items` — item catalogue management
- `module/stock-in` — stock delivery recording
- `module/sales` — sales recording and profit calculation
- `module/stock-balance-dashboard` — stock levels and revenue/profit
  reporting (daily/monthly/yearly)
- `module/pwa` — PWA manifest, icons, offline/installable support

## Getting started (WSL / Ubuntu)

```bash
git clone <repository-url>
cd Kadari-Stores
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using SQLite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Then open the app in a browser on the same network as your phone (or use
`php artisan serve --host=0.0.0.0`) to test the PWA install prompt on a
mobile device.

For putting the app on a real server (Oracle Cloud Always Free + MySQL),
including moving your existing SQLite data across without re-entering it,
see [DEPLOYMENT.md](DEPLOYMENT.md).

The seeder creates one shop-owner login and the shop's real item
catalogue, so you can sign in immediately:

- **Email:** `owner@kadaristores.test`
- **Password:** `password`

(Registration is disabled once that first account exists — this app is
built for one shop owner, not public sign-up. Change the password from
the Profile page after first login.)

### Configuration

- `LOW_STOCK_THRESHOLD` (`.env`, default `5`) — remaining quantity at or
  below which an item shows as "Low" on the Stock Balance page.

### Running tests

```bash
php artisan test
```

## Routes

| Page | URL | Purpose |
|---|---|---|
| Dashboard | `/dashboard` | Revenue, cost, profit, margin, and the daily/monthly/yearly trend |
| Items | `/items` | Manage the item catalogue |
| Stock-In | `/stock-in` | Record deliveries |
| Sales | `/sales` | Record sales |
| Stock Balance | `/stock-balance` | Remaining stock and value per item |
