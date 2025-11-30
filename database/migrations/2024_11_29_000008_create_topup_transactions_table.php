<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('topup_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique()->comment('Midtrans order ID');
            $table->decimal('idr_amount', 20, 2)->comment('Amount in IDR');
            $table->decimal('crypto_amount', 20, 8)->comment('Converted amount in MATIC');
            $table->decimal('exchange_rate', 20, 2)->comment('IDR to MATIC exchange rate');
            $table->string('payment_type')->comment('Midtrans payment type');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'expired'])->default('pending');
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_status')->nullable();
            $table->string('polygon_tx_hash')->nullable()->comment('Blockchain transaction hash');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable()->comment('Blockchain confirmation time');
            $table->text('midtrans_response')->nullable()->comment('Full Midtrans response');
            $table->text('blockchain_response')->nullable()->comment('Blockchain response');
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('order_id');
            $table->index('polygon_tx_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topup_transactions');
    }
};

