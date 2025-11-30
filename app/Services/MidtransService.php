<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Midtrans Payment Gateway Service
 * Handle IDR top-up payments via Midtrans
 */
class MidtransService
{
    private $serverKey;
    private $clientKey;
    private $isProduction;
    private $apiUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        $this->clientKey = config('services.midtrans.client_key', env('MIDTRANS_CLIENT_KEY'));
        $this->isProduction = config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false));
        
        // Set API URL based on environment
        $this->apiUrl = $this->isProduction 
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    /**
     * Create Snap transaction for top-up
     */
    public function createTransaction($orderId, $amount, $customerDetails)
    {
        try {
            Log::info('[MidtransService] Creating transaction', [
                'order_id' => $orderId,
                'amount' => $amount,
            ]);

            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $amount,
                ],
                'customer_details' => $customerDetails,
                'enabled_payments' => [
                    'credit_card',
                    'mandiri_clickpay',
                    'cimb_clicks',
                    'bca_klikbca',
                    'bca_klikpay',
                    'bri_epay',
                    'echannel',
                    'permata_va',
                    'bca_va',
                    'bni_va',
                    'bri_va',
                    'other_va',
                    'gopay',
                    'shopeepay',
                    'indomaret',
                    'alfamart',
                    'akulaku',
                    'qris',
                ],
                'credit_card' => [
                    'secure' => true,
                ],
                'callbacks' => [
                    'finish' => route('wallet.topup.finish'),
                ],
            ];

            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->post($this->apiUrl . '/transactions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('[MidtransService] Transaction created successfully', [
                    'order_id' => $orderId,
                    'token' => $data['token'] ?? null,
                ]);

                return [
                    'success' => true,
                    'snap_token' => $data['token'] ?? null,
                    'redirect_url' => $data['redirect_url'] ?? null,
                ];
            }

            Log::error('[MidtransService] Transaction creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create transaction',
                'details' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('[MidtransService] Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction status from Midtrans
     */
    public function getTransactionStatus($orderId)
    {
        try {
            $url = $this->isProduction
                ? "https://api.midtrans.com/v2/{$orderId}/status"
                : "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get status',
                'details' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('[MidtransService] Get status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify notification signature from Midtrans webhook
     */
    public function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)
    {
        $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return $mySignature === $signatureKey;
    }

    /**
     * Handle Midtrans notification/webhook
     */
    public function handleNotification($notification)
    {
        try {
            Log::info('[MidtransService] Processing notification', [
                'order_id' => $notification['order_id'] ?? null,
            ]);

            $orderId = $notification['order_id'] ?? null;
            $transactionStatus = $notification['transaction_status'] ?? null;
            $fraudStatus = $notification['fraud_status'] ?? null;
            $signatureKey = $notification['signature_key'] ?? null;
            $statusCode = $notification['status_code'] ?? null;
            $grossAmount = $notification['gross_amount'] ?? null;

            // Verify signature
            if (!$this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                Log::error('[MidtransService] Invalid signature');
                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                ];
            }

            // Determine payment status
            $status = 'pending';
            
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $status = 'completed';
                }
            } else if ($transactionStatus == 'settlement') {
                $status = 'completed';
            } else if ($transactionStatus == 'pending') {
                $status = 'pending';
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $status = 'failed';
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'status' => $status,
                'transaction_status' => $transactionStatus,
                'payment_type' => $notification['payment_type'] ?? null,
                'transaction_time' => $notification['transaction_time'] ?? null,
                'raw_notification' => $notification,
            ];

        } catch (\Exception $e) {
            Log::error('[MidtransService] Notification handling error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel transaction
     */
    public function cancelTransaction($orderId)
    {
        try {
            $url = $this->isProduction
                ? "https://api.midtrans.com/v2/{$orderId}/cancel"
                : "https://api.sandbox.midtrans.com/v2/{$orderId}/cancel";

            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->post($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to cancel transaction',
            ];

        } catch (\Exception $e) {
            Log::error('[MidtransService] Cancel error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Snap token for client-side
     */
    public function getSnapToken($orderId, $amount, $customerDetails)
    {
        $transaction = $this->createTransaction($orderId, $amount, $customerDetails);
        
        if ($transaction['success']) {
            return $transaction['snap_token'];
        }

        return null;
    }

    /**
     * Get client key for frontend
     */
    public function getClientKey()
    {
        return $this->clientKey;
    }
}

