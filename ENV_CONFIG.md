# Environment Configuration Guide

This document provides a comprehensive guide for configuring your ZK Payment application environment variables.

## Table of Contents
1. [Laravel Configuration](#laravel-configuration)
2. [Database Configuration](#database-configuration)
3. [Blockchain Configuration](#blockchain-configuration)
4. [ZK-SNARK Configuration](#zk-snark-configuration)
5. [Service Configuration](#service-configuration)
6. [Development vs Production](#development-vs-production)
7. [Security Best Practices](#security-best-practices)

---

## Laravel Configuration

### Basic Application Settings

```env
# Application
APP_NAME="ZK Payment"
APP_ENV=local                          # local, production, staging
APP_KEY=                               # Generate: php artisan key:generate
APP_DEBUG=true                         # false in production
APP_URL=http://localhost:8000

# Locale
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Timezone
APP_TIMEZONE=UTC

# PHP CLI Server Workers
PHP_CLI_SERVER_WORKERS=4

# Bcrypt Rounds (higher = more secure but slower)
BCRYPT_ROUNDS=12                       # 10-12 recommended
```

---

## Database Configuration

### MySQL Configuration (Recommended for Production)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zk_payment
DB_USERNAME=root
DB_PASSWORD=your_secure_password

# Connection Options
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=
```

### PostgreSQL Configuration (Alternative)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zk_payment
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password
DB_SCHEMA=public
```

### SQLite Configuration (Development Only)

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
# Or leave empty for default: database/database.sqlite
```

---

## Blockchain Configuration

### Polygon Amoy Testnet (Development)

```env
# Polygon Amoy Testnet (Mumbai deprecated)
POLYGON_NETWORK=amoy
POLYGON_CHAIN_ID=80002
POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/
POLYGON_RPC_URL_BACKUP=https://polygon-amoy.g.alchemy.com/v2/YOUR_ALCHEMY_KEY

# Alternative RPC Providers for Amoy
# POLYGON_RPC_URL=https://rpc-amoy.polygon.technology/
# POLYGON_RPC_URL=https://polygon-amoy-bor-rpc.publicnode.com

# Block Explorer
POLYGON_SCAN_URL=https://mumbai.polygonscan.com
POLYGONSCAN_API_KEY=your_polygonscan_api_key

# Gas Settings (Mumbai)
POLYGON_GAS_PRICE=20000000000          # 20 Gwei
POLYGON_GAS_LIMIT=500000
```

### Polygon Mainnet (Production)

```env
# Polygon Mainnet
POLYGON_NETWORK=mainnet
POLYGON_CHAIN_ID=137
POLYGON_RPC_URL=https://polygon-rpc.com/
POLYGON_RPC_URL_BACKUP=https://polygon-mainnet.g.alchemy.com/v2/YOUR_ALCHEMY_KEY

# Alternative RPC Providers
# POLYGON_RPC_URL=https://rpc-mainnet.matic.network
# POLYGON_RPC_URL=https://rpc-mainnet.maticvigil.com
# POLYGON_RPC_URL=https://rpc.ankr.com/polygon

# Block Explorer
POLYGON_SCAN_URL=https://polygonscan.com
POLYGONSCAN_API_KEY=your_polygonscan_api_key

# Gas Settings (Mainnet)
POLYGON_GAS_PRICE=50000000000          # 50 Gwei (adjust based on network)
POLYGON_GAS_LIMIT=500000
```

### Smart Contract Addresses

```env
# Deployed Contract Addresses (update after deployment)
POLYGON_CONTRACT_ADDRESS=0x0000000000000000000000000000000000000000
POLYGON_VERIFIER_ADDRESS=0x0000000000000000000000000000000000000000

# Admin Wallet (for contract deployment and management)
POLYGON_ADMIN_ADDRESS=0xYourAdminWalletAddress
POLYGON_ADMIN_PRIVATE_KEY=your_admin_private_key_here   # NEVER commit to git!
```

### Wallet Configuration

```env
# Service Wallet (for automated transactions)
POLYGON_SERVICE_WALLET=0xYourServiceWalletAddress
POLYGON_PRIVATE_KEY=your_private_key_here               # NEVER commit to git!

# HD Wallet Configuration
WALLET_MNEMONIC="your twelve word mnemonic phrase here"  # NEVER commit to git!
WALLET_DERIVATION_PATH="m/44'/60'/0'/0/"               # Ethereum derivation path
```

---

## ZK-SNARK Configuration

### Circuit Paths

```env
# ZK Circuit Directories
ZK_CIRCUITS_PATH=circuits/
ZK_BUILD_PATH=circuits/build/
ZK_KEYS_PATH=storage/keys/

# Verification Keys
ZK_BALANCE_VKEY=storage/keys/balance_verification_key.json
ZK_TRANSFER_VKEY=storage/keys/transfer_verification_key.json
ZK_AUTH_VKEY=storage/keys/auth_verification_key.json

# Proving Keys
ZK_BALANCE_PKEY=circuits/balance_check_final.zkey
ZK_TRANSFER_PKEY=circuits/private_transfer_final.zkey
ZK_AUTH_PKEY=circuits/auth_proof_final.zkey
```

### Node.js Configuration for SnarkJS

```env
# Node.js Path (for executing snarkjs verification)
NODE_PATH=node                         # or /usr/bin/node
NODE_ENV=production

# SnarkJS Settings
SNARKJS_TIMEOUT=30000                  # Verification timeout in ms
SNARKJS_MAX_MEMORY=2048                # Max memory in MB
```

---

## Service Configuration

### Redis Configuration (Recommended for Production)

```env
REDIS_CLIENT=phpredis                  # or predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Redis Sentinel (High Availability)
# REDIS_SENTINEL=mymaster
# REDIS_SENTINELS=tcp://127.0.0.1:26379,tcp://127.0.0.1:26380

# Redis Cluster
# REDIS_CLUSTER=true
# REDIS_CLUSTER_ENDPOINTS=127.0.0.1:7000,127.0.0.1:7001
```

### Cache Configuration

```env
CACHE_DRIVER=redis                     # redis, database, file, memcached
CACHE_PREFIX=zkpay_
CACHE_TTL=3600                         # Default TTL in seconds
```

### Session Configuration

```env
SESSION_DRIVER=redis                   # redis, database, file, cookie
SESSION_LIFETIME=120                   # Minutes
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true             # true in production (requires HTTPS)
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax                  # lax, strict, none
```

### Queue Configuration

```env
QUEUE_CONNECTION=redis                 # redis, database, sync
QUEUE_PREFIX=zkpay_queue_
QUEUE_RETRY_AFTER=90
QUEUE_FAILED_DRIVER=database-uuids
```

### Logging Configuration

```env
LOG_CHANNEL=stack
LOG_STACK=single,daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug                        # debug, info, notice, warning, error

# Daily Log Rotation
LOG_DAILY_DAYS=14                      # Keep logs for 14 days
LOG_SINGLE_FILE=laravel.log
```

### Mail Configuration

```env
MAIL_MAILER=smtp                       # smtp, ses, mailgun, postmark
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls                    # tls, ssl
MAIL_FROM_ADDRESS=noreply@zkpayment.app
MAIL_FROM_NAME="${APP_NAME}"
```

---

## API Keys and External Services

### Alchemy (Recommended Blockchain Provider)

```env
ALCHEMY_API_KEY=your_alchemy_api_key
ALCHEMY_WEBHOOK_SECRET=your_webhook_secret
```

### Infura (Alternative Blockchain Provider)

```env
INFURA_PROJECT_ID=your_infura_project_id
INFURA_PROJECT_SECRET=your_infura_secret
```

### The Graph (For Blockchain Indexing)

```env
THE_GRAPH_API_KEY=your_graph_api_key
THE_GRAPH_SUBGRAPH_URL=https://api.thegraph.com/subgraphs/name/your-subgraph
```

### IPFS (For Decentralized Storage)

```env
IPFS_HOST=ipfs.infura.io
IPFS_PORT=5001
IPFS_PROTOCOL=https
IPFS_PROJECT_ID=your_ipfs_project_id
IPFS_PROJECT_SECRET=your_ipfs_secret
```

---

## Development vs Production

### Development Environment (.env.local)

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Use Amoy Testnet (Mumbai deprecated)
POLYGON_NETWORK=amoy
POLYGON_CHAIN_ID=80002

# Lower security for faster development
BCRYPT_ROUNDS=10
SESSION_ENCRYPT=false

# Log everything
LOG_LEVEL=debug

# Use local services
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### Staging Environment (.env.staging)

```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.zkpayment.app

# Use Amoy Testnet (Mumbai deprecated)
POLYGON_NETWORK=amoy
POLYGON_CHAIN_ID=80002

# Production-like settings
BCRYPT_ROUNDS=12
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Use Redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

LOG_LEVEL=info
```

### Production Environment (.env.production)

```env
APP_ENV=production
APP_DEBUG=false                        # MUST be false
APP_URL=https://zkpayment.app

# Use Polygon Mainnet
POLYGON_NETWORK=mainnet
POLYGON_CHAIN_ID=137

# Maximum security
BCRYPT_ROUNDS=12
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# Production services
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Minimal logging
LOG_LEVEL=warning

# Enable monitoring
SENTRY_DSN=your_sentry_dsn
```

---

## Security Best Practices

### 1. Environment File Security

```bash
# NEVER commit .env to version control
echo ".env" >> .gitignore
echo ".env.*" >> .gitignore

# Set proper permissions
chmod 600 .env

# Use environment-specific files
.env.local       # Local development
.env.testing     # Testing
.env.staging     # Staging server
.env.production  # Production server
```

### 2. Private Key Management

**NEVER store private keys in .env files in production!**

Instead, use:

```env
# AWS Secrets Manager
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_SECRETS_MANAGER_KEY=prod/zkpayment/private-key

# HashiCorp Vault
VAULT_ADDR=https://vault.example.com
VAULT_TOKEN=your_vault_token
VAULT_SECRET_PATH=secret/zkpayment/keys

# Azure Key Vault
AZURE_KEY_VAULT_URI=https://yourvault.vault.azure.net/
AZURE_CLIENT_ID=your_client_id
AZURE_CLIENT_SECRET=your_client_secret
```

### 3. Database Security

```env
# Use strong passwords
DB_PASSWORD=RandomlyGenerated32CharPassword

# Use SSL connections in production
DB_SSLMODE=require
DB_SSLCERT=/path/to/client-cert.pem
DB_SSLKEY=/path/to/client-key.pem
DB_SSLROOTCERT=/path/to/ca-cert.pem
```

### 4. HTTPS/SSL Configuration

```env
# Force HTTPS in production
APP_FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true

# SSL Certificate Paths (if self-hosting)
SSL_CERT_PATH=/etc/ssl/certs/your-cert.pem
SSL_KEY_PATH=/etc/ssl/private/your-key.pem
```

---

## Environment Variables Checklist

### Required for Basic Operation
- [ ] `APP_KEY` - Generated with `php artisan key:generate`
- [ ] `APP_URL` - Your application URL
- [ ] `DB_*` - Database credentials
- [ ] `POLYGON_RPC_URL` - Blockchain RPC endpoint

### Required for Blockchain Integration
- [ ] `POLYGON_CHAIN_ID` - Network chain ID
- [ ] `POLYGON_CONTRACT_ADDRESS` - Deployed contract address
- [ ] `POLYGON_VERIFIER_ADDRESS` - Verifier contract address

### Required for ZK Proofs
- [ ] `ZK_*_VKEY` - Verification key paths
- [ ] `NODE_PATH` - Node.js executable path

### Recommended for Production
- [ ] `REDIS_*` - Redis configuration
- [ ] `MAIL_*` - Email configuration
- [ ] `ALCHEMY_API_KEY` or `INFURA_PROJECT_ID`
- [ ] `SENTRY_DSN` - Error monitoring
- [ ] SSL certificates and secure cookie settings

---

## Testing Configuration

Create a `.env.testing` file for automated tests:

```env
APP_ENV=testing
APP_DEBUG=true

# Use in-memory SQLite for fast tests
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Disable external services in tests
POLYGON_RPC_URL=http://localhost:8545
QUEUE_CONNECTION=sync
MAIL_MAILER=array

# Mock ZK verification
ZK_MOCK_VERIFICATION=true
```

---

## Troubleshooting

### Cannot connect to Polygon Network
- Check RPC URL is accessible
- Verify API keys for Alchemy/Infura
- Try alternative RPC endpoints

### ZK Proof Verification Fails
- Ensure Node.js is installed and in PATH
- Check verification key files exist
- Verify snarkjs is installed: `npm list -g snarkjs`

### Database Connection Issues
- Verify database credentials
- Check database server is running
- Ensure database exists: `php artisan db:create`
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

### Redis Connection Issues
- Check Redis server is running: `redis-cli ping`
- Verify Redis host and port
- Check Redis password if set

---

## Additional Resources

- [Laravel Configuration Docs](https://laravel.com/docs/configuration)
- [Polygon Network Docs](https://docs.polygon.technology/)
- [SnarkJS Documentation](https://github.com/iden3/snarkjs)
- [Web3.php Documentation](https://github.com/web3p/web3.php)

---

## Support

For configuration issues, please check:
1. `storage/logs/laravel.log` for application errors
2. `.env` file for missing or incorrect values
3. Run `php artisan config:cache` after changing .env
4. Run `php artisan config:clear` to clear cached config

## License

This configuration guide is part of the ZK Payment system.
