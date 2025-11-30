<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\TopUpTransaction;
use App\Services\MidtransService;
use App\Services\PolygonService;
use App\Services\FaucetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WalletController extends Controller
{
    protected $midtransService;
    protected $polygonService;

    public function __construct(MidtransService $midtransService, PolygonService $polygonService)
    {
        $this->midtransService = $midtransService;
        $this->polygonService = $polygonService;
    }
    /**
     * Menampilkan detail wallet dengan QR code
     */
    public function index()
    {
        $wallet = Auth::user()->wallet;
        
        if (!$wallet) {
            return redirect()->route('dashboard')->with('error', 'Wallet tidak ditemukan.');
        }

        // Generate QR Code untuk wallet address (untuk menerima pembayaran)
        $qrCodeData = json_encode([
            'type' => 'wallet_address',
            'address' => $wallet->wallet_address,
            'name' => Auth::user()->name,
            'timestamp' => time(),
        ]);

        // Use SVG format (doesn't require imagick/GD)
        $qrCode = base64_encode(QrCode::format('svg')->size(300)->margin(2)->generate($qrCodeData));

        return view('payment.wallet', compact('wallet', 'qrCode'));
    }

    /**
     * Generate QR code untuk menerima pembayaran dengan jumlah tertentu
     */
    public function generateReceiveQR(Request $request)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $wallet = Auth::user()->wallet;
        
        // Data untuk QR code
        $paymentData = [
            'type' => 'payment_request',
            'wallet_address' => $wallet->wallet_address,
            'receiver_name' => Auth::user()->name,
            'amount' => $request->amount ?? null,
            'description' => $request->description ?? null,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16)),
        ];

        // Generate QR Code (use SVG format)
        $qrCode = base64_encode(QrCode::format('svg')
            ->size(350)
            ->margin(2)
            ->errorCorrection('H')
            ->generate(json_encode($paymentData)));

        return response()->json([
            'success' => true,
            'qr_code' => $qrCode,
            'payment_data' => $paymentData,
            'format' => 'svg',
        ]);
    }

    /**
     * Decode QR code data
     */
    public function decodeQR(Request $request)
    {
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        try {
            $data = json_decode($request->qr_data, true);
            
            if (!$data || !isset($data['wallet_address'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code tidak valid',
                ], 400);
            }

            // Verify wallet exists with user relationship
            $wallet = Wallet::with('user')->where('wallet_address', $data['wallet_address'])->first();
            
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'wallet_exists' => true,
                'receiver_name' => $wallet->user->name ?? 'Unknown',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal decode QR Code: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download QR code sebagai image
     */
    public function downloadQR(Request $request)
    {
        $wallet = Auth::user()->wallet;
        
        $qrCodeData = json_encode([
            'type' => 'wallet_address',
            'address' => $wallet->wallet_address,
            'name' => Auth::user()->name,
            'timestamp' => time(),
        ]);

        // Use SVG format
        $qrCode = QrCode::format('svg')
            ->size(400)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($qrCodeData);

        return response($qrCode, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="wallet-qr-' . substr($wallet->wallet_address, 0, 15) . '.svg"');
    }

    /**
     * Get wallet info
     */
    public function getInfo()
    {
        $wallet = Auth::user()->wallet;
        
        return response()->json([
            'success' => true,
            'wallet' => [
                'address' => $wallet->wallet_address,
                'balance' => number_format($wallet->balance, 2, '.', ''),
                'polygon_address' => $wallet->polygon_address,
                'public_key' => $wallet->public_key,
            ],
        ]);
    }

    /**
     * Show top-up page
     */
    public function showTopUp()
    {
        $wallet = Auth::user()->wallet;
        
        if (!$wallet) {
            return redirect()->route('dashboard')->with('error', 'Wallet tidak ditemukan.');
        }

        // Sync balance from blockchain
        if ($wallet->polygon_address) {
            $this->syncBalance($wallet);
        }

        // Get recent top-up transactions
        $recentTopups = TopUpTransaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('payment.topup', compact('wallet', 'recentTopups'));
    }

    /**
     * Create top-up transaction with Midtrans
     */
    public function createTopUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:10000000', // Min 10k IDR, Max 10M IDR
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $wallet = $user->wallet;

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet tidak ditemukan',
                ], 404);
            }

            // Generate unique order ID
            $orderId = 'TOPUP-' . $user->id . '-' . time() . '-' . strtoupper(substr(uniqid(), -6));

            // Get MATIC price and convert
            $conversion = $this->polygonService->convertIDRtoMatic($request->amount);

            if (!$conversion['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengkonversi IDR ke MATIC',
                ], 500);
            }

            // Create top-up transaction record
            $topupTransaction = TopUpTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'order_id' => $orderId,
                'idr_amount' => $request->amount,
                'crypto_amount' => $conversion['matic_amount'],
                'exchange_rate' => $conversion['exchange_rate'],
                'payment_type' => 'pending',
                'status' => 'pending',
            ]);

            // Create Midtrans transaction
            $customerDetails = [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789',
            ];

            $midtransResult = $this->midtransService->createTransaction(
                $orderId,
                $request->amount,
                $customerDetails
            );

            if (!$midtransResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat transaksi pembayaran',
                    'error' => $midtransResult['error'] ?? 'Unknown error',
                ], 500);
            }

            // Update transaction with Midtrans data
            $topupTransaction->update([
                'midtrans_response' => $midtransResult,
            ]);

            DB::commit();

            Log::info('[WalletController] Top-up transaction created', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'idr_amount' => $request->amount,
                'matic_amount' => $conversion['matic_amount'],
            ]);

            return response()->json([
                'success' => true,
                'snap_token' => $midtransResult['snap_token'],
                'order_id' => $orderId,
                'idr_amount' => $request->amount,
                'matic_amount' => $conversion['matic_amount'],
                'exchange_rate' => $conversion['exchange_rate'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WalletController] Top-up creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Midtrans notification webhook
     */
    public function handleMidtransNotification(Request $request)
    {
        try {
            $notification = $request->all();
            
            Log::info('[WalletController] Midtrans notification received', [
                'order_id' => $notification['order_id'] ?? null,
            ]);

            // Handle notification via service
            $result = $this->midtransService->handleNotification($notification);

            if (!$result['success']) {
                return response()->json(['success' => false], 400);
            }

            // Find top-up transaction
            $topupTransaction = TopUpTransaction::where('order_id', $result['order_id'])->first();

            if (!$topupTransaction) {
                Log::error('[WalletController] Transaction not found: ' . $result['order_id']);
                return response()->json(['success' => false], 404);
            }

            // Update transaction status
            $topupTransaction->update([
                'status' => $result['status'],
                'midtrans_status' => $result['transaction_status'],
                'midtrans_transaction_id' => $notification['transaction_id'] ?? null,
                'payment_type' => $result['payment_type'] ?? 'unknown',
                'paid_at' => $result['status'] === 'completed' ? now() : null,
                'midtrans_response' => $notification,
            ]);

            // If payment completed, transfer crypto to wallet
            if ($result['status'] === 'completed' && !$topupTransaction->polygon_tx_hash) {
                $this->processCompletedTopUp($topupTransaction);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('[WalletController] Notification handling error: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Process completed top-up - transfer crypto to wallet
     */
    private function processCompletedTopUp(TopUpTransaction $transaction)
    {
        try {
            DB::beginTransaction();

            $wallet = $transaction->wallet;

            // Ensure wallet has polygon address
            if (!$wallet->polygon_address) {
                // Create blockchain wallet
                $blockchainWallet = $this->polygonService->createBlockchainWallet();
                
                if ($blockchainWallet['success']) {
                    $wallet->update([
                        'polygon_address' => $blockchainWallet['address'],
                        'public_key' => $blockchainWallet['public_key'],
                        'encrypted_private_key' => encrypt($blockchainWallet['private_key']),
                    ]);
                }
            }

            // Transfer MATIC to user wallet
            $transferResult = $this->polygonService->transferMatic(
                $wallet->polygon_address,
                $transaction->crypto_amount
            );

            if ($transferResult['success']) {
                // Update transaction with blockchain hash
                $transaction->markAsCompleted($transferResult['tx_hash']);
                
                // Update wallet balance
                $wallet->balance += $transaction->crypto_amount;
                $wallet->save();

                Log::info('[WalletController] Top-up completed successfully', [
                    'order_id' => $transaction->order_id,
                    'tx_hash' => $transferResult['tx_hash'],
                    'matic_amount' => $transaction->crypto_amount,
                ]);

                DB::commit();
                return true;
            } else {
                Log::error('[WalletController] Blockchain transfer failed', [
                    'order_id' => $transaction->order_id,
                    'error' => $transferResult['error'] ?? 'Unknown',
                ]);
                DB::rollBack();
                return false;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WalletController] Process completed top-up error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check top-up status
     */
    public function checkTopUpStatus($orderId)
    {
        try {
            $transaction = TopUpTransaction::where('order_id', $orderId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'transaction' => [
                    'order_id' => $transaction->order_id,
                    'status' => $transaction->status,
                    'idr_amount' => $transaction->idr_amount,
                    'crypto_amount' => $transaction->crypto_amount,
                    'polygon_tx_hash' => $transaction->polygon_tx_hash,
                    'created_at' => $transaction->created_at->toIso8601String(),
                    'paid_at' => $transaction->paid_at?->toIso8601String(),
                    'confirmed_at' => $transaction->confirmed_at?->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finish callback from Midtrans
     */
    public function finishTopUp(Request $request)
    {
        $orderId = $request->get('order_id');
        
        if ($orderId) {
            $transaction = TopUpTransaction::where('order_id', $orderId)
                ->where('user_id', Auth::id())
                ->first();

            if ($transaction) {
                $message = match($transaction->status) {
                    'completed' => 'Top-up berhasil! Saldo Anda telah ditambahkan.',
                    'pending' => 'Pembayaran sedang diproses. Silakan tunggu konfirmasi.',
                    'failed' => 'Pembayaran gagal. Silakan coba lagi.',
                    default => 'Status pembayaran: ' . $transaction->status,
                };

                return redirect()->route('wallet.index')->with('message', $message);
            }
        }

        return redirect()->route('wallet.index');
    }

    /**
     * Sync wallet balance from blockchain
     */
    public function syncBalance(Wallet $wallet = null)
    {
        try {
            if (!$wallet) {
                $wallet = Auth::user()->wallet;
            }

            if (!$wallet || !$wallet->polygon_address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet atau alamat Polygon tidak ditemukan',
                ], 404);
            }

            $result = $this->polygonService->syncWalletBalance($wallet->polygon_address);

            return response()->json([
                'success' => $result['success'],
                'balance' => $result['balance'] ?? 0,
                'address' => $wallet->polygon_address,
            ]);

        } catch (\Exception $e) {
            Log::error('[WalletController] Sync balance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time balance from blockchain
     */
    public function getRealTimeBalance()
    {
        try {
            $wallet = Auth::user()->wallet;

            if (!$wallet || !$wallet->polygon_address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet tidak ditemukan',
                ], 404);
            }

            $balanceData = $this->polygonService->getRealTimeBalance($wallet->polygon_address);

            return response()->json($balanceData);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request test MATIC from faucet
     */
    public function requestTestMatic(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Log::warning('[WalletController] User not authenticated for faucet request');
                return response()->json([
                    'success' => false,
                    'error' => 'User tidak terautentikasi',
                ], 401);
            }

            $wallet = $user->wallet;
            if (!$wallet) {
                Log::error('[WalletController] Wallet not found for user', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'error' => 'Wallet tidak ditemukan',
                ], 404);
            }

            Log::info('[WalletController] Processing faucet request', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'has_polygon_address' => !empty($wallet->polygon_address),
            ]);

            // Ensure wallet has polygon address
            if (!$wallet->polygon_address) {
                Log::info('[WalletController] Creating blockchain wallet for faucet request');

                $blockchainWallet = $this->polygonService->createBlockchainWallet();

                if (!$blockchainWallet['success']) {
                    Log::error('[WalletController] Failed to create blockchain wallet', [
                        'user_id' => $user->id,
                        'error' => $blockchainWallet['error'] ?? 'Unknown error',
                    ]);
                    return response()->json([
                        'success' => false,
                        'error' => 'Gagal membuat blockchain wallet: ' . ($blockchainWallet['error'] ?? 'Unknown error'),
                    ], 500);
                }

                // Update wallet with blockchain address
                try {
                    $wallet->update([
                        'polygon_address' => $blockchainWallet['address'],
                        'public_key' => $blockchainWallet['public_key'],
                        'encrypted_private_key' => encrypt($blockchainWallet['private_key']),
                    ]);

                    Log::info('[WalletController] Blockchain wallet created and linked', [
                        'user_id' => $user->id,
                        'polygon_address' => $blockchainWallet['address'],
                    ]);
                } catch (\Exception $updateError) {
                    Log::error('[WalletController] Failed to update wallet with blockchain address', [
                        'user_id' => $user->id,
                        'error' => $updateError->getMessage(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'error' => 'Gagal menyimpan informasi blockchain wallet',
                    ], 500);
                }
            }

            // Request test MATIC from faucet
            $faucetService = app(FaucetService::class);
            $result = $faucetService->requestTestMatic($user->id, $wallet->polygon_address);

            if ($result['success']) {
                // Update wallet balance if not simulation
                if (!($result['simulation'] ?? false)) {
                    try {
                        $wallet->balance += $result['amount'];
                        $wallet->save();

                        Log::info('[WalletController] Wallet balance updated after faucet distribution', [
                            'user_id' => $user->id,
                            'amount_added' => $result['amount'],
                            'new_balance' => $wallet->balance,
                        ]);
                    } catch (\Exception $balanceError) {
                        Log::error('[WalletController] Failed to update wallet balance', [
                            'user_id' => $user->id,
                            'error' => $balanceError->getMessage(),
                        ]);
                        // Don't fail the request, but log the issue
                    }
                }

                Log::info('[WalletController] Test MATIC requested successfully', [
                    'user_id' => $user->id,
                    'amount' => $result['amount'],
                    'tx_hash' => $result['tx_hash'],
                    'simulation' => $result['simulation'] ?? false,
                ]);

                return response()->json($result);
            } else {
                Log::warning('[WalletController] Faucet request failed', [
                    'user_id' => $user->id,
                    'error' => $result['error'] ?? 'Unknown error',
                    'retry_after' => $result['retry_after_seconds'] ?? null,
                ]);

                return response()->json($result, 400);
            }

        } catch (\Exception $e) {
            Log::error('[WalletController] Request test MATIC error', [
                'user_id' => Auth::id() ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan internal. Silakan coba lagi.',
                'debug_error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get faucet request history
     */
    public function getFaucetHistory()
    {
        try {
            $user = Auth::user();
            $faucetService = app(FaucetService::class);
            
            $result = $faucetService->getFaucetHistory($user->id);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'requests' => [],
            ], 500);
        }
    }

    /**
     * Check if user can request test MATIC
     */
    public function canRequestTestMatic()
    {
        try {
            $user = Auth::user();
            $faucetService = app(FaucetService::class);
            
            $canRequest = $faucetService->canRequestTestMatic($user->id);
            $remainingTime = $faucetService->getTimeUntilNextRequest($user->id);

            return response()->json([
                'success' => true,
                'can_request' => $canRequest,
                'remaining_seconds' => $remainingTime,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

