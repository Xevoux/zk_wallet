/**
 * Test Proofs Script
 * Tests proof generation and verification for all circuits
 */

const snarkjs = require("snarkjs");
const fs = require("fs");
const path = require("path");

async function testAuthProof() {
    console.log("\n============================================");
    console.log("Testing: auth_proof");
    console.log("============================================\n");

    const wasmPath = path.join(__dirname, "..", "build", "auth_proof", "auth_proof_js", "auth_proof.wasm");
    const zkeyPath = path.join(__dirname, "..", "keys", "auth_proof_final.zkey");
    const vkeyPath = path.join(__dirname, "..", "keys", "auth_proof_verification_key.json");

    if (!fs.existsSync(wasmPath) || !fs.existsSync(zkeyPath)) {
        console.log("✗ Files not found. Run build first.");
        return false;
    }

    // Test inputs
    const password = BigInt("123456789"); // Example password as field element
    const salt = BigInt("987654321");
    
    // Calculate expected commitment using Poseidon
    // In real implementation, this would be pre-calculated
    const { buildPoseidon } = require("circomlibjs");
    const poseidon = await buildPoseidon();
    const commitment = poseidon.F.toString(poseidon([password, salt]));

    const input = {
        password: password.toString(),
        salt: salt.toString(),
        commitment: commitment
    };

    console.log("Input:", JSON.stringify(input, null, 2));

    try {
        // Generate proof
        console.log("\nGenerating proof...");
        const startTime = Date.now();
        const { proof, publicSignals } = await snarkjs.groth16.fullProve(input, wasmPath, zkeyPath);
        const proofTime = Date.now() - startTime;
        console.log(`✓ Proof generated in ${proofTime}ms`);

        // Verify proof
        console.log("\nVerifying proof...");
        const vkey = JSON.parse(fs.readFileSync(vkeyPath, "utf8"));
        const verified = await snarkjs.groth16.verify(vkey, publicSignals, proof);
        
        if (verified) {
            console.log("✓ Proof VALID!");
        } else {
            console.log("✗ Proof INVALID!");
        }

        console.log("\nProof structure:");
        console.log(JSON.stringify(proof, null, 2));
        console.log("\nPublic signals:", publicSignals);

        return verified;
    } catch (error) {
        console.log("✗ Error:", error.message);
        return false;
    }
}

async function testBalanceCheck() {
    console.log("\n============================================");
    console.log("Testing: balance_check");
    console.log("============================================\n");

    const wasmPath = path.join(__dirname, "..", "build", "balance_check", "balance_check_js", "balance_check.wasm");
    const zkeyPath = path.join(__dirname, "..", "keys", "balance_check_final.zkey");
    const vkeyPath = path.join(__dirname, "..", "keys", "balance_check_verification_key.json");

    if (!fs.existsSync(wasmPath) || !fs.existsSync(zkeyPath)) {
        console.log("✗ Files not found. Run build first.");
        return false;
    }

    const { buildPoseidon } = require("circomlibjs");
    const poseidon = await buildPoseidon();

    // Test inputs
    const balance = BigInt("1000000"); // 1,000,000 (in smallest unit)
    const salt = BigInt("123456789");
    const minAmount = BigInt("500000"); // 500,000

    const balanceCommitment = poseidon.F.toString(poseidon([balance, salt]));

    const input = {
        balance: balance.toString(),
        salt: salt.toString(),
        minAmount: minAmount.toString(),
        balanceCommitment: balanceCommitment
    };

    console.log("Input:");
    console.log(`  - Balance (private): ${balance}`);
    console.log(`  - Min amount (public): ${minAmount}`);
    console.log(`  - Commitment (public): ${balanceCommitment.substring(0, 20)}...`);

    try {
        console.log("\nGenerating proof...");
        const startTime = Date.now();
        const { proof, publicSignals } = await snarkjs.groth16.fullProve(input, wasmPath, zkeyPath);
        const proofTime = Date.now() - startTime;
        console.log(`✓ Proof generated in ${proofTime}ms`);

        console.log("\nVerifying proof...");
        const vkey = JSON.parse(fs.readFileSync(vkeyPath, "utf8"));
        const verified = await snarkjs.groth16.verify(vkey, publicSignals, proof);
        
        if (verified) {
            console.log("✓ Proof VALID! Balance >= minAmount confirmed without revealing balance");
        } else {
            console.log("✗ Proof INVALID!");
        }

        return verified;
    } catch (error) {
        console.log("✗ Error:", error.message);
        return false;
    }
}

async function main() {
    console.log("============================================");
    console.log("ZK-SNARK Proof Tests");
    console.log("============================================");

    let allPassed = true;

    try {
        allPassed = await testAuthProof() && allPassed;
    } catch (e) {
        console.log("Auth proof test failed:", e.message);
        allPassed = false;
    }

    try {
        allPassed = await testBalanceCheck() && allPassed;
    } catch (e) {
        console.log("Balance check test failed:", e.message);
        allPassed = false;
    }

    console.log("\n============================================");
    console.log(allPassed ? "All tests PASSED!" : "Some tests FAILED!");
    console.log("============================================");

    process.exit(allPassed ? 0 : 1);
}

main().catch(console.error);

