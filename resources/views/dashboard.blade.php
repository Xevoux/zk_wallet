@extends('layouts.app')

@section('title', 'Dashboard - ZK Payment')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Selamat Datang, {{ Auth::user()->name }}!</h1>
        <p>Dashboard Wallet Anda</p>
    </div>

    <div class="wallet-card">
        <div class="wallet-header">
            <h2><i class="fas fa-wallet"></i> Wallet Saya</h2>
            <div class="wallet-badge">
                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Aktif</span>
            </div>
        </div>

        <div class="wallet-balance">
            <p class="balance-label">Saldo Tersedia</p>
            <h1 class="balance-amount">{{ number_format($wallet->balance, 8) }} MATIC</h1>
            <a href="{{ route('wallet.topup.show') }}" class="btn btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-top: 10px;">
                <i class="fas fa-plus-circle"></i> Top-Up Saldo
            </a>
        </div>

        <div class="wallet-info">
            <div class="info-item">
                <label>Alamat Wallet</label>
                <div class="wallet-address">
                    <code>{{ $wallet->wallet_address }}</code>
                    <button class="btn-copy" onclick="copyToClipboard('{{ $wallet->wallet_address }}')"><i class="fas fa-copy"></i></button>
                </div>
            </div>

            @if($wallet->polygon_address)
            <div class="info-item">
                <label>Polygon Address</label>
                <div class="wallet-address">
                    <code>{{ $wallet->polygon_address }}</code>
                    <button class="btn-copy" onclick="copyToClipboard('{{ $wallet->polygon_address }}')"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            @endif
        </div>

        <div class="wallet-actions">
            <a href="{{ route('wallet.topup.show') }}" class="btn btn-success">
                <i class="fas fa-wallet"></i> Top-Up Saldo
            </a>
            <a href="{{ route('wallet.index') }}" class="btn btn-primary">
                <i class="fas fa-wallet"></i> My Wallet
            </a>
            <a href="{{ route('payment.form') }}" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Kirim Uang
            </a>
            <a href="{{ route('payment.scan') }}" class="btn btn-secondary">
                <i class="fas fa-qrcode"></i> Scan QR Code
            </a>
        </div>
    </div>

    <div class="transactions-card">
        <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> Transaksi Terbaru</h2>
            <a href="{{ route('payment.history') }}" class="btn-link">Lihat Semua</a>
        </div>

        <div class="transactions-list">
            @forelse($recentTransactions as $transaction)
            <div class="transaction-item {{ $transaction->sender_wallet_id == $wallet->id ? 'sent' : 'received' }}">
                <div class="transaction-icon">
                    @if($transaction->sender_wallet_id == $wallet->id)
                        <i class="fas fa-arrow-up" style="color: var(--danger-color);"></i>
                    @else
                        <i class="fas fa-arrow-down" style="color: var(--success-color);"></i>
                    @endif
                </div>
                <div class="transaction-details">
                    <p class="transaction-type">
                        @if($transaction->sender_wallet_id == $wallet->id)
                            Kirim ke {{ $transaction->receiverWallet->wallet_address }}
                        @else
                            Terima dari {{ $transaction->senderWallet->wallet_address }}
                        @endif
                    </p>
                    <p class="transaction-date">{{ $transaction->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div class="transaction-amount {{ $transaction->sender_wallet_id == $wallet->id ? 'negative' : 'positive' }}">
                    {{ $transaction->sender_wallet_id == $wallet->id ? '-' : '+' }} Rp {{ number_format($transaction->amount, 2, ',', '.') }}
                </div>
                <div class="transaction-status">
                    <span class="badge badge-{{ $transaction->status == 'completed' ? 'success' : ($transaction->status == 'pending' ? 'warning' : 'danger') }}">
                        {{ ucfirst($transaction->status) }}
                    </span>
                    @if($transaction->zk_proof)
                        <span class="badge badge-primary" title="Transaksi Privat dengan ZK-SNARK"><i class="fas fa-lock"></i> Private</span>
                    @endif
                </div>
            </div>
            @empty
            <div class="empty-state">
                <p>Belum ada transaksi</p>
            </div>
            @endforelse
        </div>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Zero-Knowledge Proof</h3>
            <p>Semua transaksi Anda dilindungi dengan teknologi zk-SNARK untuk privasi maksimal</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-link"></i></div>
            <h3>Polygon Blockchain</h3>
            <p>Transaksi tercatat di blockchain Polygon untuk keamanan dan transparansi</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-qrcode"></i></div>
            <h3>QR Code P2P</h3>
            <p>Transfer mudah dengan scan QR code untuk transaksi peer-to-peer</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush

