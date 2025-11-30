@extends('layouts.app')

@section('title', 'Top-Up Saldo')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Top-Up Saldo Wallet</h1>
            <p class="text-gray-600">Isi saldo wallet Anda dengan IDR dan otomatis dikonversi ke MATIC</p>
        </div>

        @if(session('message'))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-blue-800">{{ session('message') }}</p>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Top-Up Form -->
            <div class="lg:col-span-2">
                <!-- Current Balance Card -->
                <div class="bg-gradient-to-br from-purple-600 to-blue-600 rounded-xl p-6 text-white mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-purple-100 text-sm mb-1">Saldo Wallet</p>
                            <h2 class="text-3xl font-bold">{{ number_format($wallet->balance, 8) }} MATIC</h2>
                        </div>
                        <button onclick="syncBalance()" id="syncButton" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                    @if($wallet->polygon_address)
                    <p class="text-purple-100 text-xs mt-2 truncate">{{ $wallet->polygon_address }}</p>
                    @endif
                </div>

                <!-- Top-Up Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-semibold mb-4">Pilih Nominal Top-Up</h3>
                    
                    <!-- Quick Amount Buttons -->
                    <div class="grid grid-cols-3 gap-3 mb-6">
                        <button onclick="setAmount(50000)" class="p-4 border-2 border-gray-300 hover:border-purple-500 rounded-lg transition">
                            <p class="text-lg font-semibold">50K</p>
                            <p class="text-xs text-gray-500">IDR 50.000</p>
                        </button>
                        <button onclick="setAmount(100000)" class="p-4 border-2 border-gray-300 hover:border-purple-500 rounded-lg transition">
                            <p class="text-lg font-semibold">100K</p>
                            <p class="text-xs text-gray-500">IDR 100.000</p>
                        </button>
                        <button onclick="setAmount(250000)" class="p-4 border-2 border-gray-300 hover:border-purple-500 rounded-lg transition">
                            <p class="text-lg font-semibold">250K</p>
                            <p class="text-xs text-gray-500">IDR 250.000</p>
                        </button>
                        <button onclick="setAmount(500000)" class="p-4 border-2 border-gray-300 hover:border-purple-500 rounded-lg transition">
                            <p class="text-lg font-semibold">500K</p>
                            <p class="text-xs text-gray-500">IDR 500.000</p>
                        </button>
                        <button onclick="setAmount(1000000)" class="p-4 border-2 border-gray-300 hover:border-purple-500 rounded-lg transition">
                            <p class="text-lg font-semibold">1Jt</p>
                            <p class="text-xs text-gray-500">IDR 1.000.000</p>
                        </button>
                        <button onclick="setAmount(2000000)" class="p-4 border-2 border-gray-300 hover:border-purple-500 rounded-lg transition">
                            <p class="text-lg font-semibold">2Jt</p>
                            <p class="text-xs text-gray-500">IDR 2.000.000</p>
                        </button>
                    </div>

                    <!-- Custom Amount Input -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Atau Masukkan Nominal Lainnya</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">IDR</span>
                            <input type="number" id="topupAmount" min="10000" max="10000000" 
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="10.000 - 10.000.000"
                                oninput="calculateCrypto()">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimal IDR 10.000 - Maksimal IDR 10.000.000</p>
                    </div>

                    <!-- Conversion Preview -->
                    <div id="conversionPreview" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Jumlah IDR</span>
                            <span class="text-sm font-semibold" id="previewIDR">-</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Rate (1 MATIC)</span>
                            <span class="text-sm font-semibold" id="previewRate">Loading...</span>
                        </div>
                        <div class="border-t border-blue-300 my-2"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Anda Akan Menerima</span>
                            <span class="text-lg font-bold text-purple-600" id="previewMATIC">-</span>
                        </div>
                    </div>

                    <!-- Top-Up Button -->
                    <button onclick="processTopUp()" id="topupButton" disabled
                        class="w-full py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="buttonText">Lanjutkan Pembayaran</span>
                        <span id="buttonLoader" class="hidden">
                            <svg class="animate-spin inline h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Right Column - Info & History -->
            <div class="lg:col-span-1">
                <!-- How It Works -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Cara Kerja
                    </h3>
                    <ol class="space-y-3 text-sm text-gray-600">
                        <li class="flex">
                            <span class="flex-shrink-0 w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xs font-semibold mr-2">1</span>
                            <span>Pilih nominal IDR yang ingin ditop-up</span>
                        </li>
                        <li class="flex">
                            <span class="flex-shrink-0 w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xs font-semibold mr-2">2</span>
                            <span>Bayar menggunakan metode pembayaran Midtrans</span>
                        </li>
                        <li class="flex">
                            <span class="flex-shrink-0 w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xs font-semibold mr-2">3</span>
                            <span>IDR otomatis dikonversi ke MATIC</span>
                        </li>
                        <li class="flex">
                            <span class="flex-shrink-0 w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xs font-semibold mr-2">4</span>
                            <span>MATIC ditransfer ke wallet Polygon Anda</span>
                        </li>
                    </ol>
                </div>

                <!-- Payment Methods -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Metode Pembayaran</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2 border border-gray-200 rounded text-center">
                            <p class="text-xs text-gray-600">GoPay</p>
                        </div>
                        <div class="p-2 border border-gray-200 rounded text-center">
                            <p class="text-xs text-gray-600">QRIS</p>
                        </div>
                        <div class="p-2 border border-gray-200 rounded text-center">
                            <p class="text-xs text-gray-600">BCA VA</p>
                        </div>
                        <div class="p-2 border border-gray-200 rounded text-center">
                            <p class="text-xs text-gray-600">Mandiri</p>
                        </div>
                        <div class="p-2 border border-gray-200 rounded text-center">
                            <p class="text-xs text-gray-600">BNI</p>
                        </div>
                        <div class="p-2 border border-gray-200 rounded text-center">
                            <p class="text-xs text-gray-600">& More</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Top-ups -->
                @if($recentTopups->count() > 0)
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Top-Up Terakhir</h3>
                    <div class="space-y-3">
                        @foreach($recentTopups as $topup)
                        <div class="border-l-4 @if($topup->status === 'completed') border-green-500 @elseif($topup->status === 'pending') border-yellow-500 @else border-red-500 @endif pl-3 py-2">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-sm font-medium">IDR {{ number_format($topup->idr_amount, 0, ',', '.') }}</span>
                                <span class="text-xs px-2 py-1 rounded @if($topup->status === 'completed') bg-green-100 text-green-700 @elseif($topup->status === 'pending') bg-yellow-100 text-yellow-700 @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($topup->status) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">{{ $topup->created_at->diffForHumans() }}</p>
                            <p class="text-xs text-gray-600">{{ number_format($topup->crypto_amount, 6) }} MATIC</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Midtrans Snap Script -->
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key', env('MIDTRANS_CLIENT_KEY')) }}"></script>

<script>
let currentRate = null;
let isLoadingRate = false;

// Get MATIC rate on page load
window.addEventListener('DOMContentLoaded', function() {
    fetchMaticRate();
});

function fetchMaticRate() {
    if (isLoadingRate) return;
    isLoadingRate = true;
    
    fetch('https://api.coingecko.com/api/v3/simple/price?ids=matic-network&vs_currencies=idr')
        .then(response => response.json())
        .then(data => {
            currentRate = data['matic-network'].idr;
            document.getElementById('previewRate').textContent = 'IDR ' + new Intl.NumberFormat('id-ID').format(currentRate);
            isLoadingRate = false;
        })
        .catch(error => {
            console.error('Error fetching rate:', error);
            currentRate = 10000; // Fallback
            document.getElementById('previewRate').textContent = 'IDR ' + new Intl.NumberFormat('id-ID').format(currentRate);
            isLoadingRate = false;
        });
}

function setAmount(amount) {
    document.getElementById('topupAmount').value = amount;
    calculateCrypto();
}

function calculateCrypto() {
    const amount = parseFloat(document.getElementById('topupAmount').value);
    const preview = document.getElementById('conversionPreview');
    const button = document.getElementById('topupButton');
    
    if (!amount || amount < 10000 || amount > 10000000) {
        preview.classList.add('hidden');
        button.disabled = true;
        return;
    }
    
    if (!currentRate) {
        fetchMaticRate();
        return;
    }
    
    const maticAmount = amount / currentRate;
    
    document.getElementById('previewIDR').textContent = 'IDR ' + new Intl.NumberFormat('id-ID').format(amount);
    document.getElementById('previewMATIC').textContent = maticAmount.toFixed(6) + ' MATIC';
    
    preview.classList.remove('hidden');
    button.disabled = false;
}

function processTopUp() {
    const amount = parseFloat(document.getElementById('topupAmount').value);
    
    if (!amount || amount < 10000 || amount > 10000000) {
        alert('Masukkan nominal antara IDR 10.000 - 10.000.000');
        return;
    }
    
    // Show loading
    const button = document.getElementById('topupButton');
    button.disabled = true;
    document.getElementById('buttonText').classList.add('hidden');
    document.getElementById('buttonLoader').classList.remove('hidden');
    
    // Create top-up transaction
    fetch('{{ route("wallet.topup.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ amount: amount })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.snap_token) {
            // Open Midtrans Snap
            snap.pay(data.snap_token, {
                onSuccess: function(result) {
                    window.location.href = '{{ route("wallet.topup.finish") }}?order_id=' + data.order_id;
                },
                onPending: function(result) {
                    window.location.href = '{{ route("wallet.topup.finish") }}?order_id=' + data.order_id;
                },
                onError: function(result) {
                    alert('Pembayaran gagal: ' + (result.status_message || 'Unknown error'));
                    resetButton();
                },
                onClose: function() {
                    resetButton();
                }
            });
        } else {
            alert('Gagal membuat transaksi: ' + (data.message || 'Unknown error'));
            resetButton();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
        resetButton();
    });
}

function resetButton() {
    const button = document.getElementById('topupButton');
    button.disabled = false;
    document.getElementById('buttonText').classList.remove('hidden');
    document.getElementById('buttonLoader').classList.add('hidden');
}

function syncBalance() {
    const button = document.getElementById('syncButton');
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    
    fetch('{{ route("wallet.balance.realtime") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal sync balance');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat sync');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
        });
}
</script>
@endsection

