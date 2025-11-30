# 🔧 Setup Smart Contract Deployment

**Panduan singkat untuk deploy smart contract ZK Payment**

---

## 📋 Quick Start

### 1. Buat File .env

```bash
# Di folder contracts/
copy env.template .env
# atau (Linux/Mac)
cp env.template .env
```

### 2. Isi Private Key

Edit file `contracts/.env`:

```env
DEPLOYER_PRIVATE_KEY=your_64_character_private_key_here
```

**⚠️ Format:** 64 karakter hex, TANPA prefix `0x`

**Contoh benar:**
```env
DEPLOYER_PRIVATE_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef
```

**Contoh salah:**
```env
DEPLOYER_PRIVATE_KEY=0x0123456789...  ❌ Ada 0x
```

---

## 🔑 Cara Dapat Private Key dari MetaMask

### Step 1: Buka MetaMask
- Klik icon MetaMask di browser

### Step 2: Export Private Key
1. Klik nama account di atas
2. Pilih **"Account Details"**
3. Klik **"Export Private Key"**
4. Masukkan password MetaMask
5. Copy private key yang muncul

### Step 3: Format Private Key
- Jika ada `0x` di depan → **HAPUS**
- Paste ke `DEPLOYER_PRIVATE_KEY`
- Pastikan 64 karakter

---

## 💰 Dapatkan MATIC Testnet

### Cara 1: Polygon Faucet (Recommended)

1. Buka: https://faucet.polygon.technology/
2. Pilih **"Polygon Amoy"**
3. Connect MetaMask atau paste address
4. Klik **"Request MATIC"**
5. Tunggu 1-2 menit
6. Check balance di MetaMask

### Cara 2: Alternative Faucets

- https://www.alchemy.com/faucets/polygon-amoy
- https://amoy-faucet.polygon.technology/

**Minimal:** 0.5 MATIC untuk deploy
**Recommended:** 1-2 MATIC untuk aman

---

## 🚀 Deploy Smart Contract

### Step 1: Install Dependencies

```bash
cd contracts
npm install
```

### Step 2: Compile Contracts

```bash
npm run compile
```

**Output yang diharapkan:**
```
Compiled 3 Solidity files successfully
```

### Step 3: Deploy ke Testnet (Amoy)

```bash
npm run deploy:amoy
```

**Output yang diharapkan:**
```
Starting deployment...
Deploying contracts with account: 0xYourAddress
Account balance: 1.234 MATIC

1. Deploying Groth16Verifier...
✓ Groth16Verifier deployed to: 0x1234567890...

2. Deploying ZKPayment...
✓ ZKPayment deployed to: 0xABCDEF1234...

✓ Deployment complete!

=== DEPLOYMENT SUMMARY ===
Network: amoy
Chain ID: 80002
Groth16Verifier: 0x1234567890...
ZKPayment: 0xABCDEF1234...        ← CATAT INI!
==========================
```

### Step 4: Catat Contract Address

**PENTING!** Copy address **ZKPayment** (bukan Groth16Verifier)

**Address juga tersimpan di:**
```
contracts/deployments/amoy.json
```

---

## 📝 Update Laravel .env

Setelah deploy berhasil, update file `.env` di root project:

```env
# Di .env root (bukan contracts/.env)
POLYGON_CONTRACT_ADDRESS=0xABCDEF1234...  ← Address ZKPayment
POLYGON_PRIVATE_KEY=your_private_key      ← SAMA dengan DEPLOYER_PRIVATE_KEY
POLYGON_MASTER_WALLET=0xYourAddress       ← Address wallet Anda
```

---

## ✅ Verify Contract (Optional tapi Recommended)

### Get Polygonscan API Key

1. Buka: https://polygonscan.com/
2. Sign up / Login
3. My Profile → API Keys
4. Create API Key (gratis)
5. Copy API key

### Update .env

```env
# contracts/.env
POLYGONSCAN_API_KEY=YOUR_API_KEY_HERE
```

### Run Verify

```bash
# Verify Groth16Verifier
npx hardhat verify --network amoy 0xVerifierAddress

# Verify ZKPayment
npx hardhat verify --network amoy 0xZKPaymentAddress 0xVerifierAddress
```

**Kenapa perlu verify?**
- ✅ User bisa lihat source code
- ✅ Meningkatkan trust
- ✅ Transparansi smart contract
- ✅ Mudah di-audit

---

## 🔍 Check Deployment

### Via Polygonscan

**Amoy Testnet:**
```
https://amoy.polygonscan.com/address/0xYourContractAddress
```

**Yang harus dicek:**
- ✅ Contract creation transaction success
- ✅ Balance: 0 MATIC (normal untuk baru deploy)
- ✅ Creator address = wallet Anda
- ✅ (Jika verified) Source code muncul

---

## 🆘 Troubleshooting

### Error: "insufficient funds for gas"

**Solusi:**
- Wallet tidak punya MATIC
- Ambil MATIC dari faucet lagi
- Perlu minimal 0.5 MATIC

### Error: "invalid private key"

**Solusi:**
- Private key format salah
- Pastikan 64 karakter hex
- Pastikan tidak ada `0x` di depan
- Tidak ada spasi atau newline

### Error: "network amoy not found"

**Solusi:**
```bash
# Re-install dependencies
rm -rf node_modules
npm install
```

### Error: "nonce too high"

**Solusi:**
- Reset MetaMask transaction counter
- MetaMask → Settings → Advanced → Reset Account
- Atau tunggu beberapa menit dan coba lagi

---

## 📊 File Structure

```
contracts/
├── .env                    ← File config Anda (create dari env.template)
├── env.template            ← Template (jangan edit)
├── hardhat.config.js       ← Config Hardhat
├── package.json            ← Dependencies & scripts
├── scripts/
│   └── deploy.js           ← Script deployment
├── contracts/
│   ├── ZKPayment.sol       ← Main contract
│   ├── Groth16Verifier.sol ← Verifier contract
│   └── IVerifier.sol       ← Interface
└── deployments/
    └── amoy.json           ← Deployment info (created after deploy)
```

---

## 🔐 Security Checklist

```
[ ] File .env TIDAK di-commit ke Git
[ ] Private key di-backup di tempat aman
[ ] Private key TIDAK di-share dengan siapapun
[ ] Untuk testnet production, wallet sama dengan runtime wallet
[ ] Wallet terpisah untuk mainnet (nanti)
[ ] Polygonscan API key optional tapi recommended
```

---

## 📚 Next Steps

Setelah deployment berhasil:

1. ✅ **Update Laravel .env**
   ```env
   POLYGON_CONTRACT_ADDRESS=0xYourZKPaymentAddress
   ```

2. ✅ **Test Connection**
   - Buat test script di Laravel
   - Check contract accessible dari backend

3. ✅ **Continue Deployment**
   - Lanjut ke: `PANDUAN_DEPLOYMENT.md`
   - Build Laravel assets
   - Upload ke hosting

---

## 🎯 Commands Reference

```bash
# Install
npm install

# Compile
npm run compile

# Deploy Testnet
npm run deploy:amoy

# Deploy Mainnet (hati-hati!)
npm run deploy:polygon

# Verify Testnet
npm run verify:amoy

# Verify Mainnet
npm run verify:polygon

# Test (if have test files)
npm run test
```

---

## 📞 Need Help?

**Resources:**
- Hardhat Docs: https://hardhat.org/docs
- Polygon Docs: https://docs.polygon.technology/
- Polygonscan: https://amoy.polygonscan.com/

**Common Issues:**
- Check: `FAQ_DEPLOYMENT.md` - Section "Smart Contract Deployment"
- Check: `PANDUAN_DEPLOYMENT.md` - Section "Deployment Smart Contract"

---

**Happy Deploying! 🚀**

