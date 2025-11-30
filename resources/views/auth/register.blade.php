<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - ZK Payment</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="auth-body">
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
            
            const form = document.querySelector('.auth-form');
            form.addEventListener('submit', function(e) {
                addLog('Registration form submitted', 'warning');
                addLog('Validating user input...');
                addLog('Generating wallet address...');
                addLog('Creating ZK commitment for user...');
                addLog('Preparing blockchain integration...');
            });
        });
    </script>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-user-plus"></i> Daftar Akun Baru</h1>
                <p>Buat wallet digital Anda sekarang</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="auth-form">
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
</body>
</html>

