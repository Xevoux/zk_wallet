@extends('layouts.app')

@section('title', 'My Wallet - ZK Payment')

@section('content')
<div class="wallet-container">
    <div class="wallet-header">
        <h1><i class="fas fa-wallet"></i> My Wallet</h1>
        <p>Kelola wallet dan terima pembayaran dengan QR Code</p>
    </div>

    <div class="wallet-grid">
        <!-- Wallet Info Card -->
        <div class="wallet-info-card">
            <h2>Informasi Wallet</h2>
            
            <div class="wallet-detail">
                <label><i class="fas fa-coins"></i> Saldo Tersedia</label>
                <div class="balance-display">
                    <h1 class="balance-amount">{{ number_format($wallet->balance, 8) }} MATIC</h1>
                    <a href="{{ route('wallet.topup.show') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Top-Up Saldo
                    </a>
                </div>
            </div>

            <div class="wallet-detail">
                <label><i class="fas fa-map-marker-alt"></i> Alamat Wallet</label>
                <div class="address-field">
                    <code id="walletAddress">{{ $wallet->wallet_address }}</code>
                    <button class="btn-copy" onclick="copyToClipboard('{{ $wallet->wallet_address }}', 'Alamat wallet')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            @if($wallet->polygon_address)
            <div class="wallet-detail">
                <label><i class="fas fa-link"></i> Polygon Address</label>
                <div class="address-field">
                    <code>{{ $wallet->polygon_address }}</code>
                    <button class="btn-copy" onclick="copyToClipboard('{{ $wallet->polygon_address }}', 'Polygon address')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            @endif

            <div class="wallet-detail">
                <label><i class="fas fa-key"></i> Public Key</label>
                <div class="address-field">
                    {{-- Default tampil sebagai simbol bullet untuk keamanan visual --}}
                    <code
                        id="publicKeyDisplay"
                        data-full-key="{{ $wallet->public_key }}"
                    >
                        {{ str_repeat('•', 16) }}
                    </code>
                    <button
                        class="btn-copy"
                        onclick="copyToClipboard('{{ $wallet->public_key }}', 'Public key')"
                        title="Copy full public key"
                    >
                        <i class="fas fa-copy"></i>
                    </button>
                    <button
                        class="btn-toggle"
                        onclick="togglePublicKeyDisplay()"
                        title="Tampilkan public key"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="wallet-actions">
                <a href="{{ route('wallet.topup.show') }}" class="btn btn-success">
                    <i class="fas fa-wallet"></i> Top-Up Saldo
                </a>
                <a href="{{ route('payment.form') }}" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Kirim Uang
                </a>
                <a href="{{ route('payment.scan') }}" class="btn btn-secondary">
                    <i class="fas fa-qrcode"></i> Scan QR
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Test MATIC Faucet Card -->
        <div class="faucet-card">
            <h2><i class="fas fa-faucet"></i> Test MATIC Faucet</h2>
            <p class="faucet-subtitle">Dapatkan test MATIC gratis untuk testing (Polygon Amoy Testnet)</p>

            <div class="faucet-info-box">
                <div class="faucet-stat">
                    <i class="fas fa-coins"></i>
                    <div>
                        <strong>0.01 MATIC</strong>
                        <small>Per Request</small>
                    </div>
                </div>
                <div class="faucet-stat">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong id="faucetCooldown">Checking...</strong>
                        <small>Cooldown</small>
                    </div>
                </div>
            </div>

            <button id="requestTestMaticBtn" class="btn btn-info btn-block" onclick="requestTestMatic()">
                <i class="fas fa-hand-holding-water"></i> Request Test MATIC
            </button>

            <div id="faucetStatus" class="faucet-status" style="display: none;">
                <div id="faucetMessage"></div>
            </div>

            <div class="faucet-note">
                <i class="fas fa-info-circle"></i> 
                <strong>Catatan:</strong>
                <ul>
                    <li>Test MATIC tidak memiliki nilai uang asli</li>
                    <li>Hanya untuk testing di Polygon Amoy Testnet</li>
                    <li>Request dibatasi 1x per 24 jam</li>
                    <li>Untuk production, gunakan Top-Up dengan Midtrans</li>
                </ul>
            </div>
        </div>

        <!-- QR Code Card -->
        <div class="qr-code-card">
            <h2>QR Code - Terima Pembayaran</h2>
            <p class="qr-subtitle">Scan QR code ini untuk menerima pembayaran</p>

            <!-- QR Code Display -->
            <div class="qr-display-area">
                <div id="defaultQRCode" class="qr-code-container">
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="Wallet QR Code" class="qr-image">
                    <p class="qr-label">Wallet Address QR</p>
                </div>

                <div id="customQRCode" class="qr-code-container" style="display: none;">
                    <img id="customQRImage" src="" alt="Custom QR Code" class="qr-image">
                    <p class="qr-label">Payment Request QR</p>
                </div>
            </div>

            <!-- Custom QR Generator -->
            <div class="qr-generator">
                <h3>Generate QR dengan Jumlah</h3>
                <form id="generateCustomQR">
                    @csrf
                    <div class="form-group">
                        <label for="qr_amount">Jumlah (Opsional)</label>
                        <input type="number" id="qr_amount" name="amount" step="0.01" min="0.01" placeholder="Masukkan jumlah">
                    </div>
                    
                    <div class="form-group">
                        <label for="qr_description">Deskripsi (Opsional)</label>
                        <input type="text" id="qr_description" name="description" placeholder="Contoh: Pembayaran produk">
                    </div>

                    <div class="qr-actions">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-magic"></i> Generate QR Custom
                        </button>
                        <button type="button" class="btn btn-secondary btn-block" onclick="resetToDefaultQR()">
                            <i class="fas fa-redo"></i> Reset ke QR Default
                        </button>
                        <button type="button" class="btn btn-success btn-block" onclick="downloadCurrentQR()">
                            <i class="fas fa-download"></i> Download QR Code
                        </button>
                    </div>
                </form>
            </div>

            <div class="qr-info">
                <p><i class="fas fa-lightbulb"></i> <strong>Tips:</strong></p>
                <ul>
                    <li>QR Default: Untuk menerima pembayaran dengan jumlah fleksibel</li>
                    <li>QR Custom: Untuk request pembayaran dengan jumlah tertentu</li>
                    <li>Share QR code ini ke pembayar untuk menerima uang</li>
                </ul>
            </div>
        </div>
        
        <!-- Wallet Logs Card -->
        <div class="logs-card">
            <h3><i class="fas fa-terminal"></i> Wallet Logs</h3>
            <div id="walletLogs" class="logs-container">
                <div class="log-entry log-info">
                    <span class="log-time">[{{ date('H:i:s') }}]</span>
                    <span class="log-message">Wallet system initialized</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Topup Modal -->
<div id="topupModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-coins"></i> Topup Saldo</h2>
            <button class="modal-close" onclick="closeTopupModal()">&times;</button>
        </div>
        <form id="topupForm">
            @csrf
            <div class="form-group">
                <label for="topup_amount">Jumlah Topup (Rp)</label>
                <input type="number" id="topup_amount" name="amount" step="1" min="1" max="10000" required placeholder="Masukkan jumlah">
                <small>Maksimal Rp 10.000 per topup (untuk demo)</small>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">Topup Sekarang</button>
                <button type="button" class="btn btn-secondary" onclick="closeTopupModal()">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/wallet.css') }}">
@endpush

@push('scripts')
<!-- Hidden data for JavaScript -->
<input type="hidden" id="faucetCheckUrl" value="{{ route('wallet.faucet.can-request') }}">
<input type="hidden" id="faucetRequestUrl" value="{{ route('wallet.faucet.request') }}">
<input type="hidden" id="qrDownloadUrl" value="{{ route('wallet.download-qr') }}">

<script>
// Add data-* attributes for routes
document.addEventListener('DOMContentLoaded', function() {
    const qrForm = document.getElementById('generateCustomQR');
    if (qrForm) {
        qrForm.dataset.generateUrl = '{{ route("wallet.generate-receive-qr") }}';
    }
});
</script>
<script src="{{ asset('js/wallet.js') }}"></script>
@endpush

