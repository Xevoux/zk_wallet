<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - ZK Payment</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-user-plus"></i> Daftar Akun Baru</h1>
                <p>Buat wallet digital Anda sekarang</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="auth-form" id="registerForm">
                @csrf

                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="zkEnabled" name="zk_enabled" value="1">
                        <span>Aktifkan ZK-SNARK Authentication (Privat)</span>
                    </label>
                    <small class="form-hint">Mode privasi: Login dengan Zero-Knowledge Proof tanpa mengirim password.</small>
                </div>

                <input type="hidden" name="zk_commitment" id="zkCommitment">
                <input type="hidden" name="zk_public_key" id="zkPublicKey">

                <button type="submit" class="btn btn-primary btn-block">
                    Daftar
                </button>

                <div class="auth-footer">
                    <p>Sudah punya akun? <a href="{{ route('login') }}">Login di sini</a></p>
                </div>
            </form>
        </div>

        <div class="process-logs">
            <h3><i class="fas fa-terminal"></i> Process Logs</h3>
            <div id="registerLogs" class="logs-container">
                <div class="log-entry log-info">
                    <span class="log-time">[00:00:00]</span>
                    <span class="log-message">System ready - Waiting for registration...</span>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/zk-snark.js') }}"></script>
    <script>
        function addLog(message, type = 'info') {
            const logsContainer = document.getElementById('registerLogs');
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
            addLog('Registration page loaded', 'success');
            addLog('Initializing ZK-SNARK module...');
            
            setTimeout(() => {
                addLog('ZK-SNARK module ready', 'success');
                addLog('Wallet generation system online', 'success');
                addLog('Ready to register new user');
            }, 500);
        });

        // Monitor ZK checkbox changes
        document.getElementById('zkEnabled').addEventListener('change', function() {
            if (this.checked) {
                addLog('ZK-SNARK mode enabled', 'success');
                addLog('Privacy mode: ON');
                addLog('Your login will use Zero-Knowledge Proof');
            } else {
                addLog('ZK-SNARK mode disabled', 'info');
                addLog('Standard authentication mode');
            }
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const zkEnabled = document.getElementById('zkEnabled').checked;
            
            addLog('Registration form submitted', 'warning');
            addLog('Validating user input...');
            
            if (zkEnabled) {
                e.preventDefault();
                addLog('ZK-SNARK registration mode activated', 'warning');
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const passwordConfirm = document.getElementById('password_confirmation').value;
                
                // Validate passwords match
                if (password !== passwordConfirm) {
                    addLog('Error: Passwords do not match', 'error');
                    alert('Password dan konfirmasi password tidak sama!');
                    return;
                }
                
                addLog('Generating ZK commitment for registration...');
                addLog('Computing deterministic commitment...');
                
                // Generate ZK commitment using the function from zk-snark.js
                if (typeof generateZKCommitment === 'function') {
                    const zkData = generateZKCommitment(email, password);
                    document.getElementById('zkCommitment').value = zkData.commitment;
                    document.getElementById('zkPublicKey').value = zkData.publicKey;
                    
                    addLog('ZK commitment generated: ' + zkData.commitment.substring(0, 16) + '...', 'success');
                    addLog('Public key generated: ' + zkData.publicKey.substring(0, 16) + '...', 'success');
                } else {
                    addLog('Error: ZK module not loaded', 'error');
                    alert('ZK-SNARK module gagal dimuat. Silakan refresh halaman.');
                    return;
                }
                
                addLog('Generating blockchain wallet address...');
                addLog('Creating user account with ZK authentication...');
                
                // Submit form after processing
                setTimeout(() => {
                    addLog('Submitting registration to server...', 'info');
                    this.submit();
                }, 500);
            } else {
                addLog('Standard registration mode', 'info');
                addLog('Generating wallet address...');
                addLog('Submitting registration...');
            }
        });
    </script>
</body>
</html>
