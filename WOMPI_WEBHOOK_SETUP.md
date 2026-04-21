# Configuración del Webhook de Wompi

## 📋 Requisitos

- Cuenta de Wompi (Sandbox o Producción)
- Backend Laravel corriendo en una URL pública o accesible desde internet
- Variables de entorno configuradas: `WOMPI_EVENTS_SECRET`

## 🔧 Pasos de Configuración

### 1. Ir al Dashboard de Wompi

**Sandbox:** https://sandbox-dashboard.wompi.co/
**Producción:** https://dashboard.wompi.co/

Inicia sesión con tus credenciales.

### 2. Navegar a Webhooks

- En el menú lateral, ve a: **Configuración** → **Webhooks**
- O busca directamente en la URL: `/admin/webhooks` o `/settings/webhooks`

### 3. Crear Nuevo Webhook

Haz clic en **"Crear Webhook"** o **"Add Webhook"**

### 4. Configurar el Webhook

Rellena los campos con:

| Campo | Valor |
|-------|-------|
| **URL** | `https://tudominio.com/api/webhooks/wompi/transaction` |
| **Evento** | `transaction.updated` |
| **Versión** | `1.0` |
| **Estado** | Activado ✓ |

**Nota:** Reemplaza `tudominio.com` con tu dominio real.

#### Ejemplos de URLs:
- **Desarrollo local con ngrok:** `https://abc123.ngrok.io/api/webhooks/wompi/transaction`
- **Producción:** `https://api.thebarrilmarket.com/api/webhooks/wompi/transaction`
- **Staging:** `https://staging-api.thebarrilmarket.com/api/webhooks/wompi/transaction`

### 5. Guardar y Probar

- Haz clic en **Guardar** o **Create**
- Wompi te mostrará un token o secret (cópialo)
- Intenta enviar un evento de prueba desde el dashboard

## 🔐 Seguridad

### Header de Validación

Wompi envía un header `X-Wompi-Signature` con cada webhook:

```
X-Wompi-Signature: sha256_hash_del_payload_con_secret
```

Nuestro backend valida este header usando `WOMPI_EVENTS_SECRET`.

### Validar Firma Manualmente

Si necesitas debuggear:

```bash
# El payload recibido debe ser exactamente igual al firmado
# Wompi usa: HMAC-SHA256(payload, WOMPI_EVENTS_SECRET)

# En PHP:
$signature = hash_hmac('sha256', $payload, $secret);
if (hash_equals($signature, $headerSignature)) {
    // ✅ Válido
}
```

## 📊 Estructura del Evento

Cuando una transacción cambia de estado, Wompi envía:

```json
{
  "event": "transaction.updated",
  "data": {
    "id": "12068474-1776799818-66019",
    "reference": "BARRIL-NQ-1776799818-KPWFU",
    "status": "APPROVED",
    "status_message": "Transacción aprobada",
    "amount_in_cents": 200000,
    "currency": "COP",
    "payment_method": {
      "type": "NEQUI",
      "phone_number": "3101234567"
    },
    "customer_email": "test@example.com",
    "created_at": "2026-04-21T19:30:00Z",
    "updated_at": "2026-04-21T19:31:00Z"
  }
}
```

### Estados Posibles

- **PENDING** - Esperando confirmación
- **APPROVED** - Pago aprobado ✅ (Aquí se decrementa inventario)
- **DECLINED** - Pago rechazado ❌
- **VOIDED** - Pago anulado
- **ERROR** - Error en el procesamiento

## 🧪 Testing del Webhook

### Opción 1: Desde el Dashboard de Wompi

1. Ve a Webhooks
2. Haz clic en el webhook que creaste
3. Busca "Send Test Event" o "Enviar evento de prueba"
4. Selecciona un evento de prueba
5. Envía

### Opción 2: Con curl (Producción)

```bash
# Obtener WOMPI_EVENTS_SECRET de tu .env
SECRET="test_events_F7umY6oIRbZSBKvpFFCxJ3hJKzyeejCJ"

PAYLOAD='{"event":"transaction.updated","data":{"id":"12068474-1776799818-66019","status":"APPROVED"}}'

SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

curl -X POST https://tudominio.com/api/webhooks/wompi/transaction \
  -H "Content-Type: application/json" \
  -H "X-Wompi-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

### Opción 3: Con PowerShell (Windows)

Ver sección **"Probar Webhook desde PowerShell"** abajo.

## 🔄 Flujo Completo

```
1. Cliente crea orden en checkout
   ↓
2. Frontend envía POST /checkout/nequi/pay
   ↓
3. Backend crea orden en BD (status=PENDING)
   ↓
4. Backend crea transacción en Wompi
   ↓
5. Cliente ve pantalla de espera
   ↓
6. Wompi procesa el pago (usuario autoriza en app)
   ↓
7. Wompi cambia status de transacción
   ↓
8. Wompi envía webhook a /api/webhooks/wompi/transaction
   ↓
9. Backend recibe webhook, valida firma
   ↓
10. Backend actualiza orden a status=APPROVED
   ↓
11. Backend decrementa inventario (stock--)
   ↓
12. Cliente ve confirmación de pago
```

## 📱 Webhooks en Desarrollo Local

Para probar webhooks en desarrollo local, usa:

- **ngrok:** `ngrok http 8000` → obtén URL como `https://abc123.ngrok.io`
- **Herd:** Configura "Expose" para obtener URL pública
- **Docker + Cloudflare Tunnel:** Para acceso más estable

Luego en Wompi configura: `https://tu-url-publica/api/webhooks/wompi/transaction`

## 🐛 Troubleshooting

### Webhook no llega

1. Verifica que la URL sea pública (prueba con `curl`)
2. Verifica que el webhook esté "Activo" en el dashboard
3. Revisa logs de Laravel: `storage/logs/laravel.log`
4. Verifica que `WOMPI_EVENTS_SECRET` sea correcta

### Error "Invalid signature"

1. Asegúrate que `WOMPI_EVENTS_SECRET` en `.env` sea exacta
2. Wompi no debería cambiar el secret una vez creado
3. Si cambiaste el secret, actualiza el webhook en el dashboard

### Orden no se actualiza

1. Verifica que exista una orden con ese `transaction_id`
2. Revisa logs: `grep "Webhook:" storage/logs/laravel.log`
3. Verifica que el estado sea válido (APPROVED, DECLINED, etc)

## 📞 Soporte

- **Docs Wompi:** https://docs.wompi.co/
- **Email:** soporte@wompi.co
- **Chat:** Disponible en dashboard de Wompi
