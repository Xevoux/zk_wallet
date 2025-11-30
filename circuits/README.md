# ZK-SNARK Circuits

This directory contains Circom circuits for Zero-Knowledge Proofs used in the ZK Payment system.

## Circuits Overview

### 1. balance_check.circom
**Purpose**: Prove that a user has sufficient balance for a transaction without revealing the actual balance.

**Private Inputs**:
- `balance`: User's actual balance (kept secret)
- `randomness`: Random value for commitment blinding

**Public Inputs**:
- `amount`: Minimum required amount for transaction
- `commitment`: Pedersen commitment to the balance

**Use Case**: Used during payment transactions to prove sufficient funds without exposing wallet balance.

### 2. private_transfer.circom
**Purpose**: Prove a valid transfer between parties without revealing sender, receiver, or amount.

**Private Inputs**:
- `senderBalance`: Sender's current balance
- `amount`: Transfer amount
- `senderSecret`: Sender's secret key
- `receiverAddress`: Receiver's wallet address
- `randomness`: Random value for new commitment

**Public Inputs**:
- `senderCommitment`: Commitment to sender's initial state
- `nullifier`: Prevents double-spending
- `newCommitment`: Commitment to the new state after transfer

**Use Case**: Private transactions where transaction details remain confidential.

### 3. auth_proof.circom
**Purpose**: Authenticate users by proving knowledge of password without transmitting it.

**Private Inputs**:
- `password`: User's password (never revealed)
- `randomness`: Random salt for commitment

**Public Inputs**:
- `passwordHash`: Hash of the password
- `commitment`: Commitment to the password

**Use Case**: Zero-knowledge authentication for user login.

## Setup Instructions

### Prerequisites
```bash
# Install circom compiler
npm install -g circom

# Install snarkjs
npm install -g snarkjs
```

### Compile Circuits

1. **Compile a circuit**:
```bash
circom circuits/balance_check.circom --r1cs --wasm --sym -o circuits/build/
```

2. **Download Powers of Tau** (ceremony file):
```bash
# For circuits with < 2^12 constraints
wget https://hermez.s3-eu-west-1.amazonaws.com/powersOfTau28_hez_final_12.ptau -O circuits/pot12_final.ptau
```

3. **Generate proving key**:
```bash
snarkjs groth16 setup circuits/build/balance_check.r1cs circuits/pot12_final.ptau circuits/balance_check_0000.zkey
```

4. **Contribute to Phase 2** (adds randomness):
```bash
snarkjs zkey contribute circuits/balance_check_0000.zkey circuits/balance_check_final.zkey --name="First contribution" -v
```

5. **Export verification key**:
```bash
snarkjs zkey export verificationkey circuits/balance_check_final.zkey storage/keys/balance_verification_key.json
```

6. **Export Solidity verifier**:
```bash
snarkjs zkey export solidityverifier circuits/balance_check_final.zkey contracts/BalanceCheckVerifier.sol
```

## Generate Proofs (Client-Side)

### Example: Balance Check Proof

1. **Create input JSON**:
```json
{
  "balance": "1000000000000000000",
  "randomness": "12345678901234567890",
  "amount": "500000000000000000",
  "commitment": "9876543210987654321"
}
```

2. **Generate witness**:
```bash
snarkjs wtns calculate circuits/build/balance_check_js/balance_check.wasm input.json witness.wtns
```

3. **Generate proof**:
```bash
snarkjs groth16 prove circuits/balance_check_final.zkey witness.wtns proof.json public.json
```

4. **Verify proof**:
```bash
snarkjs groth16 verify storage/keys/balance_verification_key.json public.json proof.json
```

## Verify Proofs (Server-Side)

The Laravel backend uses the `ZKSNARKService` to verify proofs:

```php
$zkService = new ZKSNARKService();
$isValid = $zkService->verifyWithSnarkJS($proofData, 'balance');
```

## Circuit Constraints

- **balance_check.circom**: ~200 constraints
- **private_transfer.circom**: ~500 constraints  
- **auth_proof.circom**: ~150 constraints

For larger circuits, use a higher Powers of Tau ceremony file (e.g., `pot14`, `pot16`).

## Security Considerations

1. **Trusted Setup**: Use production-grade Powers of Tau files from trusted ceremonies
2. **Randomness**: Always use cryptographically secure random values
3. **Nullifiers**: Store nullifiers in database to prevent double-spending
4. **Key Management**: Protect proving keys and verification keys

## Integration with Smart Contracts

The verification keys are exported to Solidity contracts in the `contracts/` directory. These verifiers are deployed on Polygon and called by the `ZKPayment` contract to verify proofs on-chain.

## Development vs Production

**Development**:
- Use smaller Powers of Tau files (pot12)
- Simulate proofs when needed
- Single contribution to Phase 2

**Production**:
- Use production Powers of Tau (pot14+)
- Multiple contributions to Phase 2
- Hardware wallet for key generation
- Professional security audit

## Resources

- [Circom Documentation](https://docs.circom.io/)
- [SnarkJS Documentation](https://github.com/iden3/snarkjs)
- [Circomlib](https://github.com/iden3/circomlib)
- [ZK-SNARK Explained](https://zkp.science/)
