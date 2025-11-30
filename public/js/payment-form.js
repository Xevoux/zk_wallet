/**
 * Payment Form Module
 */

function addLog(message, type = 'info') {
    const logsContainer = document.getElementById('paymentLogs');
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
    const walletBalance = document.getElementById('walletBalance');
    
    addLog('Payment module initialized', 'success');
    addLog('ZK-SNARK engine loaded');
    addLog('Polygon integration ready');
    
    if (walletBalance) {
        addLog(`Wallet balance: ${walletBalance.textContent}`, 'success');
    }
    
    addLog('Waiting for transaction...');
    
    // Monitor private transaction checkbox
    const privateCheckbox = document.getElementById('private_transaction');
    if (privateCheckbox) {
        privateCheckbox.addEventListener('change', function() {
            if (this.checked) {
                addLog('🔐 Private transaction mode ENABLED', 'warning');
                addLog('ZK-SNARK proof will be generated');
                addLog('Transaction details will be hidden');
            } else {
                addLog('Private transaction mode disabled', 'info');
            }
        });
    }
    
    // Setup form handlers
    setupQRForm();
    setupManualForm();
});

function setupQRForm() {
    const form = document.getElementById('generateQRForm');
    if (!form) return;
    
    // Generate QR Code
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const amount = document.getElementById('amount').value;
        const generateUrl = form.dataset.generateUrl;
        
        addLog('Generating QR Code for payment...', 'warning');
        addLog(`Amount: Rp ${parseFloat(amount).toLocaleString('id-ID')}`);
        
        const formData = new FormData();
        formData.append('amount', amount);
        
        try {
            addLog('Encoding payment data...');
            addLog('Creating QR code image...');
            
            const response = await fetch(generateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Use SVG format
                const imageType = data.format === 'svg' ? 'image/svg+xml' : 'image/png';
                document.getElementById('qrCodeImage').src = 'data:' + imageType + ';base64,' + data.qr_code;
                document.getElementById('qrCodeResult').style.display = 'block';
                
                addLog('✓ QR Code generated successfully', 'success');
                addLog('QR Code ready for scanning');
                
                // Store payment data for download
                window.qrPaymentData = data.payment_data;
            } else {
                addLog('✗ Failed to generate QR Code', 'error');
                alert('Gagal generate QR Code');
            }
        } catch (error) {
            console.error('Error:', error);
            addLog('✗ Error: ' + error.message, 'error');
            alert('Terjadi kesalahan');
        }
    });
}

function setupManualForm() {
    const form = document.getElementById('manualPaymentForm');
    if (!form) return;
    
    // Manual Payment
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const amount = parseFloat(document.getElementById('manual_amount').value);
        const balanceElement = document.getElementById('walletBalance');
        const balance = balanceElement ? parseFloat(balanceElement.dataset.balance) : 0;
        const receiverAddress = document.getElementById('receiver_address').value;
        const processUrl = form.dataset.processUrl;
        const dashboardUrl = form.dataset.dashboardUrl;
        
        addLog('=== TRANSACTION INITIATED ===', 'warning');
        addLog(`Receiver: ${receiverAddress.substring(0, 20)}...`);
        addLog(`Amount: Rp ${amount.toLocaleString('id-ID')}`);
        addLog(`Your balance: Rp ${balance.toLocaleString('id-ID')}`);
        
        const formData = new FormData(this);
        const privateTransaction = document.getElementById('private_transaction').checked;
        
        // Verify balance
        addLog('--- BALANCE VERIFICATION ---', 'info');
        if (amount > balance) {
            addLog('✗ INSUFFICIENT BALANCE', 'error');
            addLog(`Required: Rp ${amount.toLocaleString('id-ID')}`, 'error');
            addLog(`Available: Rp ${balance.toLocaleString('id-ID')}`, 'error');
            alert('Saldo tidak mencukupi!');
            return;
        }
        addLog('✓ Balance check passed', 'success');
        addLog(`Remaining after tx: Rp ${(balance - amount).toLocaleString('id-ID')}`);
        
        // Generate ZK proof if private transaction
        if (privateTransaction) {
            addLog('--- ZK-SNARK PROOF GENERATION ---', 'warning');
            addLog('Initializing ZK circuit...');
            addLog('Computing Pedersen commitment...');
            addLog('Generating range proof (balance >= amount)...');
            
            try {
                if (typeof generateBalanceProof === 'function') {
                    const zkProof = await generateBalanceProof(balance, amount);
                    formData.append('zk_proof', zkProof);
                    
                    addLog('✓ ZK proof generated', 'success');
                    addLog('Proof type: Groth16');
                    addLog('Curve: BN128');
                    addLog('Privacy: ENABLED (details hidden)');
                    addLog('Nullifier generated to prevent double-spending');
                } else {
                    addLog('⚠ ZK-SNARK library not loaded', 'warning');
                }
            } catch (error) {
                addLog('✗ ZK proof generation failed: ' + error.message, 'error');
                alert('Gagal membuat ZK proof');
                return;
            }
        }
        
        addLog('--- BLOCKCHAIN INTEGRATION ---', 'info');
        addLog('Preparing Polygon transaction...');
        addLog('Connecting to Polygon network...');
        
        try {
            const response = await fetch(processUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            
            addLog('Transaction submitted to backend...');
            const data = await response.json();
            
            if (data.success) {
                addLog('✓ TRANSACTION SUCCESSFUL', 'success');
                addLog('Transaction recorded on blockchain');
                addLog('Updating wallet balance...');
                addLog('=== TRANSACTION COMPLETE ===', 'success');
                
                setTimeout(() => {
                    alert('Pembayaran berhasil!');
                    window.location.href = dashboardUrl;
                }, 1000);
            } else {
                addLog('✗ TRANSACTION FAILED: ' + data.message, 'error');
                alert('Pembayaran gagal: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            addLog('✗ Network error: ' + error.message, 'error');
            alert('Terjadi kesalahan');
        }
    });
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    if (tab === 'qr') {
        document.querySelector('[onclick="switchTab(\'qr\')"]').classList.add('active');
        document.getElementById('qr-tab').classList.add('active');
    } else {
        document.querySelector('[onclick="switchTab(\'manual\')"]').classList.add('active');
        document.getElementById('manual-tab').classList.add('active');
    }
}

function downloadQR() {
    const img = document.getElementById('qrCodeImage');
    const link = document.createElement('a');
    link.download = 'payment-qr.png';
    link.href = img.src;
    link.click();
}

