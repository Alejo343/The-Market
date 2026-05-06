# CLAUDE.md

Guide Claude Code.

## Commands

```bash
composer setup   # deps + env + migrate + build
composer dev     # php + queue + vite
composer test    # clear cache + pest
php artisan test --filter TestName
php artisan migrate
npm run build
./vendor/bin/pint
```

## Architecture

Laravel 12 POS/inventory. Unit + weight products.

> Full system flows (Wompi, Siigo, ecommerce, POS): `docs/FLUJOS.md`

### Structure

2 interfaces:

- API (routes/api.php) → Sanctum Bearer
- Web (routes/web.php) → Blade + Livewire + session + Alpine

## Payments — Wompi MCP Server

MCP server `wompi-docs` is configured globally and available in all sessions.
Server path: `C:/Users/Webmaster/.claude/mcp-servers/wompi-docs/index.js`

### Flow

Route → Controller → Service → Model → DB
-FormRequest (validate)
-Resource (JSON)

Requests: 23
Resources: 13
Services: 13
Models: 12
Controllers thin. Logic in services.

### Sales Model

Products are either **unit-based** or **weight-based**:

- **Unit:** Product → ProductVariant → stock--
- **Weight:** Product → WeightLot → remaining_weight--

SaleItem + InventoryMovement = polymorphic
→ Variant or WeightLot

Always check:
saleable_type / moveable_type

Sale has optional customer fields: customer_identification, customer_name, customer_email.
If not provided → defaults to Consumidor Final (NIT 222222222) in Siigo invoice.

### Relations

Product → variants, weightLots, media (pivot: order, is_primary)
Sale → items → user
InventoryMovement → polymorphic

### Auth

API → Sanctum (Bearer token)
Web → session

Roles: admin, cashier (manual check)

ForceJsonResponse → API always JSON

### Frontend Stack

Livewire 4 (reactive)
Alpine.js 3 (UI state)
Chart.js (charts)
fetch API (AJAX — NOT axios)
Tailwind v4

### Database

SQLite (dev/test)
MySQL (prod)
Queue/cache/session → DB

20+ migrations → run migrate

### Notable Scopes

Product → active(), byUnit(), byWeight()
ProductVariant → lowStock(), outOfStock(), inStock(), onSale()
Sale → store(), online(), today(), betweenDates()

## Siigo Integration

ERP sync. Siigo is the source of truth for products, prices, and stock.
See full flow in `docs/FLUJOS.md`.

### Env vars required

```
SIIGO_USERNAME
SIIGO_ACCESS_KEY
SIIGO_PARTNER_ID
SIIGO_API_URL=https://api.siigo.com
SIIGO_COMPANY_KEY
SIIGO_WEBHOOK_URL=https://api.thebarrilmarket.com/api/webhooks/siigo/products
SIIGO_INVOICE_DOCUMENT_ID=26900
SIIGO_PAYMENT_TYPE_ID=12046
```

### Services

- SiigoAuthService — token cache (23h TTL)
- SiigoSyncService — product sync from webhook/polling
- SiigoInvoiceService — create electronic invoice from Sale

### Commands

```bash
php artisan siigo:register-webhooks   # register webhook subscriptions (run once)
php artisan siigo:sync-updated        # manual polling sync
php artisan siigo:import-products     # full import
```

### Queue worker (production)

Supervisor config: `/etc/supervisor/conf.d/market-worker.conf`
User: nobody — same as other workers on this server.
Logs: `storage/logs/worker.log`
