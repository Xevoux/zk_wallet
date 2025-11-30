<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_address',
        'public_key',
        'encrypted_private_key',
        'balance',
        'blockchain_balance',
        'polygon_address',
        'zk_proof_commitment',
        'last_blockchain_sync',
        'auto_sync_enabled',
    ];

    protected $hidden = [
        'encrypted_private_key',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'blockchain_balance' => 'decimal:8',
        'last_blockchain_sync' => 'datetime',
        'auto_sync_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_wallet_id');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'receiver_wallet_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_wallet_id')
            ->union($this->hasMany(Transaction::class, 'receiver_wallet_id'));
    }
}

