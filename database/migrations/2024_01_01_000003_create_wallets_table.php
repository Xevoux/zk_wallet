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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('wallet_address')->unique();
            $table->string('public_key');
            $table->text('encrypted_private_key');
            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('blockchain_balance', 20, 8)->default(0)->comment('Balance from blockchain');
            $table->string('polygon_address')->nullable();
            $table->text('zk_proof_commitment')->nullable(); // Commitment untuk zk-SNARK
            $table->timestamp('last_blockchain_sync')->nullable()->comment('Last time balance was synced from blockchain');
            $table->boolean('auto_sync_enabled')->default(true)->comment('Enable automatic balance sync');
            $table->timestamps();

            // Indexes for performance
            $table->index('wallet_address');
            $table->index('polygon_address');
            $table->index(['user_id', 'balance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

