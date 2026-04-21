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
Services: 12
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

### Relations

Product → variants, weightLots, media (pivot: order, is_primary)
Sale → items → user
InventoryMovement → polymorphi

### Auth

API → Sanctum (Bearer token)
Web → session

Roles: admin, cashier (manual check)

ForceJsonResponse → API always JSON

### Frontend Stack

Livewire 4 (reactive)
Alpine.js 3 (UI state)
Chart.js (charts)
Axios (AJAX)
Tailwind v4

### Database

SQLite (dev/test)
MySQL (prod)
Queue/cache/session → DB

20 migrations → run migrate

### Notable Scopes

Product → active(), byUnit(), byWeight()
ProductVariant → lowStock(), outOfStock(), inStock(), onSale()
Sale → store(), online(), today(), betweenDates()
