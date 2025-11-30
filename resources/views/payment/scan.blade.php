@extends('layouts.app')

@section('title', 'Scan QR Code - ZK Payment')

@section('content')
<div class="scan-container">
    <div class="scan-header">
        <h1><i class="fas fa-qrcode"></i> Scan QR Code</h1>
        <p>Scan QR code untuk melakukan pembayaran</p>
    </div>

    <div class="scan-content">
        <div class="scan-left-panel">
            <div class="scanner-card">
                <div class="scanner-wrapper">
                    <div id="reader"></div>
                    <div id="manualInput" style="display: none;">
                        <h3>Input Manual</h3>
                        <textarea id="qrDataInput" class="qr-input" placeholder="Paste data QR code di sini"></textarea>
                        <button class="btn btn-primary" onclick="processManualInput()">Proses</button>
                    </div>
                </div>
                
                <div class="scanner-actions">
                    <button class="btn btn-secondary" onclick="toggleManualInput()">Input Manual</button>
                    <button class="btn btn-secondary" onclick="stopScanner()">Berhenti</button>
                </div>
            </div>

            <div id="paymentConfirmation" class="payment-confirmation" style="display: none;">
                <h2>Konfirmasi Pembayaran</h2>
                
                <div class="confirmation-details">
                    <div class="detail-row">
                        <label>Alamat Penerima:</label>
                        <span id="confirmReceiverAddress"></span>
                    </div>
                    <div class="detail-row">
                        <label>Jumlah:</label>
                        <span id="confirmAmount" class="amount-highlight"></span>
                    </div>
                    <div class="detail-row">
                        <label>Waktu:</label>
                        <span id="confirmTimestamp"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="useZkProof">
                        <span>Gunakan ZK-SNARK untuk privasi <i class="fas fa-lock"></i></span>
                    </label>
                </div>

                <div class="confirmation-actions">
                    <button class="btn btn-primary" onclick="confirmPayment()">Konfirmasi & Bayar</button>
                    <button class="btn btn-secondary" onclick="cancelPayment()">Batal</button>
                </div>
            </div>
        </div>
        
        <div class="process-logs-scan">
            <h3><i class="fas fa-terminal"></i> Process Logs</h3>
            <div id="scanLogs" class="logs-container">
                <div class="log-entry log-info">
                    <span class="log-time">[00:00:00]</span>
                    <span class="log-message">Scanner initializing...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Hidden data for JavaScript -->
<span id="userBalance" style="display: none;" data-balance="{{ Auth::user()->wallet->balance ?? 0 }}"></span>
<input type="hidden" id="processUrl" value="{{ route('payment.process') }}">
<input type="hidden" id="dashboardUrl" value="{{ route('dashboard') }}">

<script src="https://unpkg.com/html5-qrcode"></script>
<script src="{{ asset('js/zk-snark.js') }}"></script>
<script src="{{ asset('js/qr-scanner.js') }}"></script>
@endpush

