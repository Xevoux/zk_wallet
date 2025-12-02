<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use kornrunner\Keccak;
use kornrunner\Ethereum\Transaction;
use Elliptic\EC;

/**
 * Polygon Blockchain Service
 * Service untuk interaksi dengan Polygon Network
 * Uses proper secp256k1 cryptography for wallet generation
 */
class PolygonService
{
    private $rpcUrl;
    private $chainId;
    private $contractAddress;
    private $privateKey;
    private $web3;
    private $eth;
    private $ec;

    public function __construct()
    {
        $this->rpcUrl = config('services.polygon.rpc_url', env('POLYGON_RPC_URL', 'https://rpc-amoy.polygon.technology/'));
        $this->chainId = config('services.polygon.chain_id', env('POLYGON_CHAIN_ID', '80002'));
        $this->contractAddress = config('services.polygon.contract_address', env('POLYGON_CONTRACT_ADDRESS'));
        $this->privateKey = config('services.polygon.private_key', env('POLYGON_PRIVATE_KEY'));
        
        // Initialize secp256k1 elliptic curve
        $this->ec = new EC('secp256k1');
        
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
     * Create new wallet on blockchain with PROPER secp256k1 cryptography
     * This creates a REAL Polygon-compatible wallet
     */
    public function createBlockchainWallet()
    {
        try {
            // Generate cryptographically secure random 32 bytes (256 bits) for private key
            $privateKeyBytes = random_bytes(32);
            $privateKey = bin2hex($privateKeyBytes);
            
            // Generate key pair using secp256k1 elliptic curve
            $keyPair = $this->ec->keyFromPrivate($privateKey);
            
            // Get public key (uncompressed format: 04 + x + y = 65 bytes = 130 hex chars)
            $publicKeyPoint = $keyPair->getPublic();
            $publicKeyX = str_pad($publicKeyPoint->getX()->toString(16), 64, '0', STR_PAD_LEFT);
            $publicKeyY = str_pad($publicKeyPoint->getY()->toString(16), 64, '0', STR_PAD_LEFT);
            $publicKey = '04' . $publicKeyX . $publicKeyY;
            
            // Derive address from public key
            // 1. Hash public key (without 04 prefix) with Keccak-256
            // 2. Take last 20 bytes (40 hex chars)
            // 3. Prepend 0x
            $publicKeyWithoutPrefix = $publicKeyX . $publicKeyY;
            $hash = Keccak::hash(hex2bin($publicKeyWithoutPrefix), 256);
            $address = '0x' . substr($hash, -40);
            
            // Apply EIP-55 checksum encoding to address
            $checksumAddress = $this->toChecksumAddress($address);
            
            Log::info('[PolygonService] New blockchain wallet created', [
                'address' => $checksumAddress,
                'public_key_length' => strlen($publicKey),
            ]);
            
            return [
                'success' => true,
                'address' => $checksumAddress,
                'public_key' => $publicKey,
                'private_key' => $privateKey,
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
     * Convert address to EIP-55 checksum format
     * This makes addresses compatible with MetaMask and other wallets
     */
    private function toChecksumAddress($address)
    {
        $address = strtolower(str_replace('0x', '', $address));
        $hash = Keccak::hash($address, 256);
        
        $checksumAddress = '0x';
        for ($i = 0; $i < 40; $i++) {
            if (intval($hash[$i], 16) >= 8) {
                $checksumAddress .= strtoupper($address[$i]);
            } else {
                $checksumAddress .= $address[$i];
            }
        }
        
        return $checksumAddress;
    }

    /**
     * Verify that a private key is valid and matches an address
     */
    public function verifyPrivateKey($privateKey, $expectedAddress)
    {
        try {
            $keyPair = $this->ec->keyFromPrivate($privateKey);
            $publicKeyPoint = $keyPair->getPublic();
            
            $publicKeyX = str_pad($publicKeyPoint->getX()->toString(16), 64, '0', STR_PAD_LEFT);
            $publicKeyY = str_pad($publicKeyPoint->getY()->toString(16), 64, '0', STR_PAD_LEFT);
            
            $hash = Keccak::hash(hex2bin($publicKeyX . $publicKeyY), 256);
            $derivedAddress = '0x' . substr($hash, -40);
            
            return strtolower($derivedAddress) === strtolower($expectedAddress);
            
        } catch (\Exception $e) {
            Log::error('[PolygonService] Private key verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sign a message with private key
     */
    public function signMessage($message, $privateKey)
    {
        try {
            $keyPair = $this->ec->keyFromPrivate($privateKey);
            $messageHash = Keccak::hash($message, 256);
            
            $signature = $keyPair->sign($messageHash, ['canonical' => true]);
            
            $r = str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT);
            $s = str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT);
            $v = dechex($signature->recoveryParam + 27);
            
            return [
                'success' => true,
                'signature' => '0x' . $r . $s . $v,
                'r' => '0x' . $r,
                's' => '0x' . $s,
                'v' => '0x' . $v,
            ];
            
        } catch (\Exception $e) {
            Log::error('[PolygonService] Message signing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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
        $methodSignature = substr(Keccak::hash($method, 256), 0, 8);
        $encodedParams = '';
        
        foreach ($params as $param) {
            if (is_numeric($param)) {
                $encodedParams .= str_pad(dechex($param), 64, '0', STR_PAD_LEFT);
            } elseif (strpos($param, '0x') === 0) {
                $encodedParams .= str_pad(substr($param, 2), 64, '0', STR_PAD_LEFT);
            } else {
                $encodedParams .= bin2hex($param);
            }
        }
        
        return '0x' . $methodSignature . $encodedParams;
    }

    /**
     * Decode result (simplified)
     */
    private function decodeResult($result)
    {
        return $result;
    }

    /**
     * Convert to Wei
     */
    private function toWei($amount)
    {
        // 1 ether = 10^18 wei
        $weiAmount = bcmul((string)$amount, '1000000000000000000', 0);
        return '0x' . $this->bcdechex($weiAmount);
    }

    /**
     * Convert large decimal to hex
     */
    private function bcdechex($dec)
    {
        $hex = '';
        do {
            $last = bcmod($dec, 16);
            $hex = dechex($last) . $hex;
            $dec = bcdiv(bcsub($dec, $last), 16);
        } while ($dec > 0);
        
        return $hex ?: '0';
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
     */
    public function getMaticPriceInIDR()
    {
        try {
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

            Log::warning('[PolygonService] Using fallback MATIC price');
            return 10000;

        } catch (\Exception $e) {
            Log::error('[PolygonService] Price fetch error: ' . $e->getMessage());
            return 10000;
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
     * Sync wallet balance from blockchain and update database
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
                    $wallet->last_sync_at = now();
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

    /**
     * Validate Polygon address format
     */
    public function isValidAddress($address)
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    /**
     * Get network info
     */
    public function getNetworkInfo()
    {
        return [
            'rpc_url' => $this->rpcUrl,
            'chain_id' => $this->chainId,
            'contract_address' => $this->contractAddress,
            'network_name' => $this->chainId == '137' ? 'Polygon Mainnet' : 'Polygon Amoy Testnet',
        ];
    }
}
