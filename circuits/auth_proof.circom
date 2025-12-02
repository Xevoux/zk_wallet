pragma circom 2.1.6;

include "../node_modules/circomlib/circuits/poseidon.circom";
include "../node_modules/circomlib/circuits/comparators.circom";

/**
 * Authentication Proof Circuit
 * Proves knowledge of password without revealing it
 * 
 * This circuit allows a user to prove they know a password
 * that hashes to a specific commitment, without revealing the password.
 * 
 * Private inputs:
 *   - password: user's password as a field element (kept private)
 *   - salt: random salt for commitment
 * 
 * Public inputs:
 *   - commitment: Poseidon hash of (password, salt)
 */
template AuthProof() {
    // Private inputs (witness)
    signal input password;
    signal input salt;
    
    // Public inputs
    signal input commitment;
    
    // Compute Poseidon hash of password and salt
    component hasher = Poseidon(2);
    hasher.inputs[0] <== password;
    hasher.inputs[1] <== salt;
    
    // Constraint: computed hash must equal the public commitment
    hasher.out === commitment;
}

component main {public [commitment]} = AuthProof();
