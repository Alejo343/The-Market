# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**The Market** is a Laravel 12 Point of Sale (POS) and inventory management system supporting both unit-based and weight-based (bulk/market-style) products.

## Common Commands

```bash
# Development (runs Laravel server, queue listener, and Vite concurrently)
composer run dev

# Full project setup from scratch
composer run setup

# Frontend only
npm run dev        # Vite dev server with HMR
npm run build      # Production build

# Tests
composer run test
php artisan test --filter TestName   # Single test

# Code style
./vendor/bin/pint                    # Auto-fix
./vendor/bin/pint --test             # Check only
```

## Architecture

The app follows a layered pattern: **Routes → Controllers → Services → Models → Database**.

- **`app/Http/Controllers/Api/`** — RESTful JSON API (authenticated via Laravel Sanctum tokens)
- **`app/Http/Controllers/Web/`** — Blade-rendered HTML responses
- **`app/Services/`** — All business logic lives here; controllers are thin and delegate to services
- **`app/Models/`** — Eloquent models
- **`resources/views/`** — Blade templates; interactive pages use Livewire components

## Key Domain Concepts

**Two product types** (determined by `products.sale_type`):
- **Unit** → backed by `product_variants` (has SKU, barcode, quantity)
- **Weight** → backed by `weight_lots` (bulk/meat-market style, sold by weight)

**Inventory movements** are polymorphic — `InventoryMovement` can reference either a `ProductVariant` or a `WeightLot` as its moveable. Movement types: `in`, `out`, `adjustment`.

**Sales** have a `channel` field: `store` or `online`.

## API Authentication

Public endpoints (no auth): `GET /api/products`, categories, brands, taxes, regions, variants, weight-lots, and `POST /api/login` / `POST /api/register`.

Protected endpoints require a Sanctum Bearer token in the `Authorization` header.

## Frontend Stack

- **Livewire 4** — reactive server-side components (no separate SPA)
- **Alpine.js** — lightweight client-side interactivity
- **Tailwind CSS 4** — utility-first styling
- **Chart.js** — dashboards/charts

## Database

Default: SQLite (configured in `.env`). Session, cache, and queue all use the `database` driver.
