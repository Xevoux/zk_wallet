# 🔐 ZK Payment - Sistem Pembayaran Digital dengan Zero-Knowledge Proof

Sistem pembayaran digital peer-to-peer yang menggunakan teknologi **Zero-Knowledge Proof (zk-SNARKs)** untuk privasi transaksi dan terintegrasi dengan **Polygon Blockchain** untuk keamanan dan transparansi.

## ✨ Fitur Utama

### 🔒 Zero-Knowledge Proof (zk-SNARKs)
- **Login Privat**: Autentikasi tanpa mengungkap password
- **Verifikasi Saldo**: Membuktikan saldo mencukupi tanpa mengungkap jumlah sebenarnya
- **Transaksi Privat**: Melakukan transaksi tanpa mengekspos detail kepada publik

### ⛓️ Blockchain Integration
- Terintegrasi dengan **Polygon Network** (Amoy Testnet / Mainnet)
- Semua transaksi tercatat on-chain untuk transparansi
- Biaya gas rendah menggunakan Polygon
- Smart contract untuk validasi transaksi

### 📱 QR Code P2P Payment
- **Generate QR Code**: Buat QR untuk menerima pembayaran (default atau dengan amount tertentu)
- **Scan QR Code**: Scan dengan camera atau manual input untuk bayar
- **Download QR**: Download QR code sebagai PNG untuk sharing
- **Dual Mode**: Support wallet address QR dan payment request QR
- Transaksi peer-to-peer yang mudah dan cepat

### 💳 Digital Wallet
- **Wallet Management**: Lihat saldo, alamat, dan public key
- **Wallet otomatis**: Dibuat otomatis untuk setiap user baru
- **QR Code Integration**: Generate QR untuk terima pembayaran langsung dari wallet
- **Copy to Clipboard**: Copy alamat wallet dengan satu klik
- **Topup Demo**: Topup saldo untuk testing (demo mode)
- **Real-time Balance**: Saldo update otomatis setelah transaksi
- **Riwayat transaksi lengkap**: Lihat semua transaksi masuk/keluar
- **Export transaksi**: Download history sebagai file

## 🛠️ Teknologi yang Digunakan

### Backend
- **Laravel 11** - PHP Framework
- **MySQL** - Database
- **PHP 8.2+** - Programming Language

### Frontend
- **Blade Templates** - Templating Engine
- **CSS3** - Styling
- **Vanilla JavaScript** - Interactivity

### Blockchain & Cryptography
- **Polygon (MATIC)** - Layer 2 Blockchain
- **zk-SNARKs** - Zero-Knowledge Proof Protocol
- **MetaMask** - Web3 Wallet Integration
- **Smart Contracts** - On-chain Logic

### Libraries
- **SimpleSoftwareIO/simple-qrcode** - QR Code Generation
- **Web3.js / Ethers.js** - Blockchain Interaction
- **snarkjs** (Optional) - zk-SNARK Implementation

## 📋 Persyaratan Sistem

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Node.js >= 18.x
- NPM / Yarn
- MetaMask Extension (untuk koneksi blockchain)

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd zk_digitalpayment
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Konfigurasi Environment

```bash
# Copy file environment
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Konfigurasi Database

Edit file `.env` dan sesuaikan kredensial database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zk_payment
DB_USERNAME=root
DB_PASSWORD=your_password
```

Buat database:

```bash
mysql -u root -p
CREATE DATABASE zk_payment;
exit;
```

### 5. Jalankan Migration

```bash
php artisan migrate
```

### 6. Build Assets

```bash
npm run build
# atau untuk development
npm run dev
```

### 7. Jalankan Server

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

## ⚙️ Konfigurasi Polygon

### Testnet (Amoy)

Untuk testing, gunakan Polygon Amoy Testnet (Mumbai sudah deprecated):

1. Install MetaMask di browser Anda
2. Tambahkan network Amoy:
   - Network Name: `Polygon Amoy Testnet`
   - RPC URL: `https://rpc-amoy.polygon.technology/`
   - Chain ID: `80002`
   - Currency Symbol: `MATIC`
   - Block Explorer: `https://amoy.polygonscan.com/`

3. Dapatkan test MATIC dari faucet:
   - https://faucet.polygon.technology/

### Mainnet

Untuk production, gunakan Polygon Mainnet:

```env
POLYGON_NETWORK=mainnet
POLYGON_RPC_URL=https://polygon-rpc.com/
POLYGON_CHAIN_ID=137
```

## 🔐 Konfigurasi zk-SNARKs

### Setup (Simulasi)

Aplikasi ini menggunakan simulasi zk-SNARKs untuk demo. Untuk implementasi production yang sebenarnya:

1. Install circom:
```bash
npm install -g circom
```

2. Install snarkjs:
```bash
npm install -g snarkjs
```

3. Compile circuits:
```bash
circom circuits/balance_verification.circom --r1cs --wasm --sym
circom circuits/private_transaction.circom --r1cs --wasm --sym
```

4. Generate trusted setup:
```bash
snarkjs groth16 setup balance_verification.r1cs pot12_final.ptau balance_verification_0000.zkey
snarkjs zkey export verificationkey balance_verification_0000.zkey verification_key.json
```

5. Update path di `.env`:
```env
ZK_ENABLED=true
ZK_CIRCUIT_PATH=storage/circuits/
ZK_PROVING_KEY_PATH=storage/keys/proving_key.json
ZK_VERIFICATION_KEY_PATH=storage/keys/verification_key.json
```

## 📱 Cara Menggunakan

### 1. Register Akun

1. Buka aplikasi di browser
2. Klik "Daftar di sini"
3. Isi form registrasi
4. Wallet akan dibuat otomatis

### 2. Login

1. Masukkan email dan password
2. (Opsional) Centang "Gunakan ZK-SNARK Login" untuk login privat
3. Klik "Login"

### 3. Akses Wallet

1. Klik menu "**Wallet**" di navbar
2. Lihat informasi wallet:
   - Balance (saldo tersedia)
   - Wallet address
   - Public key
   - Polygon address
3. QR code wallet akan otomatis tampil
4. Copy alamat dengan klik button 📋
5. Download QR code untuk sharing

### 4. Generate QR Code untuk Terima Pembayaran

**Cara 1: QR Default (Jumlah Fleksibel)**
1. Buka halaman Wallet
2. QR code default akan tampil otomatis
3. Download atau share QR code
4. Pembayar akan input jumlah saat scan

**Cara 2: QR Custom (Jumlah Tetap)**
1. Buka halaman Wallet
2. Scroll ke "Generate QR dengan Jumlah"
3. Input jumlah dan deskripsi (opsional)
4. Klik "Generate QR Custom"
5. QR code baru dengan payment request akan tampil
6. Download atau share QR code

### 5. Kirim Pembayaran

**Via Scan QR Code:**
1. Klik "Scan QR" atau menu "Pembayaran" → "Scan QR"
2. Izinkan akses kamera (atau gunakan "Input Manual")
3. Scan QR Code dari penerima
4. Verifikasi detail pembayaran
5. (Opsional) Centang "Gunakan ZK-SNARK untuk privasi"
6. Klik "Konfirmasi & Bayar"

**Via Transfer Manual:**
1. Klik menu "Pembayaran"
2. Pilih tab "Transfer Manual"
3. Masukkan alamat wallet penerima
4. Masukkan jumlah
5. Tambahkan catatan (opsional)
6. (Opsional) Centang "Transaksi Privat (ZK-SNARK)"
7. Klik "Kirim Pembayaran"

### 6. Topup Saldo (Demo)

1. Buka halaman Wallet
2. Klik button "➕ Topup" (di samping balance)
3. Masukkan jumlah (1 - 10000)
4. Klik "Topup Sekarang"
5. Saldo akan bertambah secara real-time

## 🏗️ Struktur Project

```
zk_digitalpayment/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php         # Authentication
│   │       ├── DashboardController.php    # Dashboard
│   │       ├── WalletController.php       # Wallet Management & QR
│   │       └── PaymentController.php      # Payment & Transactions
│   └── Models/
│       ├── User.php                       # User model
│       ├── Wallet.php                     # Wallet model
│       └── Transaction.php                # Transaction model
├── database/
│   └── migrations/
│       ├── create_wallets_table.php
│       ├── create_transactions_table.php
│       └── add_zk_fields_to_users_table.php
├── public/
│   ├── css/
│   │   └── style.css                      # Main stylesheet
│   └── js/
│       ├── app.js                         # Main JavaScript
│       ├── zk-snark.js                    # ZK-SNARK implementation
│       └── polygon.js                     # Polygon integration
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php              # Main layout
│       ├── auth/
│       │   ├── login.blade.php            # Login page
│       │   └── register.blade.php         # Register page
│       ├── dashboard.blade.php            # Dashboard
│       └── payment/
│           ├── form.blade.php             # Payment form
│           ├── scan.blade.php             # QR Scanner
│           └── history.blade.php          # Transaction history
└── routes/
    └── web.php                            # Web routes
```

## 🔒 Keamanan

### Best Practices yang Diterapkan

1. **Password Hashing**: Menggunakan bcrypt
2. **CSRF Protection**: Token CSRF pada semua form
3. **SQL Injection Prevention**: Eloquent ORM & prepared statements
4. **XSS Prevention**: Blade template escaping
5. **Private Key Encryption**: Private key wallet dienkripsi
6. **ZK-SNARK**: Privasi transaksi menggunakan zero-knowledge proof
7. **HTTPS**: Wajib untuk production

### Catatan Keamanan

⚠️ **PENTING untuk Production:**

1. Ubah `APP_KEY` dengan key unik
2. Set `APP_DEBUG=false`
3. Gunakan HTTPS
4. Implementasikan rate limiting
5. Gunakan zk-SNARK library production (snarkjs)
6. Secure private keys dengan HSM atau KMS
7. Audit smart contracts sebelum deploy

## 🧪 Testing

```bash
# Run semua tests
php artisan test

# Run specific test
php artisan test --filter PaymentTest
```

## 📊 Database Schema

### Users Table
- id
- name
- email
- password
- zk_login_commitment (untuk ZK login)
- zk_public_key
- zk_enabled
- timestamps

### Wallets Table
- id
- user_id
- wallet_address
- public_key
- encrypted_private_key
- balance
- polygon_address
- zk_proof_commitment
- timestamps

### Transactions Table
- id
- sender_wallet_id
- receiver_wallet_id
- amount
- transaction_hash
- polygon_tx_hash
- zk_proof (untuk transaksi privat)
- zk_public_inputs
- status
- qr_code
- notes
- timestamps

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan:

1. Fork repository ini
2. Buat branch baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📝 Lisensi

Project ini menggunakan lisensi MIT. Lihat file `LICENSE` untuk detail.

## 📧 Kontak & Support

Untuk pertanyaan, bug report, atau feature request:
- Email: support@zkpayment.com
- Issues: https://github.com/yourusername/zk_digitalpayment/issues

## 🙏 Acknowledgments

- **Laravel** - PHP Framework
- **Polygon** - Layer 2 Blockchain
- **snarkjs** - zk-SNARK JavaScript Implementation
- **circom** - Circuit Compiler
- **MetaMask** - Web3 Wallet

## 📚 Dokumentasi Lengkap

### Wallet & QR Code Documentation
- **[WALLET_GUIDE.md](WALLET_GUIDE.md)** - Panduan lengkap fitur wallet dan QR code
- **[QUICK_START_WALLET.md](QUICK_START_WALLET.md)** - Quick start guide untuk testing wallet (5 menit)
- **[WALLET_TESTING.md](WALLET_TESTING.md)** - Testing checklist lengkap (100+ test cases)
- **[WALLET_IMPLEMENTATION_SUMMARY.md](WALLET_IMPLEMENTATION_SUMMARY.md)** - Technical implementation details

### General Documentation
- **[INSTALASI.md](INSTALASI.md)** - Setup dan instalasi lengkap
- **[CARA_PENGGUNAAN.md](CARA_PENGGUNAAN.md)** - Panduan penggunaan aplikasi
- **[TESTING_CHECKLIST.md](TESTING_CHECKLIST.md)** - General testing checklist

## ⚡ Quick Start - Testing Wallet

```bash
# 1. Setup (sudah install dependencies)
php artisan migrate
php artisan serve

# 2. Register 2 users di browser
# User 1: alice@test.com / password123
# User 2: bob@test.com / password123

# 3. Test QR Payment
# - User 1: Login → Wallet → Generate QR
# - User 2: Login → Scan QR → Pay
# - Verify: Balance updated untuk kedua user
```

Lihat **[QUICK_START_WALLET.md](QUICK_START_WALLET.md)** untuk panduan testing detail.

## 🗺️ Roadmap

### Phase 1 (Current) ✅ COMPLETED
- ✅ Basic wallet functionality
- ✅ Wallet management page dengan QR code
- ✅ QR Code generation (default & custom)
- ✅ QR Code scanning (camera & manual)
- ✅ Download QR code as PNG
- ✅ Copy to clipboard functionality
- ✅ QR Code P2P payment
- ✅ Balance verification & updates
- ✅ Process logs real-time
- ✅ Topup demo feature
- ✅ Polygon integration (simulated)
- ✅ zk-SNARK simulation
- ✅ Comprehensive documentation

### Phase 2 (Next)
- [ ] Production zk-SNARK implementation dengan circom
- [ ] Smart contract deployment
- [ ] Multi-currency support
- [ ] Mobile responsive improvements

### Phase 3 (Future)
- [ ] Mobile app (React Native)
- [ ] Multi-signature wallet
- [ ] DeFi integration
- [ ] Cross-chain bridge

## ⚡ Performance Tips

1. **Database Indexing**: Index pada foreign keys dan frequently queried columns
2. **Caching**: Gunakan Redis untuk session dan cache
3. **Queue Jobs**: Gunakan queue untuk blockchain transactions
4. **CDN**: Serve static assets via CDN
5. **Database Pooling**: Connection pooling untuk better performance

## 🐛 Troubleshooting

### MetaMask tidak terdeteksi
- Pastikan extension MetaMask terinstall
- Refresh halaman setelah install MetaMask
- Clear browser cache

### Transaction gagal
- Cek saldo MATIC untuk gas fee
- Pastikan terhubung ke network yang benar
- Lihat error di console browser

### QR Scanner tidak berfungsi
- Izinkan akses kamera di browser
- Gunakan HTTPS (kamera hanya work di HTTPS)
- Fallback ke input manual jika kamera tidak tersedia

---

**Dibuat dengan ❤️ menggunakan Laravel, Polygon, dan zk-SNARKs**

© 2024 ZK Payment. All rights reserved.
