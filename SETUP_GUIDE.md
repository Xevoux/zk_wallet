# ZK Payment - Setup Guide

Complete step-by-step guide to set up the ZK Payment system with ZKP, Smart Contracts, and Polygon blockchain integration.

## 📋 Prerequisites

### Required Software:
- **PHP**: 8.2 or higher
- **Composer**: 2.x
- **Node.js**: 18.x or higher
- **NPM**: 9.x or higher
- **MySQL**: 8.0+ (or PostgreSQL 13+)
- **Redis**: 6.x+ (recommended for production)
- **Git**: For version control

### Optional but Recommended:
- **Docker**: For containerized development
- **Circom**: For compiling ZK circuits
- **Hardhat**: For smart contract development

## 🚀 Quick Start (5 Minutes)

### 1. Clone and Install

```bash
# Clone repository
git clone <your-repo-url> zk_payment
cd zk_payment

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Configure Environment

Edit `.env` file with basic configuration:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
POLYGON_NETWORK=amoy
POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/
POLYGON_CHAIN_ID=80002
```

See `ENV_CONFIG.md` for detailed configuration options.

### 3. Set Up Database

```bash
# Create SQLite database (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# (Optional) Seed with test data
php artisan db:seed
```

### 4. Start Development Server

```bash
# Option 1: Single command (includes queue, logs, and vite)
composer dev

# Option 2: Separate terminals
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Vite
npm run dev

# Terminal 3: Queue worker (optional)
php artisan queue:work
```

Visit: http://localhost:8000

## 🔧 Complete Setup (Production Ready)

### Step 1: System Dependencies

#### Ubuntu/Debian:
```bash
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-mbstring php8.2-xml \
  php8.2-mysql php8.2-zip php8.2-curl php8.2-gd php8.2-redis \
  mysql-server redis-server nodejs npm
```

#### macOS:
```bash
brew install php@8.2 mysql redis node
```

#### Windows:
- Install PHP from: https://windows.php.net/
- Install MySQL from: https://dev.mysql.com/downloads/installer/
- Install Redis from: https://github.com/microsoftarchive/redis/releases
- Install Node.js from: https://nodejs.org/

### Step 2: Install Global Tools

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Circom (for ZK circuit compilation)
curl --proto '=https' --tlsv1.2 https://sh.rustup.rs -sSf | sh
git clone https://github.com/iden3/circom.git
cd circom
cargo build --release
cargo install --path circom

# Install SnarkJS
npm install -g snarkjs

# Install Hardhat (for smart contracts)
npm install -g hardhat
```

### Step 3: Database Setup

#### MySQL:
```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE zk_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'zkpayment'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON zk_payment.* TO 'zkpayment'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zk_payment
DB_USERNAME=zkpayment
DB_PASSWORD=your_secure_password
```

### Step 4: Redis Configuration

```bash
# Start Redis
sudo systemctl start redis

# Enable on boot
sudo systemctl enable redis

# Test connection
redis-cli ping
# Should return: PONG
```

Update `.env`:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Step 5: Blockchain Configuration

#### Get Free RPC Endpoints:

1. **Alchemy** (Recommended):
   - Sign up: https://www.alchemy.com/
   - Create app for Polygon Amoy
   - Copy API key

2. **Infura** (Alternative):
   - Sign up: https://infura.io/
   - Create project
   - Copy Project ID

Update `.env`:
```env
POLYGON_RPC_URL=https://polygon-amoy.g.alchemy.com/v2/YOUR_API_KEY
ALCHEMY_API_KEY=YOUR_API_KEY
```

#### Get Test MATIC:

For Amoy testnet:
1. Visit: https://faucet.polygon.technology/
2. Select Polygon Amoy network
3. Enter your wallet address
4. Receive test MATIC

### Step 6: Compile ZK Circuits

```bash
cd circuits

# Download Powers of Tau (required for setup)
curl https://hermez.s3-eu-west-1.amazonaws.com/powersOfTau28_hez_final_12.ptau \
  -o pot12_final.ptau

# Compile balance check circuit
circom balance_check.circom --r1cs --wasm --sym -o build/

# Setup trusted setup
snarkjs groth16 setup build/balance_check.r1cs pot12_final.ptau balance_check_0000.zkey

# Contribute to ceremony
snarkjs zkey contribute balance_check_0000.zkey balance_check_final.zkey \
  --name="First contribution" -v

# Export verification key
snarkjs zkey export verificationkey balance_check_final.zkey \
  ../storage/keys/balance_verification_key.json

# Export Solidity verifier
snarkjs zkey export solidityverifier balance_check_final.zkey \
  ../contracts/BalanceCheckVerifier.sol

cd ..
```

### Step 7: Deploy Smart Contracts

```bash
cd contracts

# Install contract dependencies
npm install

# Compile contracts
npx hardhat compile

# Deploy to Amoy testnet
npx hardhat run scripts/deploy.js --network amoy

# Save the deployed addresses and update .env:
# POLYGON_CONTRACT_ADDRESS=0x...
# POLYGON_VERIFIER_ADDRESS=0x...

# Verify on Polygonscan
npx hardhat verify --network amoy DEPLOYED_ADDRESS

cd ..
```

### Step 8: Final Configuration

Update `.env` with deployed contract addresses:
```env
POLYGON_CONTRACT_ADDRESS=0xYourDeployedContractAddress
POLYGON_VERIFIER_ADDRESS=0xYourDeployedVerifierAddress
```

### Step 9: Build Assets

```bash
# Build frontend assets
npm run build

# Or for development with hot reload
npm run dev
```

### Step 10: Run Application

```bash
# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start application (production)
php artisan serve --host=0.0.0.0 --port=8000

# Or use full dev environment
composer dev
```

## 🧪 Testing the Integration

### 1. Test Database Connection

```bash
php artisan tinker
>>> DB::connection()->getPdo();
# Should return PDO object without errors
```

### 2. Test Blockchain Connection

```bash
php artisan tinker
>>> $polygon = app(\App\Services\PolygonService::class);
>>> $polygon->getGasPrice();
# Should return current gas price
```

### 3. Test QR Code Generation

```bash
php artisan tinker
>>> $qr = app(\App\Services\QRCodeService::class);
>>> $result = $qr->generatePaymentQR('0x1234...', 0.1);
>>> $result['success'];
# Should return true
```

### 4. Test ZK Proof (Simulation)

```bash
php artisan tinker
>>> $zk = app(\App\Services\ZKSNARKService::class);
>>> $proof = base64_encode(json_encode(['proof' => ['pi_a' => '0x...'], 'publicInputs' => ['commitment' => '0x...']]));
>>> $zk->verifyBalanceProof($proof, 100);
# Should return true
```

### 5. Access Application

Open browser to: http://localhost:8000

Test features:
- ✅ User registration
- ✅ Wallet creation
- ✅ Balance checking
- ✅ QR code generation
- ✅ Payment transactions

## 📊 Project Structure

```
zk_payment/
├── app/
│   ├── Http/Controllers/      # API and web controllers
│   ├── Models/                # Database models
│   └── Services/              # Business logic (ZK, Blockchain, Wallet, QR)
├── circuits/                  # Circom ZK circuits
│   ├── balance_check.circom
│   ├── private_transfer.circom
│   └── auth_proof.circom
├── contracts/                 # Solidity smart contracts
│   ├── ZKPayment.sol
│   ├── Groth16Verifier.sol
│   └── scripts/deploy.js
├── database/
│   └── migrations/            # Database schema
├── public/
│   └── js/                    # Frontend JavaScript
│       ├── polygon.js
│       └── zk-snark.js
├── resources/
│   └── views/                 # Blade templates
├── storage/
│   ├── keys/                  # ZK verification keys
│   └── logs/                  # Application logs
├── ENV_CONFIG.md              # Environment configuration guide
├── INTEGRATION_SUMMARY.md     # Integration overview
└── SETUP_GUIDE.md            # This file
```

## 🔐 Security Checklist

Before going to production:

- [ ] Set `APP_DEBUG=false`
- [ ] Use strong `APP_KEY` (never change after deployment)
- [ ] Use MySQL/PostgreSQL instead of SQLite
- [ ] Enable Redis for caching and sessions
- [ ] Use HTTPS (set `SESSION_SECURE_COOKIE=true`)
- [ ] Store private keys in secrets manager (never in .env)
- [ ] Set up firewall rules
- [ ] Enable rate limiting
- [ ] Configure CORS properly
- [ ] Set up monitoring (Sentry, New Relic)
- [ ] Regular backups
- [ ] Security audit smart contracts
- [ ] Use production Powers of Tau for circuits
- [ ] Multi-signature for admin operations

## 🐛 Troubleshooting

### Cannot connect to database
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u zkpayment -p zk_payment

# Verify .env settings
php artisan config:clear
```

### Blockchain connection fails
```bash
# Test RPC endpoint
curl -X POST -H "Content-Type: application/json" \
  --data '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}' \
  YOUR_RPC_URL

# Check API key is valid
# Try alternative RPC endpoint
```

### ZK verification fails
```bash
# Check Node.js is installed
node --version

# Check snarkjs is available
which snarkjs

# Verify keys exist
ls -la storage/keys/

# Test snarkjs manually
cd circuits
snarkjs groth16 verify verification_key.json public.json proof.json
```

### Permission errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 📚 Documentation

- **`IMPLEMENTATION_GUIDE.md`** - Detailed system architecture
- **`ENV_CONFIG.md`** - Complete environment configuration
- **`INTEGRATION_SUMMARY.md`** - Integration overview
- **`circuits/README.md`** - ZK circuit documentation
- **`contracts/README.md`** - Smart contract documentation

## 🆘 Getting Help

1. Check `storage/logs/laravel.log` for errors
2. Review documentation files
3. Test each component individually
4. Check GitHub issues
5. Contact support team

## 📝 Next Steps

After setup:

1. **Development**:
   - Create custom circuits for specific use cases
   - Implement additional smart contract features
   - Build frontend UI/UX

2. **Testing**:
   - Write comprehensive unit tests
   - Conduct integration testing
   - Security audit

3. **Deployment**:
   - Deploy to staging environment
   - Load testing
   - Deploy to production (mainnet)

## ✅ Setup Complete!

Your ZK Payment system is now ready. The integration includes:

- ✅ Zero-Knowledge Proof circuits (Circom)
- ✅ Smart contracts (Solidity)
- ✅ Polygon blockchain integration
- ✅ Laravel backend services
- ✅ QR code payment system
- ✅ Wallet management
- ✅ Transaction privacy

Start building your privacy-focused payment application! 🚀
