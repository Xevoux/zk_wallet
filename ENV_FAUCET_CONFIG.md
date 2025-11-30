# Environment Configuration for Internal Faucet

## 📝 Required .env Variables

Tambahkan konfigurasi berikut ke file `.env` Anda:

### Basic Configuration (Required)

```env
# Polygon Network Configuration
POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/
POLYGON_CHAIN_ID=80002
POLYGON_NETWORK=amoy
```

### Master Wallet Configuration (Optional)

```env
# Master Wallet untuk Internal Faucet
# Leave empty untuk Simulation Mode
# Fill untuk Real Blockchain Mode

POLYGON_MASTER_WALLET=0xYourMasterWalletAddress
POLYGON_PRIVATE_KEY=your_master_wallet_private_key
```

## 🔧 Configuration Modes

### Mode 1: Simulation Mode (Default)

**When to use:**
- Local development
- Testing UI/UX
- Demo without blockchain

**Configuration:**
```env
# Just leave master wallet empty
# POLYGON_MASTER_WALLET=
# POLYGON_PRIVATE_KEY=
```

**Behavior:**
- Generates fake transaction hash
- Updates balance locally in database
- No real blockchain transaction
- Fast and free

### Mode 2: Real Blockchain Mode

**When to use:**
- Staging environment
- End-to-end testing
- User acceptance testing (UAT)

**Configuration:**
```env
POLYGON_MASTER_WALLET=0x1234567890abcdef1234567890abcdef12345678
POLYGON_PRIVATE_KEY=abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890
```

**Behavior:**
- Real blockchain transactions
- Actual MATIC transfer
- Transaction recorded on Polygon Amoy
- Requires master wallet with test MATIC

## 🚀 Setup Instructions

### Step 1: Create Master Wallet

1. Install MetaMask browser extension
2. Create new account (separate from your main wallet)
3. Copy the address and private key

### Step 2: Add Polygon Amoy Network

Add network to MetaMask with these details:

| Setting | Value |
|---------|-------|
| Network Name | Polygon Amoy Testnet |
| RPC URL | https://rpc-amoy.polygon.technology/ |
| Chain ID | 80002 |
| Currency Symbol | MATIC |
| Block Explorer | https://amoy.polygonscan.com/ |

### Step 3: Get Test MATIC

1. Visit: https://faucet.polygon.technology/
2. Select: **Polygon Amoy**
3. Enter your master wallet address
4. Click "Submit"
5. Wait ~30 seconds
6. You should receive 2-5 test MATIC

### Step 4: Configure .env

Add to your `.env` file:

```env
POLYGON_MASTER_WALLET=0xYourAddressFromMetaMask
POLYGON_PRIVATE_KEY=YourPrivateKeyFromMetaMask
```

### Step 5: Clear Config Cache

```bash
php artisan config:clear
php artisan config:cache
```

### Step 6: Test

```bash
# Register new user
# Should automatically receive 0.5 MATIC

# Or test manually
php artisan tinker
>>> $service = app(\App\Services\FaucetService::class);
>>> $result = $service->distributeTestMatic('0xSomeAddress');
>>> print_r($result);
```

## 🔐 Security Best Practices

### ⚠️ IMPORTANT:

1. **NEVER commit .env to git**
   ```bash
   # Make sure .env is in .gitignore
   echo ".env" >> .gitignore
   ```

2. **NEVER use real private key on testnet**
   - Create separate test wallet
   - Only use test MATIC (no real value)

3. **NEVER use testnet key on mainnet**
   - Different keys for different environments
   - Production key should be separate

4. **Rotate keys periodically**
   - Change master wallet every few months
   - Update .env and transfer remaining MATIC

5. **Monitor master wallet balance**
   ```bash
   # Check balance
   cast balance $POLYGON_MASTER_WALLET --rpc-url https://rpc-amoy.polygon.technology/
   ```

6. **Set up alerts**
   - Alert when balance < 2 MATIC
   - Alert on unusual activity

## 📊 Capacity Planning

### Master Wallet Balance vs Users

| Master Wallet MATIC | Max Users (0.01 each) | Max Requests |
|---------------------|------------------------|--------------|
| 0.1 MATIC | 10 users | ~10 requests |
| 0.5 MATIC | 50 users | ~50 requests |
| 1 MATIC | 100 users | ~100 requests |
| 5 MATIC | 500 users | ~500 requests |
| 10 MATIC | 1000 users | ~1000 requests |

**Minimum Requirement:**
- Master wallet must have **at least 0.1 MATIC**
- System checks balance before each distribution
- Error message shown if balance too low

**Recommendation:**
- Start with **0.5-1 MATIC** for testing
- Monitor usage weekly
- Refill when < 0.2 MATIC

### Gas Fees

On Polygon Amoy Testnet:
- Gas fees are very low (~0.0001 MATIC)
- Negligible impact on capacity
- 5 MATIC ≈ 10 users with buffer for gas

## 🔄 Alternative RPC Endpoints

If default RPC is slow or down, try alternatives:

### Alchemy (Recommended)
```env
POLYGON_RPC_URL=https://polygon-amoy.g.alchemy.com/v2/YOUR_ALCHEMY_KEY
```

1. Sign up: https://dashboard.alchemy.com/
2. Create new app (Polygon Amoy)
3. Copy API key
4. Use URL above

### Infura
```env
POLYGON_RPC_URL=https://polygon-amoy.infura.io/v3/YOUR_INFURA_KEY
```

1. Sign up: https://infura.io/
2. Create new project
3. Enable Polygon Amoy
4. Copy project ID

### PublicNode
```env
POLYGON_RPC_URL=https://polygon-amoy-bor-rpc.publicnode.com
```

Free, no API key required.

## 🧪 Testing Configuration

### Test Simulation Mode

```bash
# Remove master wallet from .env
POLYGON_MASTER_WALLET=
POLYGON_PRIVATE_KEY=

# Clear config
php artisan config:clear

# Test
# Register new user - should get simulated MATIC
```

### Test Real Mode

```bash
# Add master wallet to .env
POLYGON_MASTER_WALLET=0x...
POLYGON_PRIVATE_KEY=...

# Clear config
php artisan config:clear

# Test
# Register new user - should get real MATIC from blockchain
```

### Verify Transaction

```bash
# Get transaction hash from logs
grep "Test MATIC distributed" storage/logs/laravel.log

# View on explorer
# https://amoy.polygonscan.com/tx/YOUR_TX_HASH
```

## 📈 Monitoring

### Check Master Wallet Balance

```bash
# Using cast (foundry)
cast balance $POLYGON_MASTER_WALLET --rpc-url https://rpc-amoy.polygon.technology/

# Using curl
curl -X POST https://rpc-amoy.polygon.technology/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "eth_getBalance",
    "params": ["'$POLYGON_MASTER_WALLET'", "latest"],
    "id": 1
  }'
```

### Check Faucet Statistics

```sql
-- Total MATIC distributed
SELECT SUM(amount) as total_matic 
FROM faucet_requests 
WHERE is_simulation = 0;

-- Requests today
SELECT COUNT(*) as requests_today 
FROM faucet_requests 
WHERE DATE(created_at) = CURDATE();

-- Master wallet usage
SELECT 
    COUNT(*) as total_requests,
    SUM(amount) as total_matic,
    AVG(amount) as avg_per_request
FROM faucet_requests 
WHERE is_simulation = 0;
```

### Monitor Logs

```bash
# Watch faucet activity
tail -f storage/logs/laravel.log | grep -E "FaucetService|Distribution"

# Check for errors
grep -i "error.*faucet" storage/logs/laravel.log

# View recent distributions
grep "Test MATIC distributed" storage/logs/laravel.log | tail -10
```

## 🐛 Troubleshooting

### Issue: Configuration not loading

```bash
php artisan config:clear
php artisan config:cache
php artisan cache:clear
```

### Issue: Master wallet not working

```bash
# Verify .env has correct values
cat .env | grep POLYGON_

# Check master wallet balance
cast balance $POLYGON_MASTER_WALLET --rpc-url https://rpc-amoy.polygon.technology/

# Test connectivity
curl https://rpc-amoy.polygon.technology/ \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'
```

### Issue: Private key format error

Private key should be:
- 64 hex characters (without 0x prefix)
- OR 66 characters (with 0x prefix)

```bash
# Good formats:
abc123...def789 (64 chars)
0xabc123...def789 (66 chars)

# Bad formats:
abc 123 ... (with spaces)
ABC123... (case matters in some contexts)
```

### Issue: Insufficient funds

```bash
# Check balance
cast balance $POLYGON_MASTER_WALLET --rpc-url https://rpc-amoy.polygon.technology/

# If low, request more from faucet
# https://faucet.polygon.technology/

# Note: Faucet limits 1 request per 24 hours per address
```

## 📚 Related Documentation

- **Setup Guide**: [INTERNAL_FAUCET_GUIDE.md](INTERNAL_FAUCET_GUIDE.md)
- **Implementation**: [FAUCET_IMPLEMENTATION_SUMMARY.md](FAUCET_IMPLEMENTATION_SUMMARY.md)
- **Quick Reference**: [QUICK_REFERENCE_AMOY.md](QUICK_REFERENCE_AMOY.md)

---

**Last Updated**: November 29, 2024  
**Version**: 1.0.0

