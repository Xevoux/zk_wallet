# ZK-SNARK Circuits for ZK Payment

This directory contains the circom circuits for zero-knowledge proofs used in the ZK Payment system.

## 📋 Prerequisites

### 1. Install Circom

```bash
# Install Rust (if not installed)
curl --proto '=https' --tlsv1.2 https://sh.rustup.rs -sSf | sh

# Clone and build circom
git clone https://github.com/iden3/circom.git
cd circom
cargo build --release
cargo install --path circom

# Verify installation
circom --version
```

**Windows Users:**
- Install Rust from https://rustup.rs/
- Build circom using the same commands in PowerShell

### 2. Install Node.js Dependencies

```bash
cd circuits
npm install
```

## 🏗️ Build Process

### Quick Build (All Steps)

```bash
npm run build
```

This will:
1. Compile all circuits
2. Run trusted setup
3. Export verification keys
4. Generate Solidity verifiers

### Manual Step-by-Step Build

#### Step 1: Download Powers of Tau

```bash
# Create directory
mkdir -p ptau

# Download (14 is sufficient for our circuits)
npm run download:ptau
# or manually:
curl -L https://hermez.s3-eu-west-1.amazonaws.com/powersOfTau28_hez_final_14.ptau -o ptau/pot14_final.ptau
```

**Note:** The Powers of Tau ceremony provides the common reference string (CRS) for Groth16. The file `pot14_final.ptau` supports circuits up to 2^14 constraints.

#### Step 2: Compile Circuits

```bash
# Compile individual circuits
npm run compile:auth      # Auth proof circuit
npm run compile:balance   # Balance check circuit  
npm run compile:transfer  # Private transfer circuit

# Or compile all
npm run compile:all
```

Output files (in `build/<circuit_name>/`):
- `<circuit>.r1cs` - Rank-1 Constraint System
- `<circuit>.sym` - Debug symbols
- `<circuit>_js/<circuit>.wasm` - WebAssembly for proof generation

#### Step 3: Trusted Setup (Groth16)

```bash
# Run setup for each circuit
npm run setup:auth
npm run setup:balance
npm run setup:transfer

# Or all at once
npm run setup:all
```

This performs:
1. Phase 1: Initialize zkey with circuit-specific setup
2. Phase 2: Contribute randomness (multiple contributions recommended for production)
3. Export verification key

Output files (in `keys/`):
- `<circuit>_final.zkey` - Proving key
- `<circuit>_verification_key.json` - Verification key

#### Step 4: Export to Application

```bash
# Export verification keys to Laravel storage
npm run export:keys

# Generate Solidity verifier contracts
npm run export:verifiers
```

Files are copied to:
- `../storage/app/zk-keys/` - For server-side verification
- `../public/zk/` - For client-side proof generation
- `../contracts/contracts/verifiers/` - Solidity contracts

## 📁 Directory Structure

```
circuits/
├── auth_proof.circom         # Authentication circuit
├── balance_check.circom      # Balance verification circuit
├── private_transfer.circom   # Private transfer circuit
├── package.json              # Build scripts
├── README.md                 # This file
├── ptau/                     # Powers of Tau files
│   └── pot14_final.ptau
├── build/                    # Compiled circuits
│   ├── auth_proof/
│   │   ├── auth_proof.r1cs
│   │   ├── auth_proof.sym
│   │   └── auth_proof_js/
│   │       └── auth_proof.wasm
│   ├── balance_check/
│   └── private_transfer/
├── keys/                     # Generated keys
│   ├── auth_proof_final.zkey
│   ├── auth_proof_verification_key.json
│   ├── balance_check_final.zkey
│   ├── balance_check_verification_key.json
│   ├── private_transfer_final.zkey
│   └── private_transfer_verification_key.json
└── scripts/                  # Build scripts
    ├── setup.js
    ├── export-keys.js
    ├── export-verifiers.js
    └── test-proofs.js
```

## 🔐 Circuits Overview

### auth_proof.circom

**Purpose:** Prove knowledge of password without revealing it

**Private Inputs:**
- `password` - User's password (as field element)
- `salt` - Random salt for commitment

**Public Inputs:**
- `commitment` - Poseidon(password, salt)

**Constraints:** ~500

### balance_check.circom

**Purpose:** Prove balance ≥ amount without revealing actual balance

**Private Inputs:**
- `balance` - Actual balance
- `salt` - Commitment randomness

**Public Inputs:**
- `minAmount` - Required minimum amount
- `balanceCommitment` - Poseidon(balance, salt)

**Constraints:** ~800

### private_transfer.circom

**Purpose:** Execute private transfer with balance and ownership verification

**Private Inputs:**
- `senderBalance` - Sender's current balance
- `amount` - Transfer amount
- `senderSecret` - Sender's secret key
- `senderSalt` - Salt for sender commitment
- `newSalt` - Salt for new commitment
- `recipientAddress` - Recipient's address

**Public Inputs:**
- `senderCommitment` - Commitment to sender's state
- `nullifier` - Prevents double-spending
- `newBalanceCommitment` - Commitment to new balance
- `recipientCommitment` - Commitment for recipient

**Constraints:** ~2000

## 🧪 Testing

```bash
# Run proof generation and verification tests
npm run test
```

This generates test proofs and verifies them using the generated keys.

## 🔒 Production Security Considerations

### Trusted Setup Ceremony

For production, the trusted setup should involve multiple independent parties:

```bash
# Initial setup
snarkjs zkey new circuit.r1cs pot14_final.ptau circuit_0000.zkey

# Contribution 1 (Party A)
snarkjs zkey contribute circuit_0000.zkey circuit_0001.zkey --name="Party A" -v

# Contribution 2 (Party B)
snarkjs zkey contribute circuit_0001.zkey circuit_0002.zkey --name="Party B" -v

# ... more contributions

# Final beacon (public randomness)
snarkjs zkey beacon circuit_000N.zkey circuit_final.zkey 0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f 10 -n="Final Beacon"

# Verify final zkey
snarkjs zkey verify circuit.r1cs pot14_final.ptau circuit_final.zkey
```

### Audit Checklist

- [ ] Multiple trusted setup contributors
- [ ] Circuit code audited
- [ ] Verification key matches deployed contract
- [ ] Nullifier storage properly implemented
- [ ] No secret leakage in public signals

## 🛠️ Troubleshooting

### "Cannot find module 'circomlib'"

```bash
npm install circomlib
```

### "Powers of Tau file not found"

```bash
npm run download:ptau
```

### "Constraint too large"

Use a larger Powers of Tau file:
```bash
curl -L https://hermez.s3-eu-west-1.amazonaws.com/powersOfTau28_hez_final_16.ptau -o ptau/pot16_final.ptau
```

### "WebAssembly memory error"

Increase Node.js memory:
```bash
NODE_OPTIONS=--max-old-space-size=8192 npm run compile:all
```

## 📚 Resources

- [Circom Documentation](https://docs.circom.io/)
- [snarkjs GitHub](https://github.com/iden3/snarkjs)
- [circomlib (circuit library)](https://github.com/iden3/circomlib)
- [ZK-SNARK Tutorial](https://blog.iden3.io/first-zk-proof.html)
- [Groth16 Paper](https://eprint.iacr.org/2016/260.pdf)

## 📝 License

MIT License - See main project LICENSE file
