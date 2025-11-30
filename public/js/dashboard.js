/**
 * Dashboard Module
 */

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Alamat berhasil disalin!');
    }).catch(err => {
        console.error('Gagal menyalin:', err);
    });
}

// Auto refresh balance setiap 30 detik (optional, can be disabled)
const autoRefreshEnabled = false; // Set to true if you want auto-refresh

if (autoRefreshEnabled) {
    setInterval(() => {
        location.reload();
    }, 30000);
}

