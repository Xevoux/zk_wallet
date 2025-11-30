/**
 * Polygon Blockchain Integration
 * Integrasi dengan Polygon Network untuk transaksi on-chain
 */

// Polygon Network Configuration
const POLYGON_CONFIG = {
    mainnet: {
        chainId: '0x89', // 137
        chainName: 'Polygon Mainnet',
        rpcUrls: ['https://polygon-rpc.com/'],
        blockExplorerUrls: ['https://polygonscan.com/'],
        nativeCurrency: {
            name: 'MATIC',
            symbol: 'MATIC',
            decimals: 18,
        },
    },
    testnet: {
        chainId: '0x13882', // 80002 - Polygon Amoy (Mumbai deprecated)
        chainName: 'Polygon Amoy Testnet',
        rpcUrls: ['https://rpc-amoy.polygon.technology/'],
        blockExplorerUrls: ['https://amoy.polygonscan.com/'],
        nativeCurrency: {
            name: 'MATIC',
            symbol: 'MATIC',
            decimals: 18,
        },
    },
};

// Smart Contract ABI (simplified)
const PAYMENT_CONTRACT_ABI = [
    {
        "inputs": [
            {"internalType": "address", "name": "recipient", "type": "address"},
            {"internalType": "uint256", "name": "amount", "type": "uint256"},
            {"internalType": "bytes", "name": "zkProof", "type": "bytes"}
        ],
        "name": "sendPayment",
        "outputs": [{"internalType": "bool", "name": "", "type": "bool"}],
        "stateMutability": "nonpayable",
        "type": "function"
    },
    {
        "inputs": [{"internalType": "address", "name": "account", "type": "address"}],
        "name": "getBalance",
        "outputs": [{"internalType": "uint256", "name": "", "type": "uint256"}],
        "stateMutability": "view",
        "type": "function"
    }
];

// Contract Address (akan diganti dengan address yang di-deploy)
const CONTRACT_ADDRESS = '0x0000000000000000000000000000000000000000'; // Placeholder

/**
 * Initialize Web3 Provider
 */
async function initWeb3() {
    if (typeof window.ethereum !== 'undefined') {
        console.log('MetaMask is installed!');
        return window.ethereum;
    } else {
        console.log('MetaMask is not installed. Using simulation mode.');
        return null;
    }
}

/**
 * Connect to MetaMask Wallet
 */
async function connectWallet() {
    try {
        const provider = await initWeb3();
        
        if (!provider) {
            alert('Silakan install MetaMask terlebih dahulu!');
            return null;
        }
        
        // Request account access
        const accounts = await provider.request({ method: 'eth_requestAccounts' });
        console.log('Connected account:', accounts[0]);
        
        // Switch to Polygon network
        await switchToPolygon();
        
        return accounts[0];
    } catch (error) {
        console.error('Error connecting wallet:', error);
        alert('Gagal menghubungkan wallet: ' + error.message);
        return null;
    }
}

/**
 * Switch to Polygon Network
 */
async function switchToPolygon(useTestnet = true) {
    try {
        const provider = await initWeb3();
        if (!provider) return false;
        
        const config = useTestnet ? POLYGON_CONFIG.testnet : POLYGON_CONFIG.mainnet;
        
        try {
            // Try to switch to the network
            await provider.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: config.chainId }],
            });
        } catch (switchError) {
            // This error code indicates that the chain has not been added to MetaMask
            if (switchError.code === 4902) {
                try {
                    await provider.request({
                        method: 'wallet_addEthereumChain',
                        params: [config],
                    });
                } catch (addError) {
                    console.error('Error adding network:', addError);
                    return false;
                }
            } else {
                console.error('Error switching network:', switchError);
                return false;
            }
        }
        
        console.log('Switched to Polygon network');
        return true;
    } catch (error) {
        console.error('Error:', error);
        return false;
    }
}

/**
 * Get Wallet Balance on Polygon
 */
async function getPolygonBalance(address) {
    try {
        const provider = await initWeb3();
        if (!provider) {
            // Simulation mode
            return simulateBalance(address);
        }
        
        const balance = await provider.request({
            method: 'eth_getBalance',
            params: [address, 'latest'],
        });
        
        // Convert from wei to ether
        const balanceInEther = parseInt(balance, 16) / Math.pow(10, 18);
        console.log('Balance:', balanceInEther, 'MATIC');
        
        return balanceInEther;
    } catch (error) {
        console.error('Error getting balance:', error);
        return 0;
    }
}

/**
 * Send Transaction to Polygon Network
 */
async function sendPolygonTransaction(to, amount, zkProof = null) {
    try {
        const provider = await initWeb3();
        
        if (!provider) {
            // Simulation mode
            return simulateTransaction(to, amount, zkProof);
        }
        
        const accounts = await provider.request({ method: 'eth_accounts' });
        const from = accounts[0];
        
        // Convert amount to wei
        const amountInWei = '0x' + (amount * Math.pow(10, 18)).toString(16);
        
        // Build transaction
        const transactionParameters = {
            from: from,
            to: to,
            value: amountInWei,
            gas: '0x5208', // 21000 gas
            gasPrice: await getGasPrice(),
        };
        
        // Add data if zkProof provided
        if (zkProof) {
            transactionParameters.data = '0x' + Buffer.from(zkProof).toString('hex');
        }
        
        // Send transaction
        const txHash = await provider.request({
            method: 'eth_sendTransaction',
            params: [transactionParameters],
        });
        
        console.log('Transaction sent:', txHash);
        
        // Wait for confirmation
        const receipt = await waitForTransaction(txHash);
        
        return {
            success: true,
            txHash: txHash,
            receipt: receipt,
        };
    } catch (error) {
        console.error('Error sending transaction:', error);
        return {
            success: false,
            error: error.message,
        };
    }
}

/**
 * Send Transaction via Smart Contract
 */
async function sendViaContract(recipient, amount, zkProof) {
    try {
        const provider = await initWeb3();
        if (!provider) {
            return simulateTransaction(recipient, amount, zkProof);
        }
        
        const accounts = await provider.request({ method: 'eth_accounts' });
        const from = accounts[0];
        
        // Encode function call
        const data = encodeFunctionCall('sendPayment', [recipient, amount, zkProof]);
        
        const transactionParameters = {
            from: from,
            to: CONTRACT_ADDRESS,
            data: data,
            gas: '0x186A0', // 100000 gas
            gasPrice: await getGasPrice(),
        };
        
        const txHash = await provider.request({
            method: 'eth_sendTransaction',
            params: [transactionParameters],
        });
        
        console.log('Contract transaction sent:', txHash);
        
        return {
            success: true,
            txHash: txHash,
        };
    } catch (error) {
        console.error('Error calling contract:', error);
        return {
            success: false,
            error: error.message,
        };
    }
}

/**
 * Get current gas price
 */
async function getGasPrice() {
    try {
        const provider = await initWeb3();
        if (!provider) {
            return '0x9502F900'; // 2.5 Gwei
        }
        
        const gasPrice = await provider.request({
            method: 'eth_gasPrice',
        });
        
        return gasPrice;
    } catch (error) {
        console.error('Error getting gas price:', error);
        return '0x9502F900'; // Default 2.5 Gwei
    }
}

/**
 * Wait for transaction confirmation
 */
async function waitForTransaction(txHash, confirmations = 1) {
    const provider = await initWeb3();
    if (!provider) return null;
    
    let receipt = null;
    let attempts = 0;
    const maxAttempts = 60; // 5 minutes (assuming 5 sec block time)
    
    while (!receipt && attempts < maxAttempts) {
        try {
            receipt = await provider.request({
                method: 'eth_getTransactionReceipt',
                params: [txHash],
            });
            
            if (!receipt) {
                await sleep(5000); // Wait 5 seconds
                attempts++;
            }
        } catch (error) {
            console.error('Error getting receipt:', error);
            await sleep(5000);
            attempts++;
        }
    }
    
    return receipt;
}

/**
 * Verify transaction on blockchain
 */
async function verifyTransaction(txHash) {
    try {
        const provider = await initWeb3();
        if (!provider) {
            return { verified: true, simulated: true };
        }
        
        const transaction = await provider.request({
            method: 'eth_getTransactionByHash',
            params: [txHash],
        });
        
        if (!transaction) {
            return { verified: false, error: 'Transaction not found' };
        }
        
        const receipt = await provider.request({
            method: 'eth_getTransactionReceipt',
            params: [txHash],
        });
        
        return {
            verified: true,
            status: receipt.status === '0x1' ? 'success' : 'failed',
            blockNumber: parseInt(receipt.blockNumber, 16),
            gasUsed: parseInt(receipt.gasUsed, 16),
        };
    } catch (error) {
        console.error('Error verifying transaction:', error);
        return { verified: false, error: error.message };
    }
}

/**
 * Get transaction history from Polygon
 */
async function getTransactionHistory(address, startBlock = 0) {
    try {
        // Dalam implementasi nyata, gunakan Polygonscan API atau The Graph
        // Untuk simulasi, return empty array
        console.log('Fetching transaction history for:', address);
        return [];
    } catch (error) {
        console.error('Error getting transaction history:', error);
        return [];
    }
}

/**
 * Deploy Smart Contract (untuk admin)
 */
async function deployContract(bytecode, abi) {
    try {
        const provider = await initWeb3();
        if (!provider) {
            console.log('Cannot deploy contract in simulation mode');
            return null;
        }
        
        const accounts = await provider.request({ method: 'eth_accounts' });
        const from = accounts[0];
        
        const transactionParameters = {
            from: from,
            data: bytecode,
            gas: '0x186A0',
            gasPrice: await getGasPrice(),
        };
        
        const txHash = await provider.request({
            method: 'eth_sendTransaction',
            params: [transactionParameters],
        });
        
        console.log('Contract deployment transaction:', txHash);
        
        const receipt = await waitForTransaction(txHash);
        const contractAddress = receipt.contractAddress;
        
        console.log('Contract deployed at:', contractAddress);
        
        return contractAddress;
    } catch (error) {
        console.error('Error deploying contract:', error);
        return null;
    }
}

// ===== SIMULATION FUNCTIONS =====

function simulateBalance(address) {
    // Simulate balance check
    return Math.random() * 100;
}

function simulateTransaction(to, amount, zkProof) {
    console.log('SIMULATION: Sending transaction');
    console.log('To:', to);
    console.log('Amount:', amount);
    console.log('ZK Proof:', zkProof ? 'Included' : 'None');
    
    // Generate fake transaction hash
    const txHash = '0x' + Array(64).fill(0).map(() => 
        Math.floor(Math.random() * 16).toString(16)
    ).join('');
    
    return {
        success: true,
        txHash: txHash,
        simulated: true,
    };
}

// ===== HELPER FUNCTIONS =====

function encodeFunctionCall(functionName, params) {
    // Simplified encoding - dalam production gunakan web3.js atau ethers.js
    return '0x' + functionName + JSON.stringify(params);
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        connectWallet,
        switchToPolygon,
        getPolygonBalance,
        sendPolygonTransaction,
        sendViaContract,
        verifyTransaction,
        getTransactionHistory,
    };
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Polygon Integration loaded');
    console.log('Ready to interact with Polygon Network');
});

