# Flujos del Sistema — The Barril Market

## Arquitectura general

```
┌─────────────────────┐     HTTPS      ┌──────────────────────────────┐
│  Next.js (ecommerce)│ ─────────────► │  Laravel API                 │
│  thebarrilmarket.com│ ◄───────────── │  api.thebarrilmarket.com/api  │
└─────────────────────┘                └──────────────────────────────┘
                                                    │
                               ┌────────────────────┼────────────────────┐
                               ▼                    ▼                    ▼
                         ┌──────────┐        ┌──────────┐        ┌──────────┐
                         │  MySQL   │        │  Wompi   │        │  Siigo   │
                         │  (prod)  │        │  (pagos) │        │  (ERP)   │
                         └──────────┘        └──────────┘        └──────────┘
```

---

## 1. Flujo de venta online (Ecommerce)

### 1.1 Pago con tarjeta (CARD)

```
[Cliente] llena formulario de checkout
    │
    ├─ Datos: nombre, email, teléfono, dirección, ciudad
    ├─ Identificación: tipo (CC/NIT/CE/PP) + número + razón social (si NIT)
    └─ Items del carrito

[Next.js browser]
    │
    ├─ 1. POST https://sandbox.wompi.co/v1/tokens/cards
    │       Authorization: Bearer NEXT_PUBLIC_WOMPI_PUBLIC_KEY
    │       → { cardToken: "tok_..." }
    │
    └─ 2. POST /api/checkout/card/pay  (Next.js API Route — servidor)
            │   cardToken, amountInCents, customerEmail, customerName,
            │   customerPhone, customerAddress, customerCity,
            │   customerIdentificationType, customerIdentification,
            │   customerBusinessName, installments, items,
            │   deliveryZoneId, deliveryCostCents
            │
            [Next.js server → Laravel]
            POST https://api.thebarrilmarket.com/api/checkout/card/pay
            Authorization: Bearer API_TOKEN
                │
                ├─ CheckoutController::cardPay()
                ├─ Genera reference (ULID)
                ├─ OrderService::createPendingOrder() → Order{status:PENDING}
                ├─ WompiService::createCardTransaction()
                │       POST https://sandbox.wompi.co/v1/transactions
                │       → { transactionId, status }
                └─ OrderService::updateTransactionId()
                    → { transactionId, reference, status }

[Next.js browser]
    │
    ├─ Guarda en localStorage "barril-pending-order":
    │       { reference, items, total, customer: form, createdAt }
    │
    └─ Polling cada 5s → GET /api/checkout/orders/{reference}/status
            Si APPROVED → ir a /checkout/result?reference=...
```

### 1.2 Pago con Nequi

```
[Next.js browser]
    └─ POST /api/checkout/nequi/pay
            │   phone, amountInCents, customerEmail, customerName,
            │   customerPhone, customerAddress, customerCity,
            │   customerIdentificationType, customerIdentification,
            │   customerBusinessName, items, deliveryZoneId, deliveryCostCents
            │
            [Next.js server → Laravel]
            POST https://api.thebarrilmarket.com/api/checkout/nequi/pay
                │
                ├─ Crea Order{status:PENDING}
                ├─ WompiService::createNequiTransaction()
                │       payment_method: { type:"NEQUI", phone_number }
                └─ → { transactionId, reference, status }

    └─ Polling cada 5s → GET /api/checkout/transactions/{transactionId}/status
```

### 1.3 Pago con PSE

```
[Next.js browser]
    ├─ GET /api/checkout/pse/institutions → lista de bancos Wompi
    │
    └─ POST /api/checkout/pse/pay
            │   amountInCents, customerEmail, fullName, phone,
            │   customerAddress, customerCity, userType, userLegalIdType,
            │   userLegalId, financialInstitutionCode, redirectUrl,
            │   customerIdentificationType, customerIdentification,
            │   customerBusinessName, items, deliveryZoneId, deliveryCostCents
            │
            [Next.js server → Laravel]
                ├─ Crea Order{status:PENDING}
                ├─ WompiService::createPseTransaction()
                └─ → { transactionId, reference, asyncPaymentUrl }

    └─ window.location.href = asyncPaymentUrl  (redirect a Wompi)
       Usuario paga en el banco → Wompi redirige a /checkout/result
       Polling hasta 2 min (24 intentos × 5s)
```

---

## 2. Resultado del pago (/checkout/result)

```
[Next.js browser — result/page.tsx]
    │
    ├─ Lee "barril-pending-order" de localStorage
    └─ Polling → GET /api/checkout/orders/{reference}/status

    Si APPROVED:
        ├─ POST /api/sales  (Next.js API Route)
        │       Authorization: Bearer API_TOKEN
        │       body: {
        │           channel: "online",
        │           items: [{ type:"variant", id, quantity }],
        │           customer: {
        │               identification: o.customer.identification,
        │               name: o.customer.businessName || o.customer.name,
        │               email: o.customer.email
        │           }
        │       }
        │
        │   [Next.js server → Laravel]
        │   POST https://api.thebarrilmarket.com/api/sales
        │       └─ SaleController::store()
        │           └─ SaleService::create()
        │               ├─ Descuenta stock (ProductVariant o WeightLot)
        │               ├─ Crea Sale + SaleItems en BD
        │               ├─ Dispatch CreateSiigoInvoiceJob (delay 3s)
        │               └─ Dispatch SendWhatsAppJob
        │
        ├─ clearCart()
        └─ localStorage.removeItem("barril-pending-order")

    Si DECLINED / ERROR / VOIDED:
        └─ Muestra botón "Intentar de nuevo" → /checkout

    Si timeout (2 min):
        └─ Muestra estado PENDING
```

---

## 3. Webhook de Wompi → actualización de orden

```
[Wompi]
    └─ POST /api/webhooks/wompi/transaction
            │   { event: "transaction.updated", data: { transaction: {...} } }
            │
            WebhookController::wompiTransaction()
                ├─ Verifica firma HMAC (WOMPI_EVENTS_SECRET)
                ├─ OrderService::updateStatus(transactionId, status)
                │
                Si PENDING → APPROVED:
                │   ├─ OrderInventoryService::processApprovedOrder()
                │   │       Descuenta stock de cada item en items_data
                │   └─ Dispatch SendWhatsAppJob (notificaciones negocio + cliente)
                │
                Si APPROVED → DECLINED/VOIDED/ERROR:
                    └─ OrderInventoryService::restoreRejectedOrder()
                            Restaura stock
```

> **Nota:** El stock puede decrementarse dos veces si el webhook de Wompi llega
> antes de que el frontend llame `POST /api/sales`. Revisar si `processApprovedOrder`
> y `SaleService::create` no duplican el descuento.

---

## 4. Facturación electrónica (Siigo)

```
[SaleService::create() — después del DB::commit()]
    └─ Dispatch CreateSiigoInvoiceJob(saleId, delay: 3s)
            │
            [Queue Worker — Supervisor]
            CreateSiigoInvoiceJob::handle()
                └─ SiigoInvoiceService::createFromSale(sale)
                        │
                        ├─ Obtiene token: SiigoAuthService::getToken()
                        │       Cache 23h → POST https://api.siigo.com/auth
                        │
                        ├─ Construye payload:
                        │   {
                        │     document: { id: SIIGO_INVOICE_DOCUMENT_ID (26900) },
                        │     customer: {
                        │         identification: sale.customer_identification ?? "222222222",
                        │         branch_office: 0
                        │     },
                        │     date: sale.created_at (YYYY-MM-DD),
                        │     items: [ { code: variant.sku, description, quantity, price } ],
                        │     payments: [{ id: SIIGO_PAYMENT_TYPE_ID (12046), value: total }]
                        │   }
                        │
                        ├─ POST https://api.siigo.com/v1/invoices
                        │
                        Si exitoso:
                        │   └─ sale.update({ siigo_invoice_id: id })
                        │
                        Si falla:
                            └─ Log::error() + reintento automático (3 tries, 60s backoff)
```

### Datos del cliente en la factura

| Caso | identification | name |
|------|---------------|------|
| Sin datos (mostrador) | `222222222` | Consumidor Final (en Siigo) |
| Con cédula | número CC/CE | Nombre del cliente |
| Con NIT | número NIT | Razón social |

---

## 5. Sincronización Siigo → App (webhooks)

```
[Siigo — cuando se crea/actualiza producto o cambia stock]
    └─ POST https://api.thebarrilmarket.com/api/webhooks/siigo/products
            │   {
            │     topic: "public.siigoapi.products.create|update|stock.update",
            │     company_key: "INVERSIONESGRUPOMARKETSAS",
            │     id: "...", code: "SKU001", name: "...", ...
            │   }
            │
            SiigoWebhookController::handle()
                ├─ Valida company_key
                ├─ Filtra topics permitidos
                └─ Dispatch ProcessSiigoWebhook(payload)

            [Queue Worker]
            ProcessSiigoWebhook::handle()
                └─ SiigoSyncService::syncFromPayload(payload)
                        └─ syncProduct(data)
                                ├─ Busca ProductVariant por sku
                                ├─ Si existe → actualiza nombre, precio, stock
                                ├─ Si no existe → crea Product + ProductVariant
                                └─ Registra SiigoSyncLog
```

### Suscripciones registradas (2026-05-06)

| Topic | ID de suscripción |
|-------|-------------------|
| `products.create` | `a7298b0d-6c29-454d-8697-394fbe859ac8` |
| `products.update` | `86040342-9b18-4793-bfed-3b33d75bf8f9` |
| `products.stock.update` | `f708bec9-a481-43ed-9db9-44538e4fd18d` |

Re-registrar: `php artisan siigo:register-webhooks`

---

## 6. Venta en mostrador (POS)

```
[Panel admin — Livewire]
    └─ POST /api/sales  (con Sanctum Bearer del cajero)
            body: {
                channel: "store",
                items: [{ type:"variant"|"weight_lot", id, quantity }]
                // sin customer → factura a Consumidor Final
            }
            │
            SaleController::store()
                └─ SaleService::create()
                        ├─ Descuenta stock / peso
                        ├─ Crea Sale + SaleItems
                        ├─ Dispatch CreateSiigoInvoiceJob
                        └─ Dispatch SendWhatsAppJob
```

---

## 7. Procesamiento de colas (Queue Worker)

```
Supervisor → market-worker (nobody@srv981120)
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    logs: storage/logs/worker.log

Jobs en cola:
    ├─ ProcessSiigoWebhook     (3 tries, 30s backoff)
    ├─ CreateSiigoInvoiceJob   (3 tries, 60s backoff)
    └─ SendWhatsAppJob
```

---

## 8. Rutas API — resumen

### Públicas (sin token)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/categories` | Lista categorías |
| GET | `/products` | Lista productos |
| GET | `/product-variants` | Lista variantes |
| GET | `/regions` | Lista regiones |
| GET | `/delivery-zones` | Zonas de envío |
| POST | `/checkout/delivery-zone/detect` | Detectar zona por coords |
| GET | `/checkout/acceptance` | Términos Wompi |
| POST | `/checkout/signature` | Genera firma SHA256 |
| GET | `/checkout/pse/institutions` | Bancos PSE |
| POST | `/checkout/nequi/pay` | Iniciar pago Nequi |
| POST | `/checkout/card/pay` | Iniciar pago tarjeta |
| POST | `/checkout/pse/pay` | Iniciar pago PSE |
| GET | `/checkout/orders/{ref}/status` | Estado de orden |
| POST | `/webhooks/wompi/transaction` | Webhook Wompi |
| POST | `/webhooks/siigo/products` | Webhook Siigo |

### Protegidas (Sanctum Bearer)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/sales` | Crear venta |
| GET | `/sales` | Listar ventas |
| POST | `/login` | Login → token |
| POST | `/logout` | Logout |
| * | `/products` | CRUD productos |
| * | `/product-variants` | CRUD variantes |
| * | `/inventory-movements` | Movimientos inventario |
| * | `/users` | CRUD usuarios |

---

## 9. Variables de entorno clave

### Laravel (`/var/www/market/backend/.env`)

```env
# Wompi
WOMPI_API_URL=https://production.wompi.co/v1
WOMPI_PUBLIC_KEY=pub_prod_...
WOMPI_PRIVATE_KEY=prv_prod_...
WOMPI_INTEGRITY_SECRET=...
WOMPI_EVENTS_SECRET=...

# Siigo
SIIGO_USERNAME=...
SIIGO_ACCESS_KEY=...
SIIGO_PARTNER_ID=the-market
SIIGO_API_URL=https://api.siigo.com
SIIGO_COMPANY_KEY=INVERSIONESGRUPOMARKETSAS
SIIGO_WEBHOOK_URL=https://api.thebarrilmarket.com/api/webhooks/siigo/products
SIIGO_INVOICE_DOCUMENT_ID=26900
SIIGO_PAYMENT_TYPE_ID=12046
```

### Next.js (`ahre/.env.local`)

```env
NEXT_PUBLIC_API_URL=https://api.thebarrilmarket.com/api
NEXT_PUBLIC_WOMPI_PUBLIC_KEY=pub_prod_...
NEXT_PUBLIC_WOMPI_API_URL=https://production.wompi.co/v1
WOMPI_PRIVATE_KEY=prv_prod_...
WOMPI_INTEGRITY_SECRET=...
API_TOKEN=<sanctum token del usuario ecommerce>
```

---

## 10. Pendientes / issues conocidos

| # | Descripción | Prioridad |
|---|-------------|-----------|
| 1 | Doble descuento de stock: webhook Wompi (`OrderInventoryService`) + `SaleService::create()` pueden decrementar el mismo stock si se ejecutan antes de que el frontend llame `/api/sales` | Alta |
| 2 | Siigo puede re-enviar webhook `stock.update` al recibir la factura electrónica creada por `CreateSiigoInvoiceJob`, causando otro descuento en BD | Alta |
| 3 | `SIIGO_PAYMENT_TYPE_ID=12046` (Transferencia) se usa para todas las ventas. Crear medio de pago diferenciado por canal (Efectivo Mostrador / Tienda Online) | Media |
| 4 | WeightLot items no se incluyen en la factura Siigo (no tienen `sku`) | Media |
| 5 | Token `API_TOKEN` en Next.js `.env.local` aún es placeholder | Alta |
