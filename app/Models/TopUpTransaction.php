<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopUpTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'order_id',
        'idr_amount',
        'crypto_amount',
        'exchange_rate',
        'payment_type',
        'status',
        'midtrans_transaction_id',
        'midtrans_status',
        'polygon_tx_hash',
        'paid_at',
        'confirmed_at',
        'midtrans_response',
        'blockchain_response',
    ];

    protected $casts = [
        'idr_amount' => 'decimal:2',
        'crypto_amount' => 'decimal:8',
        'exchange_rate' => 'decimal:2',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'midtrans_response' => 'array',
        'blockchain_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark as completed with blockchain hash
     */
    public function markAsCompleted(string $txHash): void
    {
        $this->update([
            'status' => 'completed',
            'polygon_tx_hash' => $txHash,
            'confirmed_at' => now(),
        ]);
    }
}

