pragma circom 2.0.0;

include "../node_modules/circomlib/circuits/poseidon.circom";
include "../node_modules/circomlib/circuits/pedersen.circom";

/**
 * Authentication Proof Circuit
 * Proves knowledge of password without revealing it
 * 
 * Private inputs:
 *   - password: user's password (kept private)
 *   - randomness: random salt
 * 
 * Public inputs:
 *   - passwordHash: hash of password
 *   - commitment: commitment to password
 */
template AuthProof() {
    // Private inputs
    signal input password;
    signal input randomness;
    
    // Public inputs
    signal input passwordHash;
    signal input commitment;
    
    // Output
    signal output out;
    
    // 1. Hash password with Poseidon
    component hasher = Poseidon(1);
    hasher.inputs[0] <== password;
    hasher.out === passwordHash;
    
    // 2. Create Pedersen commitment
    component commit = Pedersen(2);
    commit.in[0] <== password;
    commit.in[1] <== randomness;
    commit.out === commitment;
    
    // Output 1 if proof is valid
    out <== 1;
}

component main {public [passwordHash, commitment]} = AuthProof();
