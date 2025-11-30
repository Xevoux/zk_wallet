@extends('layouts.app')

@section('title', 'Riwayat Transaksi - ZK Payment')

@section('content')
<div class="history-container">
    <div class="history-header">
        <h1><i class="fas fa-history"></i> Riwayat Transaksi</h1>
        <p>Semua transaksi Anda</p>
    </div>

    <div class="history-filters">
        <div class="filter-group">
            <label>Filter Status:</label>
            <select id="statusFilter" onchange="filterTransactions()">
                <option value="all">Semua</option>
                <option value="completed">Selesai</option>
                <option value="pending">Pending</option>
                <option value="failed">Gagal</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Tipe:</label>
            <select id="typeFilter" onchange="filterTransactions()">
                <option value="all">Semua</option>
                <option value="sent">Terkirim</option>
                <option value="received">Diterima</option>
            </select>
        </div>
    </div>

    <div class="transactions-table-card">
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Tanggal & Waktu</th>
                    <th>Tipe</th>
                    <th>Dari/Ke</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Privasi</th>
                    <th>Blockchain</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr class="transaction-row" 
                    data-status="{{ $transaction->status }}" 
                    data-type="{{ $transaction->sender_wallet_id == $wallet->id ? 'sent' : 'received' }}">
                    <td>{{ $transaction->created_at->format('d M Y, H:i:s') }}</td>
                    <td>
                        @if($transaction->sender_wallet_id == $wallet->id)
                            <span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Terkirim</span>
                        @else
                            <span class="badge badge-success"><i class="fas fa-arrow-down"></i> Diterima</span>
                        @endif
                    </td>
                    <td>
                        @if($transaction->sender_wallet_id == $wallet->id)
                            <code class="wallet-code">{{ Str::limit($transaction->receiverWallet->wallet_address, 20) }}</code>
                        @else
                            <code class="wallet-code">{{ Str::limit($transaction->senderWallet->wallet_address, 20) }}</code>
                        @endif
                    </td>
                    <td>
                        <span class="amount {{ $transaction->sender_wallet_id == $wallet->id ? 'negative' : 'positive' }}">
                            {{ $transaction->sender_wallet_id == $wallet->id ? '-' : '+' }} 
                            Rp {{ number_format($transaction->amount, 2, ',', '.') }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $transaction->status == 'completed' ? 'success' : ($transaction->status == 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($transaction->zk_proof)
                            <span class="badge badge-primary" title="Transaksi menggunakan ZK-SNARK"><i class="fas fa-lock"></i> Private</span>
                        @else
                            <span class="badge badge-secondary"><i class="fas fa-globe"></i> Public</span>
                        @endif
                    </td>
                    <td>
                        @if($transaction->polygon_tx_hash)
                            <a href="https://polygonscan.com/tx/{{ $transaction->polygon_tx_hash }}" 
                               target="_blank" 
                               class="blockchain-link" 
                               title="Lihat di Polygonscan">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn-icon" onclick="showTransactionDetails('{{ $transaction->id }}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="empty-state">
                            <p>Belum ada transaksi</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $transactions->links() }}
    </div>
</div>

<!-- Modal Detail Transaksi -->
<div id="transactionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Detail Transaksi</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="transactionDetails">
            <!-- Details will be loaded here -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function filterTransactions() {
        const statusFilter = document.getElementById('statusFilter').value;
        const typeFilter = document.getElementById('typeFilter').value;
        const rows = document.querySelectorAll('.transaction-row');

        rows.forEach(row => {
            const status = row.dataset.status;
            const type = row.dataset.type;
            
            let showRow = true;
            
            if (statusFilter !== 'all' && status !== statusFilter) {
                showRow = false;
            }
            
            if (typeFilter !== 'all' && type !== typeFilter) {
                showRow = false;
            }
            
            row.style.display = showRow ? '' : 'none';
        });
    }

    function showTransactionDetails(transactionId) {
        // Dalam implementasi nyata, ini akan fetch detail dari server
        document.getElementById('transactionModal').style.display = 'flex';
        
        // Simulasi loading details
        document.getElementById('transactionDetails').innerHTML = `
            <div class="loading">Loading...</div>
        `;
        
        // Simulasi fetch data
        setTimeout(() => {
            document.getElementById('transactionDetails').innerHTML = `
                <div class="detail-section">
                    <h3>Informasi Transaksi</h3>
                    <p><strong>Transaction ID:</strong> ${transactionId}</p>
                    <p><strong>Hash:</strong> <code>0x...</code></p>
                    <p><strong>Status:</strong> Completed</p>
                </div>
            `;
        }, 500);
    }

    function closeModal() {
        document.getElementById('transactionModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
@endpush

