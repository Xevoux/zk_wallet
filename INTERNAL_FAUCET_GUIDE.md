# Internal Test MATIC Faucet - Setup Guide

## 📝 Overview

Sistem **Internal Faucet** memungkinkan user untuk mendapatkan test MATIC secara otomatis tanpa perlu menggunakan MetaMask atau request manual dari faucet external. Sistem ini menggunakan **custodial wallet** yang dikelola oleh aplikasi.

## 🎯 Fitur Utama

### ✅ Yang Sudah Diimplementasikan:

1. **Auto-Distribution saat Register**
   - User otomatis mendapat 0.5 MATIC saat register
   - Wallet blockchain dibuat otomatis
   - Private key dienkripsi dan disimpan aman

2. **Manual Request Test MATIC**
   - Button di halaman wallet untuk request test MATIC
   - Rate limiting: 1 request per 24 jam
   - Countdown timer menunjukkan cooldown

3. **Faucet Service**
   - Centralized service untuk manage distribution
   - Tracking semua request di database
   - Support simulation mode (tanpa master wallet)

4. **Master Wallet Distribution**
   - Satu master wallet mendistribusikan ke semua user
   - Efficient gas management
   - Transaction tracking

## 🏗️ Arsitektur

```
User Register/Request
        ↓
    FaucetService
        ↓
    PolygonService (transferMatic)
        ↓
    Master Wallet → User Wallet (Blockchain)
        ↓
    Update Balance & Record Request
```

## ⚙️ Setup & Configuration

### 1. Environment Variables

Tambahkan di file `.env`:

```env
# Polygon Amoy Testnet (untuk testing)
POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/
POLYGON_CHAIN_ID=80002
POLYGON_NETWORK=amoy

# Master Wallet (untuk distribute test MATIC)
POLYGON_MASTER_WALLET=0xYourMasterWalletAddress
POLYGON_PRIVATE_KEY=your_master_wallet_private_key
```

### 2. Mendapatkan Test MATIC untuk Master Wallet

**Langkah-langkah:**

1. **Buat Master Wallet:**
   - Install MetaMask
   - Buat wallet baru
   - Copy address dan private key

2. **Tambahkan Polygon Amoy Network ke MetaMask:**
   - Network Name: `Polygon Amoy Testnet`
   - RPC URL: `https://rpc-amoy.polygon.technology/`
   - Chain ID: `80002`
   - Currency: `MATIC`
   - Block Explorer: `https://amoy.polygonscan.com/`

3. **Request Test MATIC dari Faucet Official:**
   - Visit: https://faucet.polygon.technology/
   - Select: **Polygon Amoy**
   - Paste master wallet address
   - Request test MATIC (biasanya dapat 2-5 MATIC)
   - Wait ~30 seconds

4. **Setup di .env:**
   ```env
   POLYGON_MASTER_WALLET=0xYourAddressFromMetaMask
   POLYGON_PRIVATE_KEY=YourPrivateKeyFromMetaMask
   ```

5. **⚠️ PENTING - Keamanan:**
   - **NEVER commit `.env` to git**
   - **ONLY use test wallet for testnet**
   - **NEVER use real private key**
   - Generate separate test wallet for development

### 3. Database Migration

Migration sudah dibuat dan dijalankan:

```bash
php artisan migrate
```

Table `faucet_requests` akan dibuat dengan struktur:
- `id` - Primary key
- `user_id` - Foreign key ke users
- `wallet_address` - Polygon address
- `amount` - Jumlah MATIC yang didistribusikan
- `tx_hash` - Transaction hash blockchain
- `is_simulation` - Flag untuk simulation mode
- `created_at` - Timestamp

## 🚀 Cara Penggunaan

### User Flow

#### 1. Register (Auto-Distribution)

```
User Register
    ↓
Wallet Created (with Polygon Address)
    ↓
Auto-receive 0.5 Test MATIC
    ↓
Ready to use!
```

**User tidak perlu:**
- Install MetaMask
- Request dari faucet external
- Setup network manually

#### 2. Manual Request (di Halaman Wallet)

1. User login dan buka halaman **My Wallet**
2. Lihat card **"Test MATIC Faucet"**
3. Klik button **"Request Test MATIC"**
4. Sistem check cooldown (24 jam)
5. Jika eligible, MATIC dikirim otomatis
6. Balance updated

**Rate Limiting:**
- ✅ 1 request per 24 jam
- ❌ Cannot request if cooldown active
- ⏰ Countdown timer shows remaining time

## 📊 Monitoring & Tracking

### Check Faucet Statistics

```php
// Get total distributed MATIC
$faucetService = app(\App\Services\FaucetService::class);
$totalDistributed = $faucetService->getTotalDistributed();

// Get user's faucet history
$history = $faucetService->getFaucetHistory($userId);
```

### Database Query

```sql
-- Total MATIC distributed
SELECT SUM(amount) as total FROM faucet_requests WHERE is_simulation = 0;

-- Requests per user
SELECT user_id, COUNT(*) as requests, SUM(amount) as total_matic 
FROM faucet_requests 
GROUP BY user_id;

-- Recent requests
SELECT * FROM faucet_requests ORDER BY created_at DESC LIMIT 10;
```

## 🔒 Security Considerations

### Implemented Security:

1. **Rate Limiting**
   - 1 request per 24 hours per user
   - Prevents abuse

2. **Private Key Encryption**
   - User private keys encrypted di database
   - Using Laravel's encryption

3. **Master Wallet Protection**
   - Private key di .env (not in git)
   - Separate test wallet

4. **Transaction Verification**
   - All requests logged
   - TX hash tracked on blockchain

### Best Practices:

- ✅ Use separate test wallet for master
- ✅ Never commit `.env` file
- ✅ Monitor faucet balance regularly
- ✅ Set up alerts for low balance
- ✅ Rotate master wallet periodically

## 🆚 Simulation Mode vs Real Mode

### Simulation Mode (No Master Wallet)

**Activated when:**
- `POLYGON_MASTER_WALLET` not set in .env
- `POLYGON_PRIVATE_KEY` not set

**Behavior:**
- Generate fake transaction hash
- Update database with simulation flag
- Update user balance locally
- **NO real blockchain transaction**

**Use for:**
- Local development
- Testing UI/UX
- Demo without blockchain

### Real Mode (With Master Wallet)

**Activated when:**
- Master wallet configured in .env
- Private key available

**Behavior:**
- Real blockchain transaction
- Actual MATIC transfer
- Real gas fees
- Transaction on Polygon Amoy

**Use for:**
- Staging environment
- End-to-end testing
- User acceptance testing (UAT)

## 📈 Capacity Planning

### With 1 Test MATIC in Master Wallet:

| Scenario | Users | MATIC per User | Total MATIC |
|----------|-------|----------------|-------------|
| Register only | 100 | 0.01 | 1.0 |
| Register + 1 request | 50 | 0.02 | 1.0 |
| Register + 2 requests | ~33 | 0.03 | 0.99 |

**Minimum Requirement:**
- Master wallet **MUST have at least 0.1 MATIC**
- System validates balance before distribution
- Error shown if balance too low

**Recommendation:**
- Keep at least **0.5-1 MATIC** in master wallet
- Monitor balance via dashboard  
- Refill when < 0.2 MATIC
- Much more efficient: 1 MATIC = 100 users!

## 🔄 Migration to Production

### From Testnet to Mainnet:

1. **Update .env:**
   ```env
   # Change to Polygon Mainnet
   POLYGON_RPC_URL=https://polygon-rpc.com/
   POLYGON_CHAIN_ID=137
   POLYGON_NETWORK=mainnet
   
   # Use production master wallet (with real MATIC)
   POLYGON_MASTER_WALLET=0xProductionAddress
   POLYGON_PRIVATE_KEY=production_private_key
   ```

2. **Disable Faucet for Production:**
   
   Option A - Remove faucet button from UI
   Option B - Add environment check:
   
   ```php
   // In blade template
   @if(env('APP_ENV') !== 'production')
       <!-- Faucet card -->
   @endif
   ```

3. **Alternative for Production:**
   - Keep faucet disabled
   - Force users to use Midtrans top-up
   - Or reduce amount to 0.001 MATIC (for gas only)

## 🐛 Troubleshooting

### Issue: "Master wallet belum dikonfigurasi"

**Solution:**
1. Check `.env` has `POLYGON_MASTER_WALLET`
2. Check `.env` has `POLYGON_PRIVATE_KEY`
3. Run: `php artisan config:clear`

### Issue: "Insufficient funds in master wallet"

**Solution:**
1. Check master wallet balance:
   ```bash
   cast balance YOUR_MASTER_WALLET --rpc-url https://rpc-amoy.polygon.technology/
   ```
2. Request more test MATIC from faucet
3. Wait 24 hours between faucet requests

### Issue: "Transaction failed"

**Solution:**
1. Check RPC endpoint is accessible
2. Verify chain ID is correct (80002 for Amoy)
3. Check private key format
4. Ensure master wallet has gas for transaction

### Issue: "Faucet cooldown not working"

**Solution:**
1. Check `faucet_requests` table exists
2. Run: `php artisan migrate`
3. Clear cache: `php artisan cache:clear`

## 📞 Support & Maintenance

### Regular Maintenance Tasks:

**Daily:**
- ✅ Monitor faucet request logs
- ✅ Check for failed transactions

**Weekly:**
- ✅ Review faucet balance
- ✅ Check abuse patterns
- ✅ Clean up old logs

**Monthly:**
- ✅ Refill master wallet if needed
- ✅ Review rate limiting rules
- ✅ Update documentation

### Logs Location:

```bash
# Laravel logs
storage/logs/laravel.log

# Filter faucet logs
grep -E "FaucetService|WalletController.*faucet" storage/logs/laravel.log

# Real-time monitoring
tail -f storage/logs/laravel.log | grep Faucet
```

## 🎓 API Reference

### Request Test MATIC

```javascript
POST /wallet/faucet/request

Response:
{
  "success": true,
  "amount": 0.5,
  "tx_hash": "0x...",
  "simulation": false,
  "message": "Test MATIC berhasil dikirim ke wallet Anda!"
}
```

### Check Cooldown

```javascript
GET /wallet/faucet/can-request

Response:
{
  "success": true,
  "can_request": true,
  "remaining_seconds": 0
}
```

### Get History

```javascript
GET /wallet/faucet/history

Response:
{
  "success": true,
  "requests": [
    {
      "id": 1,
      "user_id": 1,
      "wallet_address": "0x...",
      "amount": 0.5,
      "tx_hash": "0x...",
      "created_at": "2024-11-29T12:00:00Z"
    }
  ]
}
```

## 📚 Related Documentation

- [QUICK_REFERENCE_AMOY.md](QUICK_REFERENCE_AMOY.md) - Polygon Amoy quick reference
- [INTEGRATION_SUMMARY_TOPUP.md](INTEGRATION_SUMMARY_TOPUP.md) - Top-up integration
- [TOPUP_SETUP_GUIDE.md](TOPUP_SETUP_GUIDE.md) - Midtrans top-up setup

---

**Last Updated**: November 29, 2024  
**Version**: 1.0.0  
**Status**: ✅ Production Ready (for Testnet)

