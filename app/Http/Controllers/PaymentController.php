<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentController extends Controller
{
    public function showPaymentForm()
    {
        $wallet = Auth::user()->wallet;
        return view('payment.form', compact('wallet'));
    }

    public function generateQRCode(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $wallet = Auth::user()->wallet;
        $amount = $request->amount;

        // Generate payment data untuk QR Code
        $paymentData = json_encode([
            'wallet_address' => $wallet->wallet_address,
            'amount' => $amount,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16)),
        ]);

        // Generate QR Code (use SVG format - doesn't require imagick/GD)
        $qrCode = base64_encode(QrCode::format('svg')->size(300)->generate($paymentData));

        return response()->json([
            'success' => true,
            'qr_code' => $qrCode,
            'payment_data' => $paymentData,
            'format' => 'svg',
        ]);
    }

    public function scanQRCode()
    {
        return view('payment.scan');
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'receiver_address' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'zk_proof' => 'nullable|string',
        ]);

        $senderWallet = Auth::user()->wallet;
        $receiverWallet = Wallet::where('wallet_address', $request->receiver_address)->first();

        if (!$receiverWallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet penerima tidak ditemukan.',
            ], 404);
        }

        // Verifikasi saldo
        if ($request->has('zk_proof')) {
            // Verifikasi saldo dengan zk-SNARK proof
            $zkService = app(\App\Services\ZKSNARKService::class);
            $balanceVerified = $zkService->verifyBalanceProof($request->zk_proof, $request->amount);

            if (!$balanceVerified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi saldo ZK gagal. Pastikan proof valid dan saldo mencukupi.',
                ], 400);
            }

            \Log::info('[PaymentController] ZK balance proof verified', [
                'sender_wallet_id' => $senderWallet->id,
                'amount' => $request->amount,
            ]);
        } else {
            // Verifikasi saldo normal
            if ($senderWallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak mencukupi.',
                ], 400);
            }
        }

        try {
            DB::beginTransaction();

            // Generate transaction hash
            $transactionHash = hash('sha256', $senderWallet->wallet_address . $receiverWallet->wallet_address . $request->amount . time());

            // Generate ZK proof untuk transaksi privat
            $zkProof = null;
            $zkPublicInputs = null;
            if ($request->has('private_transaction') && $request->private_transaction) {
                $zkProof = $this->generateTransactionZKProof($senderWallet, $receiverWallet, $request->amount);
                $zkPublicInputs = json_encode([
                    'commitment' => hash('sha256', $transactionHash),
                    'timestamp' => time(),
                ]);
            }

            // Buat transaksi
            $transaction = Transaction::create([
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'amount' => $request->amount,
                'transaction_hash' => $transactionHash,
                'zk_proof' => $zkProof,
                'zk_public_inputs' => $zkPublicInputs,
                'status' => 'pending',
                'notes' => $request->notes ?? null,
            ]);

            // Update saldo
            $senderWallet->balance -= $request->amount;
            $senderWallet->save();

            $receiverWallet->balance += $request->amount;
            $receiverWallet->save();

            // Kirim transaksi ke Polygon blockchain (async)
            $this->sendToPolygon($transaction);

            // Update status transaksi
            $transaction->status = 'completed';
            $transaction->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil!',
                'transaction' => $transaction,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Transaksi gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function transactionHistory()
    {
        $wallet = Auth::user()->wallet;
        
        $transactions = Transaction::where('sender_wallet_id', $wallet->id)
            ->orWhere('receiver_wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('payment.history', compact('transactions', 'wallet'));
    }

    /**
     * Generate ZK proof for private transaction
     * @deprecated Use ZKSNARKService for proper proof generation
     */
    private function generateTransactionZKProof($senderWallet, $receiverWallet, $amount)
    {
        // This should be handled client-side with proper ZK circuit
        // For now, return a placeholder that indicates this needs client-side generation
        \Log::warning('[PaymentController] Transaction ZK proof generation not implemented client-side');

        // Return null to indicate proof generation failed
        return null;
    }

    private function sendToPolygon($transaction)
    {
        try {
            Log::info('[PaymentController] Sending transaction to Polygon blockchain', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'sender_id' => $transaction->sender_wallet_id,
                'receiver_id' => $transaction->receiver_wallet_id,
            ]);

            // Get wallet addresses
            $senderWallet = $transaction->senderWallet;
            $receiverWallet = $transaction->receiverWallet;

            if (!$senderWallet || !$receiverWallet) {
                Log::error('[PaymentController] Wallet not found', [
                    'sender_wallet_id' => $transaction->sender_wallet_id,
                    'receiver_wallet_id' => $transaction->receiver_wallet_id,
                ]);
                throw new \Exception('Wallet not found');
            }

            // Use PolygonService to send transaction
            $polygonService = app(\App\Services\PolygonService::class);

            // Send transaction from sender to receiver
            $txResult = $polygonService->sendTransaction(
                $receiverWallet->polygon_address ?? $receiverWallet->wallet_address,
                $transaction->amount
            );

            if ($txResult['success']) {
                // Update transaction with real blockchain hash
                $transaction->polygon_tx_hash = $txResult['tx_hash'];
                $transaction->status = 'completed';
                $transaction->save();

                Log::info('[PaymentController] Transaction sent to blockchain successfully', [
                    'transaction_id' => $transaction->id,
                    'polygon_tx_hash' => $txResult['tx_hash'],
                ]);

                return $txResult;
            } else {
                Log::error('[PaymentController] Blockchain transaction failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $txResult['error'] ?? 'Unknown error',
                ]);
                throw new \Exception($txResult['error'] ?? 'Blockchain transaction failed');
            }

        } catch (\Exception $e) {
            Log::error('[PaymentController] Send to polygon error: ' . $e->getMessage(), [
                'transaction_id' => $transaction->id,
            ]);

            // Update transaction status to failed
            $transaction->status = 'failed';
            $transaction->save();

            throw $e;
        }
    }
}

