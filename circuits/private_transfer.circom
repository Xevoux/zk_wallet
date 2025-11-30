pragma circom 2.0.0;

include "../node_modules/circomlib/circuits/pedersen.circom";
include "../node_modules/circomlib/circuits/poseidon.circom";
include "../node_modules/circomlib/circuits/comparators.circom";

/**
 * Private Transfer Circuit
 * Proves valid transfer without revealing sender, receiver, or amount
 * 
 * Private inputs:
 *   - senderBalance: sender's balance
 *   - amount: transfer amount
 *   - senderSecret: sender's secret key
 *   - receiverAddress: receiver's address
 *   - randomness: random value for commitment
 * 
 * Public inputs:
 *   - senderCommitment: commitment to sender's balance
 *   - nullifier: prevents double-spending
 *   - newCommitment: commitment to new state
 */
template PrivateTransfer() {
    // Private inputs
    signal input senderBalance;
    signal input amount;
    signal input senderSecret;
    signal input receiverAddress;
    signal input randomness;
    
    // Public inputs
    signal input senderCommitment;
    signal input nullifier;
    signal input newCommitment;
    
    // Output
    signal output out;
    
    // 1. Verify sender has sufficient balance
    component balanceCheck = GreaterEqThan(64);
    balanceCheck.in[0] <== senderBalance;
    balanceCheck.in[1] <== amount;
    balanceCheck.out === 1;
    
    // 2. Verify sender commitment
    component senderCommit = Pedersen(2);
    senderCommit.in[0] <== senderBalance;
    senderCommit.in[1] <== senderSecret;
    senderCommit.out === senderCommitment;
    
    // 3. Generate nullifier to prevent double-spending
    component nullifierGen = Poseidon(2);
    nullifierGen.inputs[0] <== senderSecret;
    nullifierGen.inputs[1] <== senderBalance;
    nullifierGen.out === nullifier;
    
    // 4. Calculate new balance after transfer
    signal newBalance;
    newBalance <== senderBalance - amount;
    
    // 5. Create new commitment
    component newCommit = Pedersen(3);
    newCommit.in[0] <== newBalance;
    newCommit.in[1] <== receiverAddress;
    newCommit.in[2] <== randomness;
    newCommit.out === newCommitment;
    
    // Output 1 if all checks pass
    out <== 1;
}

component main {public [senderCommitment, nullifier, newCommitment]} = PrivateTransfer();
