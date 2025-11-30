<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * QR Code Service
 * Service untuk generate dan verify QR codes untuk payment
 */
class QRCodeService
{
    private $zkService;
    private $expirationMinutes = 15; // QR code valid for 15 minutes

    public function __construct(ZKSNARKService $zkService)
    {
        $this->zkService = $zkService;
    }

    /**
     * Generate QR Code untuk payment request
     */
    public function generatePaymentQR($walletAddress, $amount, $metadata = [])
    {
        Log::info('[QRCodeService] Generating payment QR code');
        
        try {
            // Generate unique code
            $codeId = $this->generateUniqueCode();
            
            // Prepare payment data
            $paymentData = [
                'code_id' => $codeId,
                'wallet_address' => $walletAddress,
                'amount' => $amount,
                'currency' => $metadata['currency'] ?? 'MATIC',
                'description' => $metadata['description'] ?? '',
                'created_at' => now()->timestamp,
                'expires_at' => now()->addMinutes($this->expirationMinutes)->timestamp,
            ];
            
            // Add digital signature for authenticity
            $paymentData['signature'] = $this->signPaymentData($paymentData);
            
            // Encrypt sensitive data
            $encryptedData = Crypt::encryptString(json_encode($paymentData));
            
            // Generate QR code
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->margin(1)
                ->generate($encryptedData);
            
            // Store QR code data in database
            $this->storeQRCodeData($codeId, $paymentData);
            
            Log::info('[QRCodeService] QR code generated successfully', [
                'code_id' => $codeId,
                'amount' => $amount
            ]);
            
            return [
                'success' => true,
                'code_id' => $codeId,
                'qr_code' => base64_encode($qrCode),
                'data' => $paymentData,
                'expires_at' => $paymentData['expires_at'],
            ];
            
        } catch (\Exception $e) {
            Log::error('[QRCodeService] Error generating QR code: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Scan and validate QR Code
     */
    public function scanQRCode($encryptedData)
    {
        Log::info('[QRCodeService] Scanning QR code');
        
        try {
            // Decrypt data
            $decryptedData = Crypt::decryptString($encryptedData);
            $paymentData = json_decode($decryptedData, true);
            
            if (!$paymentData) {
                throw new \Exception('Invalid QR code data');
            }
            
            // Verify signature
            if (!$this->verifySignature($paymentData)) {
                throw new \Exception('QR code signature verification failed');
            }
            
            // Check expiration
            if (now()->timestamp > $paymentData['expires_at']) {
                throw new \Exception('QR code has expired');
            }
            
            // Check if already used
            if ($this->isQRCodeUsed($paymentData['code_id'])) {
                throw new \Exception('QR code has already been used');
            }
            
            Log::info('[QRCodeService] QR code scanned successfully', [
                'code_id' => $paymentData['code_id']
            ]);
            
            return [
                'success' => true,
                'data' => $paymentData,
            ];
            
        } catch (\Exception $e) {
            Log::error('[QRCodeService] Error scanning QR code: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process payment dari QR code dengan ZK proof
     */
    public function processQRPayment($codeId, $senderAddress, $zkProof)
    {
        Log::info('[QRCodeService] Processing QR payment', ['code_id' => $codeId]);
        
        try {
            // Get QR code data
            $qrData = $this->getQRCodeData($codeId);
            
            if (!$qrData) {
                throw new \Exception('QR code not found');
            }
            
            // Verify ZK proof for balance
            $proofValid = $this->zkService->verifyBalanceProof(
                $zkProof,
                $qrData['amount']
            );
            
            if (!$proofValid) {
                throw new \Exception('ZK proof verification failed');
            }
            
            // Mark QR code as used
            $this->markQRCodeAsUsed($codeId);
            
            Log::info('[QRCodeService] QR payment processed successfully');
            
            return [
                'success' => true,
                'transaction_data' => [
                    'from' => $senderAddress,
                    'to' => $qrData['wallet_address'],
                    'amount' => $qrData['amount'],
                ],
            ];
            
        } catch (\Exception $e) {
            Log::error('[QRCodeService] Error processing QR payment: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate unique code ID
     */
    private function generateUniqueCode()
    {
        return 'QR' . strtoupper(bin2hex(random_bytes(16)));
    }

    /**
     * Sign payment data untuk authenticity
     */
    private function signPaymentData($data)
    {
        // Remove signature field if exists
        unset($data['signature']);
        
        // Create signature using app key
        $dataString = json_encode($data);
        return hash_hmac('sha256', $dataString, config('app.key'));
    }

    /**
     * Verify signature
     */
    private function verifySignature($data)
    {
        $signature = $data['signature'] ?? null;
        
        if (!$signature) {
            return false;
        }
        
        $expectedSignature = $this->signPaymentData($data);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Store QR code data (simplified - dalam production gunakan database)
     */
    private function storeQRCodeData($codeId, $data)
    {
        // Dalam production, simpan ke database table qr_codes
        // Untuk simulasi, gunakan cache
        cache()->put('qr_code:' . $codeId, $data, now()->addMinutes($this->expirationMinutes));
    }

    /**
     * Get QR code data
     */
    private function getQRCodeData($codeId)
    {
        return cache()->get('qr_code:' . $codeId);
    }

    /**
     * Check if QR code has been used
     */
    private function isQRCodeUsed($codeId)
    {
        return cache()->has('qr_used:' . $codeId);
    }

    /**
     * Mark QR code as used
     */
    private function markQRCodeAsUsed($codeId)
    {
        cache()->put('qr_used:' . $codeId, true, now()->addDays(30));
    }

    /**
     * Generate QR code untuk receiving payment
     */
    public function generateReceiveQR($walletAddress, $amount = null)
    {
        $metadata = [
            'type' => 'receive',
            'description' => $amount ? "Request payment of $amount MATIC" : 'Receive payment',
        ];
        
        return $this->generatePaymentQR($walletAddress, $amount ?? 0, $metadata);
    }

    /**
     * Validate QR code structure
     */
    public function validateQRStructure($data)
    {
        $requiredFields = ['code_id', 'wallet_address', 'amount', 'created_at', 'expires_at', 'signature'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
}
