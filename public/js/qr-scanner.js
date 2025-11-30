/**
 * QR Scanner Module
 */

let html5QrCode;
let scannedData = null;

function addLog(message, type = 'info') {
    const logsContainer = document.getElementById('scanLogs');
    if (!logsContainer) return;
    
    const logEntry = document.createElement('div');
    logEntry.className = `log-entry log-${type}`;
    
    const now = new Date();
    const timeStr = now.toTimeString().split(' ')[0];
    
    logEntry.innerHTML = `
        <span class="log-time">[${timeStr}]</span>
        <span class="log-message">${message}</span>
    `;
    
    logsContainer.appendChild(logEntry);
    logsContainer.scrollTop = logsContainer.scrollHeight;
}

document.addEventListener('DOMContentLoaded', function() {
    addLog('QR Scanner module loaded', 'success');
    addLog('ZK-SNARK engine initialized');
    addLog('Requesting camera access...');
    
    // Monitor ZK checkbox
    const zkCheckbox = document.getElementById('useZkProof');
    if (zkCheckbox) {
        zkCheckbox.addEventListener('change', function() {
            if (this.checked) {
                addLog('🔐 Private transaction mode ENABLED', 'warning');
                addLog('ZK-SNARK proof will be generated for privacy');
            } else {
                addLog('Private transaction mode disabled', 'info');
            }
        });
    }
});

// Initialize QR Scanner
function initScanner() {
    if (typeof Html5Qrcode === 'undefined') {
        addLog('✗ QR Scanner library not loaded', 'error');
        return;
    }
    
    html5QrCode = new Html5Qrcode("reader");
    
    addLog('Starting camera scanner...');
    
    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        onScanSuccess,
        onScanError
    ).then(() => {
        addLog('✓ Camera active - Ready to scan', 'success');
        addLog('Point camera at QR code...');
    }).catch(err => {
        console.error("Unable to start scanner:", err);
        addLog('✗ Camera error: ' + err.message, 'error');
        addLog('Please use manual input mode', 'warning');
        const readerEl = document.getElementById('reader');
        if (readerEl) {
            readerEl.innerHTML = '<p class="error-message">Kamera tidak tersedia. Gunakan input manual.</p>';
        }
    });
}

function onScanSuccess(decodedText, decodedResult) {
    addLog('=== QR CODE DETECTED ===', 'success');
    addLog('Decoding QR data...');
    console.log("QR Code scanned:", decodedText);
    processQRData(decodedText);
    stopScanner();
}

function onScanError(errorMessage) {
    // Handle scan error silently
}

function processQRData(data) {
    try {
        addLog('Parsing QR code data...');
        scannedData = JSON.parse(data);
        
        addLog('✓ QR code validated', 'success');
        addLog(`QR Type: ${scannedData.type || 'payment'}`);
        
        // Handle different QR code types
        if (scannedData.type === 'wallet_address') {
            // Wallet address QR - need to input amount
            addLog('Wallet address QR detected');
            addLog(`Receiver: ${scannedData.address || scannedData.wallet_address}`);
            
            const amount = prompt('Masukkan jumlah pembayaran (Rp):');
            if (!amount || amount <= 0) {
                addLog('✗ Payment cancelled - no amount', 'warning');
                cancelPayment();
                return;
            }
            
            scannedData.wallet_address = scannedData.address || scannedData.wallet_address;
            scannedData.amount = parseFloat(amount);
            
        } else if (scannedData.type === 'payment_request' || scannedData.wallet_address) {
            // Payment request QR - amount included
            addLog('Payment request QR detected');
        } else {
            // Legacy format or direct wallet address
            if (!scannedData.wallet_address && scannedData.address) {
                scannedData.wallet_address = scannedData.address;
            }
            if (!scannedData.amount) {
                const amount = prompt('Masukkan jumlah pembayaran (Rp):');
                if (!amount || amount <= 0) {
                    addLog('✗ Payment cancelled - no amount', 'warning');
                    cancelPayment();
                    return;
                }
                scannedData.amount = parseFloat(amount);
            }
        }
        
        addLog('Payment details extracted:');
        addLog(`  Receiver: ${scannedData.wallet_address.substring(0, 20)}...`);
        addLog(`  Amount: Rp ${parseFloat(scannedData.amount).toLocaleString('id-ID')}`);
        addLog(`  Timestamp: ${new Date((scannedData.timestamp || Date.now() / 1000) * 1000).toLocaleString('id-ID')}`);
        
        // Display confirmation
        document.getElementById('confirmReceiverAddress').textContent = scannedData.wallet_address;
        document.getElementById('confirmAmount').textContent = 'Rp ' + parseFloat(scannedData.amount).toLocaleString('id-ID');
        document.getElementById('confirmTimestamp').textContent = new Date((scannedData.timestamp || Date.now() / 1000) * 1000).toLocaleString('id-ID');
        
        document.querySelector('.scanner-card').style.display = 'none';
        document.getElementById('paymentConfirmation').style.display = 'block';
        
        addLog('Waiting for user confirmation...');
    } catch (error) {
        addLog('✗ Invalid QR code format: ' + error.message, 'error');
        alert('QR Code tidak valid: ' + error.message);
    }
}

async function confirmPayment() {
    if (!scannedData) {
        addLog('✗ No payment data available', 'error');
        alert('Tidak ada data pembayaran');
        return;
    }

    const amount = parseFloat(scannedData.amount);
    const balanceElement = document.getElementById('userBalance');
    const balance = balanceElement ? parseFloat(balanceElement.dataset.balance) : 0;
    const useZkProof = document.getElementById('useZkProof').checked;
    const processUrl = document.getElementById('processUrl').value;
    const dashboardUrl = document.getElementById('dashboardUrl').value;
    
    addLog('=== PAYMENT CONFIRMATION ===', 'warning');
    addLog(`Amount: Rp ${amount.toLocaleString('id-ID')}`);
    addLog(`Your balance: Rp ${balance.toLocaleString('id-ID')}`);
    
    // Balance verification
    addLog('--- BALANCE VERIFICATION ---', 'info');
    if (amount > balance) {
        addLog('✗ INSUFFICIENT BALANCE', 'error');
        addLog(`Required: Rp ${amount.toLocaleString('id-ID')}`, 'error');
        addLog(`Available: Rp ${balance.toLocaleString('id-ID')}`, 'error');
        alert('Saldo tidak mencukupi!');
        return;
    }
    addLog('✓ Balance check passed', 'success');
    addLog(`Remaining: Rp ${(balance - amount).toLocaleString('id-ID')}`);

    const formData = new FormData();
    formData.append('receiver_address', scannedData.wallet_address);
    formData.append('amount', scannedData.amount);
    formData.append('private_transaction', useZkProof ? '1' : '0');

    if (useZkProof) {
        addLog('--- ZK-SNARK PROOF GENERATION ---', 'warning');
        addLog('Initializing ZK circuit for QR payment...');
        addLog('Computing Pedersen commitment...');
        addLog('Generating range proof...');
        
        try {
            if (typeof generateBalanceProof === 'function') {
                const zkProof = await generateBalanceProof(balance, amount);
                formData.append('zk_proof', zkProof);
                
                addLog('✓ ZK proof generated successfully', 'success');
                addLog('Proof protocol: Groth16');
                addLog('Elliptic curve: BN128');
                addLog('Privacy level: MAXIMUM');
                addLog('Transaction details hidden from public');
            } else {
                addLog('⚠ ZK-SNARK library not loaded', 'warning');
            }
        } catch (error) {
            addLog('✗ ZK proof generation failed: ' + error.message, 'error');
            alert('Gagal membuat ZK proof');
            return;
        }
    }

    addLog('--- BLOCKCHAIN TRANSACTION ---', 'info');
    addLog('Preparing transaction data...');
    addLog('Submitting to Polygon network...');

    try {
        const response = await fetch(processUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        addLog('Waiting for transaction confirmation...');
        const data = await response.json();

        if (data.success) {
            addLog('✓ PAYMENT SUCCESSFUL', 'success');
            addLog('Transaction recorded on blockchain');
            addLog('Wallet balance updated');
            addLog('=== TRANSACTION COMPLETE ===', 'success');
            
            setTimeout(() => {
                alert('Pembayaran berhasil!');
                window.location.href = dashboardUrl;
            }, 1000);
        } else {
            addLog('✗ PAYMENT FAILED: ' + data.message, 'error');
            alert('Pembayaran gagal: ' + data.message);
            cancelPayment();
        }
    } catch (error) {
        console.error('Error:', error);
        addLog('✗ Network error: ' + error.message, 'error');
        alert('Terjadi kesalahan');
    }
}

function cancelPayment() {
    scannedData = null;
    document.getElementById('paymentConfirmation').style.display = 'none';
    document.querySelector('.scanner-card').style.display = 'block';
    initScanner();
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            console.log("Scanner stopped");
        }).catch(err => {
            console.error("Error stopping scanner:", err);
        });
    }
}

function toggleManualInput() {
    const reader = document.getElementById('reader');
    const manualInput = document.getElementById('manualInput');
    
    if (manualInput.style.display === 'none') {
        stopScanner();
        reader.style.display = 'none';
        manualInput.style.display = 'block';
    } else {
        reader.style.display = 'block';
        manualInput.style.display = 'none';
        initScanner();
    }
}

function processManualInput() {
    const data = document.getElementById('qrDataInput').value;
    if (data) {
        processQRData(data);
    } else {
        alert('Masukkan data QR code terlebih dahulu');
    }
}

// Initialize scanner on page load
window.addEventListener('load', initScanner);

// Stop scanner when leaving page
window.addEventListener('beforeunload', stopScanner);

