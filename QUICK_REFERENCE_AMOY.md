# Polygon Amoy - Quick Reference

## 🚀 Quick Start

### Network Details

```
Network Name: Polygon Amoy Testnet
RPC URL: https://rpc-amoy.polygon.technology/
Chain ID: 80002
Chain ID (Hex): 0x13882
Currency: MATIC
Block Explorer: https://amoy.polygonscan.com/
Faucet: https://faucet.polygon.technology/
```

### Add to MetaMask (Quick)

Klik tombol ini atau tambahkan manual dengan detail di atas:
- Network Name: `Polygon Amoy Testnet`
- RPC URL: `https://rpc-amoy.polygon.technology/`
- Chain ID: `80002`

## ⚙️ Configuration

### .env File
```env
POLYGON_NETWORK=amoy
POLYGON_CHAIN_ID=80002
POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/

# Master Wallet untuk Internal Faucet (Optional)
POLYGON_MASTER_WALLET=0xYourMasterWalletAddress
POLYGON_PRIVATE_KEY=your_master_wallet_private_key
```

### Alternative RPC URLs
```env
# Alchemy
https://polygon-amoy.g.alchemy.com/v2/YOUR_KEY

# Infura
https://polygon-amoy.infura.io/v3/YOUR_KEY

# PublicNode
https://polygon-amoy-bor-rpc.publicnode.com
```

## 📦 Deployment Commands

### Compile
```bash
cd contracts
npm run compile
```

### Deploy to Amoy
```bash
npm run deploy:amoy
```

### Verify Contract
```bash
npm run verify:amoy CONTRACT_ADDRESS
```

## 💰 Get Test MATIC

### Method 1: Internal Faucet (Recommended for Testing)

**Automatic Distribution:**
- ✅ User otomatis dapat 0.5 MATIC saat register
- ✅ No MetaMask required
- ✅ Custodial wallet managed by system

**Manual Request:**
1. Login ke aplikasi
2. Buka halaman **My Wallet**
3. Klik **"Request Test MATIC"** button
4. Dapatkan 0.5 MATIC instantly
5. Limit: 1 request per 24 jam

📖 **Setup Guide:** See [INTERNAL_FAUCET_GUIDE.md](INTERNAL_FAUCET_GUIDE.md)

### Method 2: Official Polygon Faucet (For Master Wallet)

1. Visit: https://faucet.polygon.technology/
2. Select: **Polygon Amoy**
3. Enter your wallet address
4. Wait ~30 seconds
5. Get 2-5 MATIC per request

## 🔍 Useful Links

| Resource | URL |
|----------|-----|
| **RPC Endpoint** | https://rpc-amoy.polygon.technology/ |
| **Block Explorer** | https://amoy.polygonscan.com/ |
| **Faucet** | https://faucet.polygon.technology/ |
| **Alchemy Dashboard** | https://dashboard.alchemy.com/ |
| **Infura Dashboard** | https://infura.io/dashboard |
| **Polygon Docs** | https://docs.polygon.technology/ |

## 🛠️ Common Commands

```bash
# Check balance
cast balance YOUR_ADDRESS --rpc-url https://rpc-amoy.polygon.technology/

# Get latest block
cast block-number --rpc-url https://rpc-amoy.polygon.technology/

# Get transaction
cast tx TX_HASH --rpc-url https://rpc-amoy.polygon.technology/

# Get contract code
cast code CONTRACT_ADDRESS --rpc-url https://rpc-amoy.polygon.technology/
```

## 📝 Web3.js / Ethers.js

### Web3.js
```javascript
const Web3 = require('web3');
const web3 = new Web3('https://rpc-amoy.polygon.technology/');

// Check chain ID
const chainId = await web3.eth.getChainId();
console.log(chainId); // 80002
```

### Ethers.js
```javascript
const { ethers } = require('ethers');
const provider = new ethers.JsonRpcProvider('https://rpc-amoy.polygon.technology/');

// Check network
const network = await provider.getNetwork();
console.log(network.chainId); // 80002n
```

## 🔐 Security Notes

- ⚠️ **NEVER** use real private keys on testnet
- ⚠️ **NEVER** commit `.env` file to git
- ⚠️ Generate separate test wallets for development
- ⚠️ Test MATIC has no real value

## 🆚 Mumbai vs Amoy

| Feature | Mumbai | Amoy |
|---------|--------|------|
| Chain ID | 80001 | **80002** |
| Status | ❌ Deprecated | ✅ Active |
| Support | Limited | Full |
| Faucet | May not work | ✅ Working |

## 📚 Documentation

- **Internal Faucet Guide**: `INTERNAL_FAUCET_GUIDE.md` ⭐ NEW
- **Top-Up Integration**: `INTEGRATION_SUMMARY_TOPUP.md`
- **Top-Up Setup**: `TOPUP_SETUP_GUIDE.md`
- **Migration Guide**: `MIGRATION_MUMBAI_TO_AMOY.md`
- **Setup Guide**: `SETUP_GUIDE.md`
- **Config Guide**: `ENV_CONFIG.md`
- **Changelog**: `CHANGELOG_AMOY_MIGRATION.md`

## ⚡ Troubleshooting

### "Insufficient funds"
→ Get test MATIC from faucet

### "Chain ID mismatch"
→ Check MetaMask is on Amoy (80002)

### "Contract not found"
→ Redeploy contract to Amoy

### "RPC Error"
→ Try alternative RPC endpoint

### "Faucet not working"
→ Wait 24h between requests

## 📞 Support

Having issues? Check:
1. Your `.env` configuration
2. MetaMask network settings
3. Contract deployment status
4. RPC endpoint availability

---

**Quick Access**: Bookmark this page for fast reference!

**Last Updated**: November 2025

