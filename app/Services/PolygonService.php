<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use kornrunner\Keccak;
use kornrunner\Ethereum\Transaction;

/**
 * Polygon Blockchain Service
 * Service untuk interaksi dengan Polygon Network
 * Enhanced with Web3.php library integration
 */
class PolygonService
{
    private $rpcUrl;
    private $chainId;
    private $contractAddress;
    private $privateKey;
    private $web3;
    private $eth;

    public function __construct()
    {
        $this->rpcUrl = config('services.polygon.rpc_url', env('POLYGON_RPC_URL', 'https://rpc-amoy.polygon.technology/'));
        $this->chainId = config('services.polygon.chain_id', env('POLYGON_CHAIN_ID', '80002'));
        $this->contractAddress = config('services.polygon.contract_address', env('POLYGON_CONTRACT_ADDRESS'));
        $this->privateKey = config('services.polygon.private_key', env('POLYGON_PRIVATE_KEY'));
        
        // Initialize Web3
        $this->initializeWeb3();
    }
    
    /**
     * Initialize Web3 instance
     */
    private function initializeWeb3()
    {
        try {
            $this->web3 = new Web3($this->rpcUrl);
            $this->eth = $this->web3->eth;
            Log::info('[PolygonService] Web3 initialized successfully');
        } catch (\Exception $e) {
            Log::error('[PolygonService] Failed to initialize Web3: ' . $e->getMessage());
            $this->web3 = null;
            $this->eth = null;
        }
    }

    /**
     * Send transaction to Polygon
     */
    public function sendTransaction($to, $amount, $data = null)
    {
        try {
            // Get master wallet address
            $masterWallet = env('POLYGON_MASTER_WALLET');

            if (!$masterWallet) {
                Log::error('[PolygonService] No master wallet configured');
                return null;
            }

            if (!$this->privateKey) {
                Log::error('[PolygonService] No private key configured');
                return null;
            }

            // Convert amount to wei
            $amountInWei = $this->toWei($amount);

            // Get nonce for master wallet
            $nonce = $this->getTransactionCount($masterWallet);

            // Build transaction
            $transaction = [
                'from' => $masterWallet,
                'to' => $to,
                'value' => $amountInWei,
                'gas' => '0x5208', // 21000
                'gasPrice' => $this->getGasPrice(),
                'chainId' => $this->chainId,
                'nonce' => '0x' . dechex($nonce),
            ];

            if ($data) {
                $transaction['data'] = $data;
            }

            // Sign transaction
            $signedTx = $this->signTransaction($transaction);

            // Send via RPC
            $response = $this->rpcCall('eth_sendRawTransaction', [$signedTx]);

            if (isset($response['result'])) {
                Log::info('Transaction sent to Polygon: ' . $response['result']);
                return $response['result'];
            }

            Log::error('Failed to send transaction: ' . json_encode($response));
            return null;

        } catch (\Exception $e) {
            Log::error('Polygon Transaction Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get transaction receipt
     */
    public function getTransactionReceipt($txHash)
    {
        try {
            $response = $this->rpcCall('eth_getTransactionReceipt', [$txHash]);
            
            if (isset($response['result'])) {
                return $response['result'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Get Receipt Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get balance using Web3
     */
    public function getBalance($address)
    {
        try {
            if ($this->eth) {
                // Use Web3.php
                $balance = null;
                $this->eth->getBalance($address, 'latest', function ($err, $result) use (&$balance) {
                    if ($err !== null) {
                        throw new \Exception($err->getMessage());
                    }
                    $balance = $result;
                });
                
                if ($balance) {
                    return $this->fromWei($balance->toString());
                }
            }
            
            // Fallback to RPC
            $response = $this->rpcCall('eth_getBalance', [$address, 'latest']);
            
            if (isset($response['result'])) {
                // Convert from wei to ether
                return $this->fromWei(hexdec($response['result']));
            }

            return 0;
        } catch (\Exception $e) {
            Log::error('Get Balance Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get gas price
     */
    public function getGasPrice()
    {
        try {
            $response = $this->rpcCall('eth_gasPrice', []);
            
            if (isset($response['result'])) {
                return $response['result'];
            }

            return '0x9502F900'; // Default 2.5 Gwei
        } catch (\Exception $e) {
            Log::error('Get Gas Price Error: ' . $e->getMessage());
            return '0x9502F900';
        }
    }

    /**
     * Call smart contract method
     */
    public function callContract($method, $params = [])
    {
        try {
            // Encode function call
            $data = $this->encodeFunctionCall($method, $params);

            $transaction = [
                'to' => $this->contractAddress,
                'data' => $data,
            ];

            $response = $this->rpcCall('eth_call', [$transaction, 'latest']);

            if (isset($response['result'])) {
                return $this->decodeResult($response['result']);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Contract Call Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * RPC Call to Polygon
     */
    private function rpcCall($method, $params = [])
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => time(),
        ];

        $response = Http::timeout(30)
            ->post($this->rpcUrl, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('RPC call failed: ' . $response->body());
    }

    /**
     * Sign transaction using ethereum-offline-raw-tx library
     */
    private function signTransaction($transaction)
    {
        try {
            if (!$this->privateKey) {
                Log::error('[PolygonService] No private key configured');
                return null;
            }

            // Remove 0x prefix if present
            $privateKey = preg_replace('/^0x/', '', $this->privateKey);

            // Prepare transaction data for ethereum-offline-raw-tx
            $txData = [
                'nonce' => hexdec($transaction['nonce']),
                'gasPrice' => hexdec($transaction['gasPrice']),
                'gasLimit' => hexdec($transaction['gas']),
                'to' => $transaction['to'],
                'value' => hexdec($transaction['value']),
                'data' => $transaction['data'] ?? '',
                'chainId' => $transaction['chainId'],
            ];

            // Create and sign transaction
            $transactionObj = new Transaction($txData);
            $signedTransaction = $transactionObj->sign($privateKey);

            return '0x' . $signedTransaction;

        } catch (\Exception $e) {
            Log::error('[PolygonService] Transaction signing error: ' . $e->getMessage());
            return null;
        }
    }
    
    
    /**
     * Get transaction count (nonce)
     */
    public function getTransactionCount($address)
    {
        try {
            if ($this->eth && $address) {
                $count = null;
                $this->eth->getTransactionCount($address, 'pending', function ($err, $result) use (&$count) {
                    if ($err === null) {
                        $count = $result;
                    }
                });
                
                if ($count) {
                    return hexdec($count->toString());
                }
            }
            
            // Fallback
            $response = $this->rpcCall('eth_getTransactionCount', [$address, 'pending']);
            return isset($response['result']) ? hexdec($response['result']) : 0;
            
        } catch (\Exception $e) {
            Log::error('[PolygonService] Get transaction count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Encode function call (simplified)
     */
    private function encodeFunctionCall($method, $params)
    {
        // Dalam production, gunakan proper ABI encoding
        // Gunakan library web3.php atau manual encoding sesuai ABI spec
        
        $methodSignature = substr(hash('sha256', $method), 0, 8);
        $encodedParams = bin2hex(json_encode($params));
        
        return '0x' . $methodSignature . $encodedParams;
    }

    /**
     * Decode result (simplified)
     */
    private function decodeResult($result)
    {
        // Dalam production, decode sesuai ABI
        return $result;
    }

    /**
     * Convert to Wei
     */
    private function toWei($amount)
    {
        // 1 ether = 10^18 wei
        $weiAmount = bcmul($amount, '1000000000000000000');
        return '0x' . dechex((int)$weiAmount);
    }

    /**
     * Convert from Wei using Utils
     */
    private function fromWei($wei)
    {
        try {
            if (class_exists('Web3\\Utils')) {
                return Utils::fromWei($wei, 'ether');
            }
        } catch (\Exception $e) {
            // Fallback to manual calculation
        }
        
        return bcdiv((string)$wei, '1000000000000000000', 18);
    }
    
    /**
     * Create contract instance
     */
    public function getContract($abi)
    {
        try {
            if (!$this->web3 || !$this->contractAddress) {
                throw new \Exception('Web3 or contract address not configured');
            }
            
            $contract = new Contract($this->rpcUrl, $abi);
            $contract->at($this->contractAddress);
            
            return $contract;
            
        } catch (\Exception $e) {
            Log::error('[PolygonService] Contract initialization error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Estimate gas for transaction
     */
    public function estimateGas($transaction)
    {
        try {
            if ($this->eth) {
                $gas = null;
                $this->eth->estimateGas($transaction, function ($err, $result) use (&$gas) {
                    if ($err === null) {
                        $gas = $result;
                    }
                });
                
                if ($gas) {
                    return hexdec($gas->toString());
                }
            }
            
            // Fallback to RPC
            $response = $this->rpcCall('eth_estimateGas', [$transaction]);
            return isset($response['result']) ? hexdec($response['result']) : 21000;
            
        } catch (\Exception $e) {
            Log::error('[PolygonService] Gas estimation error: ' . $e->getMessage());
            return 21000;
        }
    }

    /**
     * Verify transaction on blockchain
     */
    public function verifyTransaction($txHash)
    {
        try {
            $receipt = $this->getTransactionReceipt($txHash);
            
            if (!$receipt) {
                return [
                    'verified' => false,
                    'error' => 'Transaction not found',
                ];
            }

            return [
                'verified' => true,
                'status' => $receipt['status'] === '0x1' ? 'success' : 'failed',
                'blockNumber' => hexdec($receipt['blockNumber']),
                'gasUsed' => hexdec($receipt['gasUsed']),
            ];
        } catch (\Exception $e) {
            Log::error('Verify Transaction Error: ' . $e->getMessage());
            return [
                'verified' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get real-time balance from blockchain
     * Sync balance directly from Polygon network
     */
    public function getRealTimeBalance($address)
    {
        try {
            Log::info('[PolygonService] Getting real-time balance for: ' . $address);
            
            $balance = $this->getBalance($address);
            
            return [
                'success' => true,
                'balance' => $balance,
                'address' => $address,
                'network' => 'Polygon Amoy Testnet',
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('[PolygonService] Real-time balance error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balance' => 0,
            ];
        }
    }

    /**
     * Create new wallet on blockchain
     */
    public function createBlockchainWallet()
    {
        try {
            // Generate new wallet
            $privateKey = bin2hex(random_bytes(32));
            $publicKey = $this->getPublicKeyFromPrivate($privateKey);
            $address = $this->getAddressFromPublicKey($publicKey);
            
            Log::info('[PolygonService] New blockchain wallet created: ' . $address);
            
            return [
                'success' => true,
                'address' => $address,
                'public_key' => $publicKey,
                'private_key' => $privateKey, // Should be encrypted before storage
            ];
        } catch (\Exception $e) {
            Log::error('[PolygonService] Wallet creation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get public key from private key
     */
    private function getPublicKeyFromPrivate($privateKey)
    {
        // In production, use proper secp256k1 curve
        // For now, simplified version
        return '04' . hash('sha256', $privateKey) . hash('sha256', $privateKey . 'pub');
    }

    /**
     * Get address from public key
     */
    private function getAddressFromPublicKey($publicKey)
    {
        // Remove '04' prefix if present
        $publicKey = preg_replace('/^04/', '', $publicKey);
        
        // Hash public key with Keccak-256
        $hash = Keccak::hash(hex2bin($publicKey), 256);
        
        // Take last 20 bytes (40 hex chars) and prepend 0x
        $address = '0x' . substr($hash, -40);
        
        return $address;
    }

    /**
     * Transfer MATIC to wallet (for top-up)
     */
    public function transferMatic($toAddress, $amount)
    {
        try {
            Log::info('[PolygonService] Transferring MATIC', [
                'to' => $toAddress,
                'amount' => $amount,
            ]);

            // Get master wallet address
            $masterWallet = env('POLYGON_MASTER_WALLET');
            
            if (!$masterWallet) {
                Log::warning('[PolygonService] No master wallet configured, using simulation mode');
                // Simulation mode - return fake tx hash
                $txHash = '0x' . bin2hex(random_bytes(32));
                
                return [
                    'success' => true,
                    'tx_hash' => $txHash,
                    'amount' => $amount,
                    'to' => $toAddress,
                    'simulation' => true,
                ];
            }

            // Send transaction
            $txHash = $this->sendTransaction($toAddress, $amount);
            
            if ($txHash) {
                return [
                    'success' => true,
                    'tx_hash' => $txHash,
                    'amount' => $amount,
                    'to' => $toAddress,
                    'simulation' => false,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to send transaction',
            ];

        } catch (\Exception $e) {
            Log::error('[PolygonService] Transfer error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current MATIC price in IDR
     * In production, use price oracle or API like CoinGecko
     */
    public function getMaticPriceInIDR()
    {
        try {
            // Use CoinGecko API to get real-time price
            $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => 'matic-network',
                'vs_currencies' => 'idr',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $price = $data['matic-network']['idr'] ?? null;
                
                if ($price) {
                    Log::info('[PolygonService] MATIC price: IDR ' . $price);
                    return $price;
                }
            }

            // Fallback price if API fails
            Log::warning('[PolygonService] Using fallback MATIC price');
            return 10000; // Default fallback: 10,000 IDR per MATIC

        } catch (\Exception $e) {
            Log::error('[PolygonService] Price fetch error: ' . $e->getMessage());
            return 10000; // Fallback price
        }
    }

    /**
     * Convert IDR to MATIC
     */
    public function convertIDRtoMatic($idrAmount)
    {
        try {
            $maticPrice = $this->getMaticPriceInIDR();
            $maticAmount = $idrAmount / $maticPrice;
            
            Log::info('[PolygonService] Conversion', [
                'idr' => $idrAmount,
                'matic' => $maticAmount,
                'rate' => $maticPrice,
            ]);

            return [
                'success' => true,
                'idr_amount' => $idrAmount,
                'matic_amount' => round($maticAmount, 8),
                'exchange_rate' => $maticPrice,
            ];
        } catch (\Exception $e) {
            Log::error('[PolygonService] Conversion error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync wallet balance from blockchain
     */
    public function syncWalletBalance($walletAddress)
    {
        try {
            $balanceData = $this->getRealTimeBalance($walletAddress);
            
            if ($balanceData['success']) {
                // Update wallet in database
                $wallet = \App\Models\Wallet::where('polygon_address', $walletAddress)->first();
                
                if ($wallet) {
                    $wallet->balance = $balanceData['balance'];
                    $wallet->save();
                    
                    Log::info('[PolygonService] Wallet balance synced', [
                        'wallet_id' => $wallet->id,
                        'balance' => $balanceData['balance'],
                    ]);
                }
                
                return $balanceData;
            }

            return $balanceData;
        } catch (\Exception $e) {
            Log::error('[PolygonService] Sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

