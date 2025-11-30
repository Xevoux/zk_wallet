# 🚀 Quick Start - Internal Faucet

## ⚡ 2-Minute Setup

### For Local Development (Simulation Mode)

```bash
# 1. Run migration (already done)
php artisan migrate

# 2. Start server
php artisan serve

# 3. Register new user
# Go to: http://localhost:8000/register
# Fill form and submit

# ✅ Done! User gets 0.5 MATIC automatically
```

**That's it!** No master wallet needed for development.

---

## 🔧 For Staging/UAT (Real Blockchain)

### Step 1: Get Master Wallet (5 minutes)

1. **Create wallet in MetaMask**
   - Install MetaMask
   - Create new account
   - Copy address

2. **Get test MATIC**
   - Visit: https://faucet.polygon.technology/
   - Select: Polygon Amoy
   - Paste address → Submit
   - Wait 30 seconds

3. **Export private key**
   - MetaMask → Account Details → Export Private Key
   - Copy the key

### Step 2: Configure .env (1 minute)

Add to `.env`:

```env
POLYGON_MASTER_WALLET=0xYourWalletAddress
POLYGON_PRIVATE_KEY=your_private_key_here
POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/
POLYGON_CHAIN_ID=80002
```

### Step 3: Apply & Test (30 seconds)

```bash
php artisan config:clear
php artisan serve
```

Register new user → Should receive real MATIC from blockchain!

---

## 📖 How It Works

### Auto-Distribution

```
User Register
    ↓
System creates wallet
    ↓
0.01 MATIC sent automatically
    ↓
User ready to use!
```

### Manual Request

```
User clicks "Request Test MATIC"
    ↓
System checks cooldown (24h)
    ↓
System checks master wallet balance (min 0.1 MATIC)
    ↓
If OK: Send 0.01 MATIC
    ↓
Balance updated
```

---

## 🎯 Key Features

- ✅ **Auto MATIC on register** - User gets 0.01 MATIC instantly
- ✅ **No MetaMask needed** - Custodial wallet managed by system
- ✅ **Manual request** - Button in wallet page (1x per 24h)
- ✅ **Rate limiting** - Prevent abuse with cooldown
- ✅ **Balance validation** - Master wallet must have min 0.1 MATIC
- ✅ **Simulation mode** - Works without master wallet (dev)
- ✅ **Real blockchain** - Works with master wallet (staging)

---

## 📍 Where to Find

### For Users:
1. Register → Auto receive MATIC
2. Login → Go to "My Wallet"
3. See "Test MATIC Faucet" card
4. Click "Request Test MATIC" button

### For Developers:

**New Files:**
- `app/Services/FaucetService.php`
- `database/migrations/..._create_faucet_requests_table.php`

**Modified Files:**
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/WalletController.php`
- `routes/web.php`
- `resources/views/payment/wallet.blade.php`

**Documentation:**
- `INTERNAL_FAUCET_GUIDE.md` - Complete guide
- `ENV_FAUCET_CONFIG.md` - Configuration guide
- `FAUCET_IMPLEMENTATION_SUMMARY.md` - Implementation details

---

## 🔍 Verify It Works

### Check Logs:

```bash
# Watch faucet activity
tail -f storage/logs/laravel.log | grep Faucet

# Should see:
# [FaucetService] Distributing test MATIC
# [FaucetService] Test MATIC distributed successfully
```

### Check Database:

```sql
-- See faucet requests
SELECT * FROM faucet_requests ORDER BY created_at DESC LIMIT 5;

-- Count distributions
SELECT COUNT(*), SUM(amount) FROM faucet_requests;
```

### Check User Balance:

```sql
-- See user wallets
SELECT user_id, wallet_address, polygon_address, balance 
FROM wallets 
ORDER BY created_at DESC;
```

---

## ⚠️ Important Notes

### For Development:
- ✅ Simulation mode works out of the box
- ✅ No blockchain needed
- ✅ Fast and free

### For Staging/Production:
- ⚠️ Need master wallet with test MATIC
- ⚠️ Configure .env properly
- ⚠️ Monitor master wallet balance
- ⚠️ Never commit .env to git
- ⚠️ Use separate test wallet

---

## 🆘 Quick Troubleshooting

### Issue: User not getting MATIC

**Check:**
1. Migration ran? → `php artisan migrate`
2. Config cached? → `php artisan config:clear`
3. Check logs → `grep Faucet storage/logs/laravel.log`

### Issue: "Master wallet belum dikonfigurasi"

**Solution:**
- Add `POLYGON_MASTER_WALLET` to .env
- Add `POLYGON_PRIVATE_KEY` to .env
- Run `php artisan config:clear`

**OR** just use simulation mode (works without master wallet!)

### Issue: Balance not updating

**Check:**
1. Database connection OK?
2. Wallet has polygon_address?
3. Check `faucet_requests` table

---

## 📚 More Information

- **Complete Guide**: [INTERNAL_FAUCET_GUIDE.md](INTERNAL_FAUCET_GUIDE.md)
- **Configuration**: [ENV_FAUCET_CONFIG.md](ENV_FAUCET_CONFIG.md)
- **Implementation**: [FAUCET_IMPLEMENTATION_SUMMARY.md](FAUCET_IMPLEMENTATION_SUMMARY.md)
- **Polygon Info**: [QUICK_REFERENCE_AMOY.md](QUICK_REFERENCE_AMOY.md)

---

## ✅ Ready to Go!

Your internal faucet is now ready!

**For Development:**
```bash
php artisan serve
# Just use it! No extra setup needed.
```

**For Staging:**
```bash
# 1. Setup master wallet (see above)
# 2. Configure .env
# 3. Clear config
php artisan config:clear
# 4. Done!
```

**Happy Testing! 🎉**

