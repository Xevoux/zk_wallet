pragma circom 2.0.0;

include "../node_modules/circomlib/circuits/comparators.circom";
include "../node_modules/circomlib/circuits/pedersen.circom";

/**
 * Balance Check Circuit
 * Proves that balance >= amount without revealing actual balance
 * 
 * Private inputs:
 *   - balance: actual balance (kept private)
 *   - randomness: random value for commitment
 * 
 * Public inputs:
 *   - amount: minimum required amount
 *   - commitment: Pedersen commitment of balance
 */
template BalanceCheck() {
    // Private inputs
    signal input balance;
    signal input randomness;
    
    // Public inputs
    signal input amount;
    signal input commitment;
    
    // Output signal
    signal output out;
    
    // Check that balance >= amount
    component greaterOrEqual = GreaterEqThan(64);
    greaterOrEqual.in[0] <== balance;
    greaterOrEqual.in[1] <== amount;
    greaterOrEqual.out === 1;
    
    // Verify Pedersen commitment
    // commitment = Pedersen(balance, randomness)
    component commitmentChecker = Pedersen(2);
    commitmentChecker.in[0] <== balance;
    commitmentChecker.in[1] <== randomness;
    commitmentChecker.out === commitment;
    
    // Output 1 if proof is valid
    out <== 1;
}

component main {public [amount, commitment]} = BalanceCheck();
