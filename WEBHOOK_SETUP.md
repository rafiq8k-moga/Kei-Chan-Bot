# WhatsApp Bot Webhook Configuration

## URL Webhook untuk Dashboard Fonnte

### 1. Webhook Utama (Pesan Masuk)
```
https://moutogami.com/project_gabut_gw/public/whatsapp_webhook.php
```
- **Fungsi**: Menerima semua pesan masuk WhatsApp
- **Handle**: Chat bot, commands, attachments, group messages

### 2. Webhook Device Status
```
https://moutogami.com/project_gabut_gw/public/webhook_device_status.php
```
- **Fungsi**: Monitor status koneksi device WhatsApp
- **Notifikasi**: Connect/disconnect dengan alasan

### 3. Webhook Message Status
```
https://moutogami.com/project_gabut_gw/public/webhook_message_status.php
```
- **Fungsi**: Update status pengiriman pesan real-time
- **Status**: Pending → Sent → Delivered → Read/Failed

### 4. Webhook Chaining
```
https://moutogami.com/project_gabut_gw/public/webhook_chaining.php
```
- **Fungsi**: Integrasi dengan sistem lain (CRM, Analytics, etc.)
- **Fitur**: Forward data, backup, trigger berdasarkan konten

## Setup di Dashboard Fonnte

1. Login ke dashboard Fonnte
2. Menu **Device** → **Edit** pada device kamu
3. Set **Auto Read** = **ON** (wajib agar webhook berfungsi)
4. Masukkan URL webhook di masing-masing field:
   - **Webhook ?** → URL Webhook Utama
   - **Webhook Connect ?** → URL Device Status  
   - **Webhook Message Status ?** → URL Message Status
   - **Webhook Chaining ?** → URL Chaining

## Requirements

- Semua URL harus mendukung **POST** dan **GET** method
- Server harus mengembalikan **HTTP 200** response
- PHP 7.4+ dengan ekstensi JSON dan cURL

## Testing

Setelah setup, test dengan:
1. Kirim pesan ke WhatsApp bot
2. Check logs di `../logs/` folder
3. Monitor device status di dashboard
4. Test koneksi dengan curl:

```bash
curl -X POST https://moutogami.com/project_gabut_gw/public/whatsapp_webhook.php \
  -H "Content-Type: application/json" \
  -d '{"sender":"08123456789","message":"test","device":"your_device"}'
```

## Fitur Tambahan

- **Logging**: Semua aktivitas di-log ke file
- **Error Handling**: Response HTTP code yang tepat
- **Extensible**: Mudah ditambah integrasi lain
- **Backup**: Webhook chaining otomatis backup data
