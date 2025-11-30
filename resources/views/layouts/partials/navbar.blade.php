@auth
<nav class="navbar">
    <div class="navbar-brand">
        <h2><i class="fas fa-shield-alt"></i> ZK Payment</h2>
    </div>
    
    <div class="navbar-menu">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('wallet.index') }}" class="nav-link {{ request()->routeIs('wallet.*') ? 'active' : '' }}">
            <i class="fas fa-wallet"></i>
            <span>Wallet</span>
        </a>
        <a href="{{ route('payment.form') }}" class="nav-link {{ request()->routeIs('payment.form') ? 'active' : '' }}">
            <i class="fas fa-paper-plane"></i>
            <span>Pembayaran</span>
        </a>
        <a href="{{ route('payment.history') }}" class="nav-link {{ request()->routeIs('payment.history') ? 'active' : '' }}">
            <i class="fas fa-history"></i>
            <span>Riwayat</span>
        </a>
    </div>
    
    <div class="navbar-user">
        <!-- User Profile Dropdown -->
        <div class="profile-dropdown">
            <button class="profile-trigger" onclick="toggleProfileDropdown()">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-info">
                    <span class="profile-name">{{ Auth::user()->name }}</span>
                    <span class="profile-email">{{ Auth::user()->email }}</span>
                </div>
                <i class="fas fa-chevron-down profile-arrow"></i>
            </button>
            
            <div class="profile-menu" id="profileDropdown">
                <div class="profile-menu-header">
                    <div class="profile-menu-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-menu-info">
                        <strong>{{ Auth::user()->name }}</strong>
                        <span>{{ Auth::user()->email }}</span>
                    </div>
                </div>
                
                <div class="profile-menu-divider"></div>
                
                <a href="{{ route('dashboard') }}" class="profile-menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="{{ route('wallet.index') }}" class="profile-menu-item">
                    <i class="fas fa-wallet"></i>
                    <span>My Wallet</span>
                </a>
                
                <a href="{{ route('payment.history') }}" class="profile-menu-item">
                    <i class="fas fa-history"></i>
                    <span>Riwayat Transaksi</span>
                </a>
                
                <div class="profile-menu-divider"></div>
                
                <a href="#" class="profile-menu-item" onclick="event.preventDefault(); document.getElementById('profile-settings-modal').style.display='flex';">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
                
                <a href="#" class="profile-menu-item" onclick="event.preventDefault(); document.getElementById('about-modal').style.display='flex';">
                    <i class="fas fa-info-circle"></i>
                    <span>Tentang ZK Payment</span>
                </a>
                
                <div class="profile-menu-divider"></div>
                
                <form action="{{ route('logout') }}" method="POST" class="profile-menu-form">
                    @csrf
                    <button type="submit" class="profile-menu-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<!-- Profile Settings Modal -->
<div id="profile-settings-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-cog"></i> Pengaturan Profil</h2>
            <button class="modal-close" onclick="document.getElementById('profile-settings-modal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <div class="profile-settings-info">
                <div class="settings-avatar-large">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="settings-info">
                    <h3>{{ Auth::user()->name }}</h3>
                    <p>{{ Auth::user()->email }}</p>
                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Akun Terverifikasi</span>
                </div>
            </div>
            
            <div class="settings-section">
                <h4><i class="fas fa-shield-alt"></i> Keamanan Akun</h4>
                <div class="settings-item">
                    <div class="settings-item-info">
                        <strong>Password</strong>
                        <span>Terakhir diubah: {{ Auth::user()->updated_at->diffForHumans() }}</span>
                    </div>
                    <button class="btn btn-sm btn-outline" disabled>
                        <i class="fas fa-key"></i> Ubah Password
                    </button>
                </div>
                
                <div class="settings-item">
                    <div class="settings-item-info">
                        <strong>Two-Factor Authentication</strong>
                        <span>Tingkatkan keamanan akun Anda</span>
                    </div>
                    <button class="btn btn-sm btn-outline" disabled>
                        <i class="fas fa-lock"></i> Aktifkan 2FA
                    </button>
                </div>
            </div>
            
            <div class="settings-section">
                <h4><i class="fas fa-bell"></i> Notifikasi</h4>
                <div class="settings-item">
                    <div class="settings-item-info">
                        <strong>Email Notifikasi Transaksi</strong>
                        <span>Terima notifikasi setiap transaksi</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked disabled>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="settings-note">
                <i class="fas fa-info-circle"></i>
                <p>Fitur pengaturan lanjutan akan segera tersedia</p>
            </div>
        </div>
    </div>
</div>

<!-- About Modal -->
<div id="about-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-info-circle"></i> Tentang ZK Payment</h2>
            <button class="modal-close" onclick="document.getElementById('about-modal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <div class="about-content">
                <div class="about-logo">
                    <i class="fas fa-shield-alt" style="font-size: 4rem; color: var(--primary-purple);"></i>
                </div>
                
                <h3>ZK Payment</h3>
                <p class="about-tagline">Sistem Pembayaran Digital dengan Zero-Knowledge Proof</p>
                
                <div class="about-version">
                    <span class="badge badge-primary">Version 1.0.0</span>
                </div>
                
                <div class="about-features">
                    <h4>Fitur Utama:</h4>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Zero-Knowledge Proof (zk-SNARK) untuk privasi maksimal</li>
                        <li><i class="fas fa-check-circle"></i> Integrasi dengan Polygon Blockchain</li>
                        <li><i class="fas fa-check-circle"></i> QR Code untuk transaksi P2P yang mudah</li>
                        <li><i class="fas fa-check-circle"></i> Wallet digital yang aman dan terenkripsi</li>
                        <li><i class="fas fa-check-circle"></i> Riwayat transaksi lengkap dan terverifikasi</li>
                    </ul>
                </div>
                
                <div class="about-tech">
                    <h4>Teknologi:</h4>
                    <div class="tech-badges">
                        <span class="tech-badge"><i class="fab fa-laravel"></i> Laravel</span>
                        <span class="tech-badge"><i class="fas fa-shield-alt"></i> zk-SNARK</span>
                        <span class="tech-badge"><i class="fas fa-link"></i> Polygon</span>
                        <span class="tech-badge"><i class="fas fa-database"></i> MySQL</span>
                    </div>
                </div>
                
                <div class="about-footer">
                    <p>&copy; 2024 ZK Payment. All rights reserved.</p>
                    <p>Developed with <i class="fas fa-heart" style="color: #ef4444;"></i> for secure digital payments</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const trigger = document.querySelector('.profile-trigger');
    
    if (dropdown && trigger) {
        if (!trigger.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('active');
        }
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const profileModal = document.getElementById('profile-settings-modal');
    const aboutModal = document.getElementById('about-modal');
    
    if (event.target == profileModal) {
        profileModal.style.display = 'none';
    }
    if (event.target == aboutModal) {
        aboutModal.style.display = 'none';
    }
}
</script>
@endauth

