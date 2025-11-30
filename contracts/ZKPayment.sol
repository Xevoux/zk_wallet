// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "./Groth16Verifier.sol";

/**
 * @title ZKPayment
 * @dev Main payment contract with Zero-Knowledge Proof verification
 * Implements private transactions with ZK-SNARK proofs
 */
contract ZKPayment {
    // Verifier contract instance
    Groth16Verifier public verifier;
    
    // Owner of the contract
    address public owner;
    
    // Mapping of user balances (encrypted/committed balances)
    mapping(address => uint256) public commitments;
    
    // Mapping of used nullifiers to prevent double-spending
    mapping(bytes32 => bool) public nullifiers;
    
    // Transaction counter
    uint256 public transactionCount;
    
    // Events
    event Deposit(address indexed user, uint256 commitment, uint256 amount);
    event Withdrawal(address indexed user, uint256 amount, bytes32 nullifier);
    event PrivateTransfer(bytes32 nullifier, uint256 newCommitment);
    event VerificationFailed(address indexed user, string reason);
    
    // Modifiers
    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can call this function");
        _;
    }
    
    modifier validProof(
        uint[2] memory a,
        uint[2][2] memory b,
        uint[2] memory c,
        uint[1] memory input
    ) {
        require(
            verifier.verifyProof(a, b, c, input),
            "Invalid ZK proof"
        );
        _;
    }
    
    /**
     * @dev Constructor
     * @param _verifierAddress Address of the Groth16Verifier contract
     */
    constructor(address _verifierAddress) {
        owner = msg.sender;
        verifier = Groth16Verifier(_verifierAddress);
    }
    
    /**
     * @dev Deposit funds with commitment
     * @param commitment Pedersen commitment of the balance
     */
    function deposit(uint256 commitment) external payable {
        require(msg.value > 0, "Deposit amount must be greater than 0");
        require(commitment > 0, "Invalid commitment");
        
        commitments[msg.sender] = commitment;
        
        emit Deposit(msg.sender, commitment, msg.value);
    }
    
    /**
     * @dev Withdraw funds with ZK proof
     * @param amount Amount to withdraw
     * @param nullifier Nullifier to prevent double spending
     * @param a Proof parameter a
     * @param b Proof parameter b
     * @param c Proof parameter c
     * @param input Public inputs
     */
    function withdraw(
        uint256 amount,
        bytes32 nullifier,
        uint[2] memory a,
        uint[2][2] memory b,
        uint[2] memory c,
        uint[1] memory input
    ) external validProof(a, b, c, input) {
        require(amount > 0, "Withdrawal amount must be greater than 0");
        require(!nullifiers[nullifier], "Nullifier already used");
        require(address(this).balance >= amount, "Insufficient contract balance");
        
        // Mark nullifier as used
        nullifiers[nullifier] = true;
        
        // Transfer funds
        payable(msg.sender).transfer(amount);
        
        emit Withdrawal(msg.sender, amount, nullifier);
    }
    
    /**
     * @dev Private transfer with ZK proof
     * @param nullifier Nullifier for sender's UTXO
     * @param newCommitment New commitment for receiver
     * @param a Proof parameter a
     * @param b Proof parameter b
     * @param c Proof parameter c
     * @param input Public inputs
     */
    function privateTransfer(
        bytes32 nullifier,
        uint256 newCommitment,
        uint[2] memory a,
        uint[2][2] memory b,
        uint[2] memory c,
        uint[1] memory input
    ) external validProof(a, b, c, input) {
        require(!nullifiers[nullifier], "Nullifier already used");
        require(newCommitment > 0, "Invalid commitment");
        
        // Mark nullifier as used
        nullifiers[nullifier] = true;
        
        // Increment transaction count
        transactionCount++;
        
        emit PrivateTransfer(nullifier, newCommitment);
    }
    
    /**
     * @dev Send payment with ZK proof verification
     * @param recipient Recipient address (can be encrypted)
     * @param amount Amount to send (can be hidden in commitment)
     * @param zkProofData Encoded ZK proof data
     */
    function sendPayment(
        address recipient,
        uint256 amount,
        bytes memory zkProofData
    ) external returns (bool) {
        require(recipient != address(0), "Invalid recipient address");
        require(amount > 0, "Amount must be greater than 0");
        
        // Decode proof data
        (
            uint[2] memory a,
            uint[2][2] memory b,
            uint[2] memory c,
            uint[1] memory input
        ) = abi.decode(zkProofData, (uint[2], uint[2][2], uint[2], uint[1]));
        
        // Verify proof
        bool isValid = verifier.verifyProof(a, b, c, input);
        
        if (!isValid) {
            emit VerificationFailed(msg.sender, "Invalid payment proof");
            return false;
        }
        
        // Process payment
        require(address(this).balance >= amount, "Insufficient balance");
        payable(recipient).transfer(amount);
        
        transactionCount++;
        return true;
    }
    
    /**
     * @dev Get balance (returns commitment, not actual balance)
     * @param account Account address
     * @return Commitment value
     */
    function getBalance(address account) external view returns (uint256) {
        return commitments[account];
    }
    
    /**
     * @dev Check if nullifier has been used
     * @param nullifier Nullifier to check
     * @return True if nullifier has been used
     */
    function isNullifierUsed(bytes32 nullifier) external view returns (bool) {
        return nullifiers[nullifier];
    }
    
    /**
     * @dev Update verifier contract address (only owner)
     * @param _verifierAddress New verifier address
     */
    function updateVerifier(address _verifierAddress) external onlyOwner {
        require(_verifierAddress != address(0), "Invalid verifier address");
        verifier = Groth16Verifier(_verifierAddress);
    }
    
    /**
     * @dev Get contract balance
     * @return Contract ETH/MATIC balance
     */
    function getContractBalance() external view returns (uint256) {
        return address(this).balance;
    }
    
    /**
     * @dev Emergency withdraw (only owner)
     */
    function emergencyWithdraw() external onlyOwner {
        payable(owner).transfer(address(this).balance);
    }
    
    /**
     * @dev Receive function to accept deposits
     */
    receive() external payable {
        emit Deposit(msg.sender, 0, msg.value);
    }
}
