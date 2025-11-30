# Setup Guide - Top-Up Saldo dengan Midtrans & Polygon

Panduan lengkap untuk mengintegrasikan fitur top-up saldo menggunakan Midtrans Payment Gateway dan Polygon Blockchain.

## 📋 Daftar Isi

1. [Persyaratan](#persyaratan)
2. [Konfigurasi Environment](#konfigurasi-environment)
3. [Setup Midtrans](#setup-midtrans)
4. [Setup Polygon Wallet](#setup-polygon-wallet)
5. [Migrasi Database](#migrasi-database)
6. [Testing](#testing)
7. [Deployment](#deployment)

## Persyaratan

### Software Requirements
- PHP 8.2 atau lebih tinggi
- Composer
- MySQL/MariaDB
- Node.js & NPM (untuk Vite)
- Laravel 11.x

### Service Requirements
- Akun Midtrans (Sandbox untuk testing)
- Polygon Wallet dengan MATIC untuk gas fees
- RPC Endpoint (Alchemy, Infura, atau public RPC)

## Konfigurasi Environment

### 1. Salin dan Edit File .env

Tambahkan konfigurasi berikut ke file `.env` Anda:

```env
# ======================================
# POLYGON BLOCKCHAIN CONFIGURATION
# ======================================
POLYGON_NETWORK=testnet
POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/
POLYGON_CHAIN_ID=80002
POLYGON_CONTRACT_ADDRESS=
POLYGON_PRIVATE_KEY=your_private_key_here
POLYGON_MASTER_WALLET=your_master_wallet_address

# ======================================
# MIDTRANS PAYMENT GATEWAY
# ======================================
MIDTRANS_SERVER_KEY=your_server_key_here
MIDTRANS_CLIENT_KEY=your_client_key_here
MIDTRANS_IS_PRODUCTION=false
```

## Setup Midtrans

### 1. Daftar Akun Midtrans

1. Kunjungi [Midtrans Dashboard](https://dashboard.midtrans.com/)
2. Registrasi atau login ke akun Anda
3. Pilih environment **Sandbox** untuk testing

### 2. Dapatkan API Credentials

1. Masuk ke **Settings** → **Access Keys**
2. Salin **Server Key** dan **Client Key**
3. Masukkan ke file `.env`:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxxxxxxx
```

### 3. Setup Webhook/Notification URL

1. Di Midtrans Dashboard, masuk ke **Settings** → **Configuration**
2. Set **Payment Notification URL** ke:
   ```
   https://yourdomain.com/webhook/midtrans
   ```
3. Untuk local testing, gunakan ngrok atau tools serupa:
   ```bash
   ngrok http 8000
   ```
   Lalu set notification URL ke:
   ```
   https://your-ngrok-url.ngrok.io/webhook/midtrans
   ```

### 4. Aktifkan Payment Methods

Di Midtrans Dashboard → **Settings** → **Payment**, aktifkan:
- ☑️ GoPay
- ☑️ QRIS
- ☑️ Bank Transfer (BCA VA, Mandiri Bill, BNI VA, BRI VA)
- ☑️ Credit Card
- ☑️ E-Wallet lainnya (OVO, ShopeePay, dll)

## Setup Polygon Wallet

### 1. Buat Master Wallet

Master wallet digunakan untuk mengirim MATIC ke user setelah top-up berhasil.

**Cara 1: Menggunakan MetaMask**
1. Install [MetaMask](https://metamask.io/)
2. Buat wallet baru atau import existing
3. Switch ke **Polygon Amoy Testnet**
   - Network Name: `Polygon Amoy Testnet`
   - RPC URL: `https://rpc-amoy.polygon.technology/`
   - Chain ID: `80002`
   - Currency Symbol: `MATIC`
   - Block Explorer: `https://www.oklink.com/amoy`

4. Export private key:
   - Klik 3 titik → Account Details → Export Private Key
   - **⚠️ JANGAN PERNAH SHARE PRIVATE KEY!**

**Cara 2: Menggunakan Web3.php (Programmatic)**
```php
// Akan di-generate otomatis saat user register
```

### 2. Dapatkan Test MATIC

Untuk testing di Amoy Testnet:
1. Kunjungi [Polygon Faucet](https://faucet.polygon.technology/)
2. Masukkan address wallet Anda
3. Pilih network **Polygon Amoy**
4. Request MATIC (akan mendapat sekitar 0.2 MATIC)

### 3. Set Environment Variables

```env
# Private key dari master wallet (tanpa 0x prefix)
POLYGON_PRIVATE_KEY=your_private_key_without_0x

# Address dari master wallet
POLYGON_MASTER_WALLET=0xYourMasterWalletAddress
```

### 4. Setup RPC Endpoint (Opsional tapi Recommended)

Untuk production, gunakan RPC provider yang reliable:

**Alchemy:**
1. Daftar di [Alchemy](https://www.alchemy.com/)
2. Buat app baru untuk Polygon
3. Copy RPC URL
4. Update `.env`:
   ```env
   POLYGON_RPC_URL=https://polygon-amoy.g.alchemy.com/v2/your_api_key
   ```

**Infura:**
1. Daftar di [Infura](https://www.infura.io/)
2. Buat project baru
3. Pilih Polygon network
4. Copy endpoint URL

## Migrasi Database

### 1. Jalankan Migrasi

```bash
php artisan migrate
```

Ini akan membuat tabel:
- `topup_transactions` - Menyimpan transaksi top-up
- Update `wallets` table dengan field blockchain sync

### 2. Verifikasi Tabel

```bash
php artisan tinker
```

```php
// Cek tabel topup_transactions
DB::table('topup_transactions')->count();

// Cek struktur wallet
DB::getSchemaBuilder()->getColumnListing('wallets');
```

## Testing

### 1. Testing Top-Up Flow

1. Login ke aplikasi
2. Navigasi ke halaman Wallet
3. Klik tombol "Top-Up Saldo"
4. Pilih nominal (contoh: IDR 50.000)
5. Klik "Lanjutkan Pembayaran"
6. Akan muncul Snap Midtrans
7. Pilih payment method
8. Gunakan kredensial test (untuk sandbox):

**Credit Card Test:**
```
Card Number: 4811 1111 1111 1114
CVV: 123
Exp Date: 01/25
OTP: 112233
```

**GoPay Test:**
- Akan muncul QR code atau redirect
- Otomatis success di sandbox mode

### 2. Monitoring Logs

```bash
tail -f storage/logs/laravel.log
```

Perhatikan log untuk:
- `[MidtransService]` - Transaksi Midtrans
- `[PolygonService]` - Transaksi blockchain
- `[WalletController]` - Flow top-up

### 3. Test Webhook Notification

Gunakan Postman atau curl untuk test webhook:

```bash
curl -X POST http://localhost:8000/webhook/midtrans \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "TOPUP-1-1234567890-ABC123",
    "transaction_status": "settlement",
    "status_code": "200",
    "gross_amount": "50000",
    "signature_key": "calculated_signature",
    "payment_type": "gopay",
    "transaction_time": "2024-11-29 12:00:00"
  }'
```

### 4. Test Balance Sync

1. Di halaman wallet, klik icon refresh/sync
2. Balance akan diupdate dari blockchain
3. Cek logs untuk proses sync

## Deployment

### 1. Production Environment

Update `.env` untuk production:

```env
APP_ENV=production
APP_DEBUG=false

POLYGON_NETWORK=mainnet
POLYGON_RPC_URL=https://polygon-mainnet.g.alchemy.com/v2/your_api_key
POLYGON_CHAIN_ID=137

MIDTRANS_IS_PRODUCTION=true
MIDTRANS_SERVER_KEY=Mid-server-production-key
MIDTRANS_CLIENT_KEY=Mid-client-production-key
```

### 2. Security Checklist

- ☑️ Environment variables aman (jangan di-commit)
- ☑️ Private key terenkripsi di database
- ☑️ HTTPS aktif untuk webhook
- ☑️ Rate limiting untuk API endpoints
- ☑️ Input validation untuk semua request
- ☑️ Webhook signature verification aktif

### 3. Optimize untuk Production

```bash
# Clear dan cache config
php artisan config:clear
php artisan config:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Build frontend assets
npm run build

# Setup queue worker untuk async processing
php artisan queue:work --daemon
```

### 4. Monitoring & Maintenance

**Setup Cron Job untuk Sync Balance:**

```cron
# Sync balance setiap 5 menit
*/5 * * * * cd /path/to/app && php artisan wallet:sync-balances
```

**Create Command:**
```php
// app/Console/Commands/SyncWalletBalances.php
php artisan make:command SyncWalletBalances
```

## Troubleshooting

### Error: "Transaction not found"
- Cek apakah webhook URL sudah benar
- Pastikan signature verification benar
- Cek logs Midtrans Dashboard

### Error: "Failed to transfer MATIC"
- Cek balance master wallet
- Pastikan private key benar
- Verifikasi RPC endpoint accessible
- Cek gas price dan network congestion

### Balance tidak update
- Trigger manual sync dari UI
- Cek `last_blockchain_sync` timestamp
- Verifikasi polygon_address exist
- Pastikan RPC endpoint working

### Midtrans Snap tidak muncul
- Cek client key benar
- Pastikan Snap script loaded
- Buka console browser untuk error
- Verifikasi CORS settings

## Fitur Tambahan (Opsional)

### 1. Auto-Convert Rate Caching

Untuk mengurangi API calls ke CoinGecko, cache rate:

```php
// config/cache.php
'matic_rate' => [
    'ttl' => 300, // 5 minutes
],
```

### 2. Queue untuk Blockchain Transfer

Process blockchain transfer secara async:

```php
// Dispatch job
ProcessTopUpTransfer::dispatch($transaction);
```

### 3. Email Notification

Kirim email konfirmasi setelah top-up berhasil:

```php
Mail::to($user)->send(new TopUpSuccessMail($transaction));
```

## Support & Resources

- **Midtrans Docs:** https://docs.midtrans.com/
- **Polygon Docs:** https://docs.polygon.technology/
- **Web3.php:** https://github.com/web3p/web3.php
- **Laravel Docs:** https://laravel.com/docs

## License

Proprietary - All rights reserved

---

**⚠️ PENTING:** 
- Jangan pernah commit file `.env` ke repository
- Simpan private key dengan aman
- Gunakan testnet untuk development
- Audit smart contract sebelum production

