@extends('layouts.app')

@section('title', 'Pembayaran - ZK Payment')

@section('content')
<div class="payment-container">
    <div class="payment-header">
        <h1><i class="fas fa-money-bill-wave"></i> Pembayaran Digital</h1>
        <p>Kirim uang dengan aman menggunakan QR Code</p>
    </div>

    <div class="payment-grid">
        <!-- Form Pembayaran -->
        <div class="payment-card">
            <h2>Buat Pembayaran</h2>
            
            <div class="payment-tabs">
                <button class="tab-btn active" onclick="switchTab('qr')">Generate QR Code</button>
                <button class="tab-btn" onclick="switchTab('manual')">Transfer Manual</button>
            </div>

            <!-- Tab Generate QR -->
            <div id="qr-tab" class="tab-content active">
                <form id="generateQRForm" class="payment-form">
                    @csrf
                    <div class="form-group">
                        <label for="amount">Jumlah (Rp)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required placeholder="Masukkan jumlah">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Generate QR Code
                    </button>
                </form>

                <div id="qrCodeResult" class="qr-result" style="display: none;">
                    <h3>QR Code Pembayaran</h3>
                    <div class="qr-code-container">
                        <img id="qrCodeImage" src="" alt="QR Code">
                    </div>
                    <p class="qr-instruction">Tunjukkan QR Code ini ke penerima untuk melakukan pembayaran</p>
                    <button class="btn btn-secondary" onclick="downloadQR()">Download QR</button>
                </div>
            </div>

            <!-- Tab Transfer Manual -->
            <div id="manual-tab" class="tab-content">
                <form id="manualPaymentForm" class="payment-form">
                    @csrf
                    <div class="form-group">
                        <label for="receiver_address">Alamat Wallet Penerima</label>
                        <input type="text" id="receiver_address" name="receiver_address" required placeholder="ZKWALLET...">
                    </div>

                    <div class="form-group">
                        <label for="manual_amount">Jumlah (Rp)</label>
                        <input type="number" id="manual_amount" name="amount" step="0.01" min="0.01" required placeholder="Masukkan jumlah">
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan (Opsional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Tambahkan catatan"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="private_transaction" name="private_transaction">
                            <span>Transaksi Privat (Gunakan ZK-SNARK) <i class="fas fa-lock"></i></span>
                        </label>
                        <small>Transaksi privat menyembunyikan detail dengan zero-knowledge proof</small>
                    </div>

                    <div class="wallet-balance-info">
                        <p>Saldo Anda: <strong>Rp {{ number_format($wallet->balance, 2, ',', '.') }}</strong></p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Kirim Pembayaran
                    </button>
                </form>
            </div>
        </div>

        <!-- Process Logs -->
        <div class="process-logs-sidebar">
            <h3><i class="fas fa-terminal"></i> Process Logs</h3>
            <div id="paymentLogs" class="logs-container">
                <div class="log-entry log-info">
                    <span class="log-time">[00:00:00]</span>
                    <span class="log-message">Payment system ready</span>
                </div>
            </div>
            
            <div class="wallet-info-mini">
                <p><strong>Saldo:</strong> Rp {{ number_format($wallet->balance, 2, ',', '.') }}</p>
                <p><strong>Alamat:</strong> <code>{{ Str::limit($wallet->wallet_address, 15) }}</code></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Hidden data for JavaScript -->
<span id="walletBalance" style="display: none;" data-balance="{{ $wallet->balance }}">Rp {{ number_format($wallet->balance, 2, ',', '.') }}</span>

<script src="{{ asset('js/zk-snark.js') }}"></script>
<script>
// Add data-* attributes for routes
document.addEventListener('DOMContentLoaded', function() {
    const qrForm = document.getElementById('generateQRForm');
    if (qrForm) {
        qrForm.dataset.generateUrl = '{{ route("payment.generate-qr") }}';
    }
    
    const manualForm = document.getElementById('manualPaymentForm');
    if (manualForm) {
        manualForm.dataset.processUrl = '{{ route("payment.process") }}';
        manualForm.dataset.dashboardUrl = '{{ route("dashboard") }}';
    }
});
</script>
<script src="{{ asset('js/payment-form.js') }}"></script>
@endpush

