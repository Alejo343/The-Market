<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de pedido</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a1a1a; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 30px; }
        .section-title { font-size: 14px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #888; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
        th { text-align: left; padding: 8px 0; color: #888; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; }
        td { padding: 10px 0; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .total-row td { font-weight: bold; font-size: 15px; border-bottom: none; padding-top: 16px; }
        .footer { background: #f9f9f9; padding: 20px 30px; text-align: center; font-size: 12px; color: #aaa; }
        .badge { display: inline-block; background: #f0f0f0; color: #555; border-radius: 4px; padding: 4px 10px; font-size: 13px; font-family: monospace; }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h1>The Market Gourmet</h1>
        <p style="margin: 8px 0 0; font-size: 14px; color: #ccc;">¡Gracias por tu compra!</p>
    </div>

    <div class="body">

        <p style="font-size: 15px;">Hola <strong>{{ $order->customer_name }}</strong>, recibimos tu pedido y está siendo procesado.</p>

        {{-- Datos del pedido --}}
        <div class="section-title" style="margin-top: 24px;">Datos del pedido</div>
        <div class="info-row">
            <span class="info-label">Referencia</span>
            <span class="badge">{{ $order->reference }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha</span>
            <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Método de pago</span>
            <span>{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'Online')) }}</span>
        </div>

        {{-- Datos de entrega --}}
        @if($order->customer_address)
        <div class="section-title" style="margin-top: 28px;">Entrega</div>
        <div class="info-row">
            <span class="info-label">Dirección</span>
            <span>{{ $order->customer_address }}</span>
        </div>
        @if($order->customer_city)
        <div class="info-row">
            <span class="info-label">Ciudad</span>
            <span>{{ $order->customer_city }}</span>
        </div>
        @endif
        @if($order->deliveryZone)
        <div class="info-row">
            <span class="info-label">Zona de entrega</span>
            <span>{{ $order->deliveryZone->name }}</span>
        </div>
        @endif
        @endif

        {{-- Productos --}}
        <div class="section-title" style="margin-top: 28px;">Productos</div>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center;">Cant.</th>
                    <th style="text-align:right;">Precio</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>
                        @if($item->item)
                            {{ $item->item->product->name ?? 'Producto' }}
                            @if($item->item->presentation)
                                <br><span style="font-size:12px;color:#aaa;">{{ $item->item->presentation }}</span>
                            @endif
                        @else
                            Producto
                        @endif
                    </td>
                    <td style="text-align:center;">{{ (int) $item->quantity }}</td>
                    <td style="text-align:right;">${{ number_format($item->price, 0, ',', '.') }}</td>
                    <td style="text-align:right;">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                @if($order->delivery_cost_cents > 0)
                <tr>
                    <td colspan="3" style="color:#888;">Envío</td>
                    <td style="text-align:right;">${{ number_format($order->delivery_cost_cents / 100, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td colspan="3">Total</td>
                    <td style="text-align:right;">${{ number_format($sale->total, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top: 32px; font-size: 13px; color: #888;">
            Si tienes alguna duda sobre tu pedido escríbenos y con gusto te ayudamos.
        </p>

    </div>

    <div class="footer">
        The Market Gourmet &mdash; {{ config('app.name') }}<br>
        Este correo es generado automáticamente, por favor no respondas a él.
    </div>

</div>
</body>
</html>
