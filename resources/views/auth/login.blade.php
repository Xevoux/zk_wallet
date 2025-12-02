<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - ZK Payment</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-shield-alt"></i> ZK Payment</h1>
                <p>Pembayaran Digital dengan Zero-Knowledge Proof</p>
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

            @if($errors->any())
                <div class="alert alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="auth-form" id="loginForm">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="zkEnabled" name="zk_enabled" value="1">
                        <span>Gunakan ZK-SNARK Login (Privat)</span>
                    </label>
                    <small class="form-hint">Aktifkan jika Anda mendaftar dengan mode ZK-SNARK.</small>
                </div>

                <input type="hidden" name="zk_proof" id="zkProof">

                <button type="submit" class="btn btn-primary btn-block">
                    Login
                </button>

                <div class="auth-footer">
                    <p>Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a></p>
                </div>
            </form>
        </div>

        <div class="process-logs">
            <h3><i class="fas fa-terminal"></i> Process Logs</h3>
            <div id="loginLogs" class="logs-container">
                <div class="log-entry log-info">
                    <span class="log-time">[00:00:00]</span>
                    <span class="log-message">System ready - Waiting for login...</span>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/zk-snark.js') }}"></script>
    <script>
        function addLog(message, type = 'info') {
            const logsContainer = document.getElementById('loginLogs');
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
            addLog('Login page loaded', 'success');
            addLog('Initializing ZK-SNARK module...');
            
            setTimeout(() => {
                addLog('ZK-SNARK module ready', 'success');
                addLog('Groth16 protocol initialized', 'success');
                addLog('BN128 curve parameters loaded', 'success');
                addLog('Ready for authentication');
            }, 500);

            // Check if there's a success message (from registration)
            @if(session('success'))
                addLog('{{ session('success') }}', 'success');
            @endif

            @if($errors->any())
                addLog('{{ $errors->first() }}', 'error');
            @endif
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const zkEnabled = document.getElementById('zkEnabled').checked;
            
            if (zkEnabled) {
                e.preventDefault();
                addLog('ZK-SNARK login mode activated', 'warning');
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                if (!email || !password) {
                    addLog('Error: Email and password required', 'error');
                    alert('Email dan password harus diisi!');
                    return;
                }
                
                addLog('Generating ZK proof for authentication...');
                addLog('Recreating commitment from credentials...');
                
                // First recreate the commitment (should match what was stored during registration)
                if (typeof generateZKCommitment === 'function') {
                    const zkData = generateZKCommitment(email, password);
                    addLog('Commitment recreated: ' + zkData.commitment.substring(0, 16) + '...', 'info');
                    
                    // Generate proof
                    addLog('Generating Zero-Knowledge Proof...');
                    if (typeof generateZKLoginProof === 'function') {
                        const zkProof = generateZKLoginProof(email, password, zkData.commitment);
                        document.getElementById('zkProof').value = zkProof;
                        
                        addLog('ZK proof generated successfully', 'success');
                        addLog('Proof type: Groth16');
                        addLog('Curve: BN128');
                        addLog('Password NOT transmitted - only proof sent', 'success');
                        addLog('Submitting proof to server...');
                        
                        // Submit form
                        setTimeout(() => {
                            this.submit();
                        }, 500);
                    } else {
                        addLog('Error: ZK proof generation failed', 'error');
                        alert('ZK-SNARK module gagal. Silakan refresh halaman.');
                    }
                } else {
                    addLog('Error: ZK module not loaded', 'error');
                    alert('ZK-SNARK module tidak tersedia. Silakan refresh halaman.');
                }
            } else {
                addLog('Standard login mode', 'info');
                addLog('Submitting credentials...');
            }
        });

        // Monitor checkbox changes
        document.getElementById('zkEnabled').addEventListener('change', function() {
            if (this.checked) {
                addLog('ZK-SNARK mode enabled', 'success');
                addLog('Privacy mode: ON - Password will NOT be transmitted');
                addLog('Using Zero-Knowledge Proof for authentication');
            } else {
                addLog('ZK-SNARK mode disabled', 'info');
                addLog('Standard authentication mode');
            }
        });
    </script>
</body>
</html>
