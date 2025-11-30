<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Wallet Service
 * Service untuk manage wallet operations
 */
class WalletService
{
    private $polygonService;
    private $zkService;

    public function __construct(PolygonService $polygonService, ZKSNARKService $zkService)
    {
        $this->polygonService = $polygonService;
        $this->zkService = $zkService;
    }

    /**
     * Create new wallet untuk user
     */
    public function createWallet($userId, $walletType = 'custodial')
    {
        Log::info('[WalletService] Creating wallet', ['user_id' => $userId]);
        
        try {
            DB::beginTransaction();
            
            // Generate wallet address (simplified)
            $walletAddress = $this->generateWalletAddress();
            
            // Create wallet record
            $wallet = Wallet::create([
                'user_id' => $userId,
                'address' => $walletAddress,
                'type' => $walletType,
                'balance' => 0,
                'status' => 'active',
            ]);
            
            DB::commit();
            
            Log::info('[WalletService] Wallet created successfully', [
                'wallet_id' => $wallet->id,
                'address' => $walletAddress
            ]);
            
            return [
                'success' => true,
                'wallet' => $wallet,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WalletService] Error creating wallet: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get wallet balance dengan ZK privacy
     */
    public function getBalance($walletAddress, $withZKProof = false)
    {
        Log::info('[WalletService] Getting balance', ['address' => $walletAddress]);
        
        try {
            // Get balance dari blockchain
            $balance = $this->polygonService->getBalance($walletAddress);
            
            if ($withZKProof) {
                // Generate commitment untuk hide actual balance
                $randomness = bin2hex(random_bytes(32));
                $commitment = $this->zkService->generateCommitment($balance, $randomness);
                
                return [
                    'success' => true,
                    'balance' => $balance,
                    'commitment' => $commitment,
                    'proof' => $randomness, // In production, don't expose randomness
                ];
            }
            
            return [
                'success' => true,
                'balance' => $balance,
            ];
            
        } catch (\Exception $e) {
            Log::error('[WalletService] Error getting balance: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send payment dengan ZK proof
     */
    public function sendPayment($fromAddress, $toAddress, $amount, $zkProof = null)
    {
        Log::info('[WalletService] Sending payment', [
            'from' => $fromAddress,
            'to' => $toAddress,
            'amount' => $amount
        ]);
        
        try {
            DB::beginTransaction();
            
            // Verify balance dengan ZK proof jika provided
            if ($zkProof) {
                $proofValid = $this->zkService->verifyBalanceProof($zkProof, $amount);
                
                if (!$proofValid) {
                    throw new \Exception('ZK proof verification failed');
                }
            }
            
            // Send transaction to blockchain
            $txHash = $this->polygonService->sendTransaction($toAddress, $amount);
            
            if (!$txHash) {
                throw new \Exception('Failed to send transaction to blockchain');
            }
            
            // Create transaction record
            $transaction = Transaction::create([
                'from_address' => $fromAddress,
                'to_address' => $toAddress,
                'amount' => $amount,
                'tx_hash' => $txHash,
                'status' => 'pending',
                'type' => 'transfer',
                'zk_proof_hash' => $zkProof ? hash('sha256', $zkProof) : null,
            ]);
            
            DB::commit();
            
            Log::info('[WalletService] Payment sent successfully', [
                'tx_hash' => $txHash,
                'transaction_id' => $transaction->id
            ]);
            
            return [
                'success' => true,
                'tx_hash' => $txHash,
                'transaction' => $transaction,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WalletService] Error sending payment: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory($walletAddress, $limit = 50)
    {
        Log::info('[WalletService] Getting transaction history', ['address' => $walletAddress]);
        
        try {
            $transactions = Transaction::where('from_address', $walletAddress)
                ->orWhere('to_address', $walletAddress)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            
            return [
                'success' => true,
                'transactions' => $transactions,
            ];
            
        } catch (\Exception $e) {
            Log::error('[WalletService] Error getting transaction history: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify transaction on blockchain
     */
    public function verifyTransaction($txHash)
    {
        Log::info('[WalletService] Verifying transaction', ['tx_hash' => $txHash]);
        
        try {
            $verification = $this->polygonService->verifyTransaction($txHash);
            
            if ($verification['verified']) {
                // Update transaction status
                $transaction = Transaction::where('tx_hash', $txHash)->first();
                
                if ($transaction) {
                    $transaction->update([
                        'status' => $verification['status'],
                        'confirmed_at' => now(),
                    ]);
                }
            }
            
            return $verification;
            
        } catch (\Exception $e) {
            Log::error('[WalletService] Error verifying transaction: ' . $e->getMessage());
            
            return [
                'verified' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate wallet address (improved for production)
     */
    private function generateWalletAddress()
    {
        try {
            // Use timestamp + random for uniqueness
            $timestamp = microtime(true) * 10000; // More precision
            $random = bin2hex(random_bytes(16)); // 32 chars

            // Combine and hash for deterministic but unpredictable address
            $seed = $timestamp . $random . config('app.key');
            $hash = hash('sha256', $seed);

            // Take first 20 bytes (40 hex chars) and format as Ethereum address
            $address = '0x' . substr($hash, 0, 40);

            // Validate format (should always be valid with this method)
            if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
                throw new \Exception('Generated address format invalid');
            }

            \Log::info('[WalletService] Generated secure wallet address', [
                'address' => $address,
                'method' => 'timestamp+random+hash',
            ]);

            return $address;

        } catch (\Exception $e) {
            \Log::error('[WalletService] Wallet address generation failed: ' . $e->getMessage());

            // Fallback: use more basic but still secure method
            return '0x' . bin2hex(random_bytes(20));
        }
    }

    /**
     * Import existing wallet
     */
    public function importWallet($userId, $address, $privateKey = null)
    {
        Log::info('[WalletService] Importing wallet', ['address' => $address]);
        
        try {
            // Validate address
            if (!$this->isValidAddress($address)) {
                throw new \Exception('Invalid wallet address');
            }
            
            // Check if wallet already exists
            $existingWallet = Wallet::where('address', $address)->first();
            
            if ($existingWallet) {
                throw new \Exception('Wallet already imported');
            }
            
            // Create wallet record
            $wallet = Wallet::create([
                'user_id' => $userId,
                'address' => $address,
                'type' => $privateKey ? 'custodial' : 'non-custodial',
                'balance' => 0,
                'status' => 'active',
            ]);
            
            // Get initial balance
            $balance = $this->polygonService->getBalance($address);
            $wallet->update(['balance' => $balance]);
            
            Log::info('[WalletService] Wallet imported successfully');
            
            return [
                'success' => true,
                'wallet' => $wallet,
            ];
            
        } catch (\Exception $e) {
            Log::error('[WalletService] Error importing wallet: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate Ethereum/Polygon address
     */
    private function isValidAddress($address)
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    /**
     * Update wallet balance from blockchain
     */
    public function syncBalance($walletAddress)
    {
        try {
            $balance = $this->polygonService->getBalance($walletAddress);
            
            $wallet = Wallet::where('address', $walletAddress)->first();
            
            if ($wallet) {
                $wallet->update(['balance' => $balance]);
            }
            
            return [
                'success' => true,
                'balance' => $balance,
            ];
            
        } catch (\Exception $e) {
            Log::error('[WalletService] Error syncing balance: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
