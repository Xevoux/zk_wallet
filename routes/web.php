<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Midtrans Webhook (no auth required)
Route::post('/webhook/midtrans', [\App\Http\Controllers\WalletController::class, 'handleMidtransNotification'])->name('webhook.midtrans');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Wallet Routes
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WalletController::class, 'index'])->name('index');
        Route::post('/generate-receive-qr', [\App\Http\Controllers\WalletController::class, 'generateReceiveQR'])->name('generate-receive-qr');
        Route::post('/decode-qr', [\App\Http\Controllers\WalletController::class, 'decodeQR'])->name('decode-qr');
        Route::get('/download-qr', [\App\Http\Controllers\WalletController::class, 'downloadQR'])->name('download-qr');
        Route::get('/info', [\App\Http\Controllers\WalletController::class, 'getInfo'])->name('info');
        
        // Top-up routes
        Route::get('/topup', [\App\Http\Controllers\WalletController::class, 'showTopUp'])->name('topup.show');
        Route::post('/topup/create', [\App\Http\Controllers\WalletController::class, 'createTopUp'])->name('topup.create');
        Route::get('/topup/status/{orderId}', [\App\Http\Controllers\WalletController::class, 'checkTopUpStatus'])->name('topup.status');
        Route::get('/topup/finish', [\App\Http\Controllers\WalletController::class, 'finishTopUp'])->name('topup.finish');
        
        // Balance sync
        Route::get('/balance/sync', [\App\Http\Controllers\WalletController::class, 'syncBalance'])->name('balance.sync');
        Route::get('/balance/realtime', [\App\Http\Controllers\WalletController::class, 'getRealTimeBalance'])->name('balance.realtime');
        
        // Faucet routes
        Route::post('/faucet/request', [\App\Http\Controllers\WalletController::class, 'requestTestMatic'])->name('faucet.request');
        Route::get('/faucet/history', [\App\Http\Controllers\WalletController::class, 'getFaucetHistory'])->name('faucet.history');
        Route::get('/faucet/can-request', [\App\Http\Controllers\WalletController::class, 'canRequestTestMatic'])->name('faucet.can-request');
    });
    
    // Payment Routes
    Route::get('/payment', [PaymentController::class, 'showPaymentForm'])->name('payment.form');
    Route::post('/payment/generate-qr', [PaymentController::class, 'generateQRCode'])->name('payment.generate-qr');
    Route::get('/payment/scan', [PaymentController::class, 'scanQRCode'])->name('payment.scan');
    Route::post('/payment/process', [PaymentController::class, 'processPayment'])->name('payment.process');
    Route::get('/payment/history', [PaymentController::class, 'transactionHistory'])->name('payment.history');
});
