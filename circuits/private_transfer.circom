pragma circom 2.1.6;

include "../node_modules/circomlib/circuits/poseidon.circom";
include "../node_modules/circomlib/circuits/comparators.circom";
include "../node_modules/circomlib/circuits/bitify.circom";

/**
 * Private Transfer Circuit
 * Proves valid transfer without revealing sender balance, receiver, or exact amount
 * 
 * This circuit implements a privacy-preserving transfer mechanism:
 * 1. Proves sender has sufficient balance
 * 2. Proves knowledge of sender's secret (ownership)
 * 3. Generates a nullifier to prevent double-spending
 * 4. Creates new commitment for updated state
 * 
 * Private inputs:
 *   - senderBalance: sender's current balance
 *   - amount: transfer amount
 *   - senderSecret: sender's secret key (proves ownership)
 *   - newSalt: random salt for new commitment
 * 
 * Public inputs:
 *   - senderCommitment: commitment to sender's current state
 *   - nullifier: prevents double-spending (derived from secret + commitment)
 *   - newBalanceCommitment: commitment to sender's new balance
 *   - recipientCommitment: commitment for recipient
 */
template PrivateTransfer(n) {
    // Private inputs (witness)
    signal input senderBalance;
    signal input amount;
    signal input senderSecret;
    signal input senderSalt;
    signal input newSalt;
    signal input recipientAddress;
    
    // Public inputs
    signal input senderCommitment;
    signal input nullifier;
    signal input newBalanceCommitment;
    signal input recipientCommitment;
    
    // ========================================
    // 1. Verify sender owns the commitment
    // ========================================
    // senderCommitment = Poseidon(senderBalance, senderSecret, senderSalt)
    component senderCommitHasher = Poseidon(3);
    senderCommitHasher.inputs[0] <== senderBalance;
    senderCommitHasher.inputs[1] <== senderSecret;
    senderCommitHasher.inputs[2] <== senderSalt;
    senderCommitHasher.out === senderCommitment;
    
    // ========================================
    // 2. Verify sender has sufficient balance
    // ========================================
    component balanceCheck = GreaterEqThan(n);
    balanceCheck.in[0] <== senderBalance;
    balanceCheck.in[1] <== amount;
    balanceCheck.out === 1;
    
    // ========================================
    // 3. Verify nullifier is correctly computed
    // ========================================
    // nullifier = Poseidon(senderSecret, senderCommitment)
    // This ensures each commitment can only be spent once
    component nullifierHasher = Poseidon(2);
    nullifierHasher.inputs[0] <== senderSecret;
    nullifierHasher.inputs[1] <== senderCommitment;
    nullifierHasher.out === nullifier;
    
    // ========================================
    // 4. Compute new balance
    // ========================================
    signal newBalance;
    newBalance <== senderBalance - amount;
    
    // Ensure new balance is non-negative (implicitly checked by field arithmetic,
    // but we add explicit check for safety)
    component newBalancePositive = GreaterEqThan(n);
    newBalancePositive.in[0] <== newBalance;
    newBalancePositive.in[1] <== 0;
    newBalancePositive.out === 1;
    
    // ========================================
    // 5. Verify new balance commitment
    // ========================================
    // newBalanceCommitment = Poseidon(newBalance, senderSecret, newSalt)
    component newCommitHasher = Poseidon(3);
    newCommitHasher.inputs[0] <== newBalance;
    newCommitHasher.inputs[1] <== senderSecret;
    newCommitHasher.inputs[2] <== newSalt;
    newCommitHasher.out === newBalanceCommitment;
    
    // ========================================
    // 6. Verify recipient commitment
    // ========================================
    // recipientCommitment = Poseidon(amount, recipientAddress)
    component recipientHasher = Poseidon(2);
    recipientHasher.inputs[0] <== amount;
    recipientHasher.inputs[1] <== recipientAddress;
    recipientHasher.out === recipientCommitment;
}

// Using 64-bit comparison for balance values
component main {public [senderCommitment, nullifier, newBalanceCommitment, recipientCommitment]} = PrivateTransfer(64);
