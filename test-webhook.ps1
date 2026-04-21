$SECRET = "test_events_F7umY6oIRbZSBKvpFFCxJ3hJKzyeejCJ"
$PAYLOAD = @{
    event = "transaction.updated"
    data = @{
        id = "12068474-1776799818-66019"
        reference = "BARRIL-NQ-1776799818-KPWFU"
        status = "APPROVED"
        status_message = "Transacción aprobada"
        amount_in_cents = 200000
        currency = "COP"
    }
} | ConvertTo-Json -Compress

Write-Host "=== Webhook Test ==="
Write-Host "Payload: $PAYLOAD"
Write-Host "Payload length: $($PAYLOAD.Length) bytes"

$SecretBytes = [System.Text.Encoding]::UTF8.GetBytes($SECRET)
$PayloadBytes = [System.Text.Encoding]::UTF8.GetBytes($PAYLOAD)
$HMAC = New-Object System.Security.Cryptography.HMACSHA256 -ArgumentList @(,$SecretBytes)
$HASH = $HMAC.ComputeHash($PayloadBytes)
$SIGNATURE = -join ($HASH | ForEach-Object { "{0:x2}" -f $_ })

Write-Host "Secret: $SECRET"
Write-Host "Calculated signature: $SIGNATURE"
Write-Host ""

$HEADERS = @{
    "Content-Type" = "application/json; charset=utf-8"
    "X-Wompi-Signature" = $SIGNATURE
}

# Convertir payload a bytes UTF-8 para asegurar encoding correcto
$bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($PAYLOAD)

Write-Host "Sending POST /api/webhooks/wompi/transaction..."
$RESPONSE = Invoke-WebRequest -Uri "http://the-market.test/api/webhooks/wompi/transaction" `
    -Method POST `
    -Headers $HEADERS `
    -Body $bodyBytes `
    -UseBasicParsing `
    -ErrorAction SilentlyContinue

Write-Host "Response Status: $($RESPONSE.StatusCode)"
Write-Host "Response Body: $($RESPONSE.Content)"
Write-Host ""
Write-Host "Check logs at: storage/logs/laravel.log"
