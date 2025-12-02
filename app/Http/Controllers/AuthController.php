<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Services\PolygonService;
use App\Services\ZKSNARKService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $zkEnabled = $request->has('zk_enabled') && $request->zk_enabled;
        
        // Prepare user data
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'zk_enabled' => $zkEnabled,
        ];

        // If ZK mode enabled, generate and store commitment
        if ($zkEnabled) {
            // Generate commitment server-side using same algorithm as client
            // This ensures consistency between register and login
            $commitment = $request->zk_commitment;
            $publicKey = $request->zk_public_key;
            
            // If not provided by client, generate server-side
            if (empty($commitment)) {
                $commitment = $this->generateCommitment($request->email, $request->password);
            }
            if (empty($publicKey)) {
                $publicKey = $this->generatePublicKey($commitment);
            }
            
            $userData['zk_login_commitment'] = $commitment;
            $userData['zk_public_key'] = $publicKey;
            
            Log::info('[AuthController] ZK registration', [
                'email' => $request->email,
                'commitment' => substr($commitment, 0, 16) . '...',
                'from_client' => !empty($request->zk_commitment),
            ]);
        }

        $user = User::create($userData);

        // Create wallet with proper blockchain address
        $wallet = $this->createWalletForUser($user);

        if (!$wallet) {
            Log::error('[AuthController] Failed to create wallet for user', ['user_id' => $user->id]);
        }

        $message = 'Akun berhasil dibuat! Silakan login untuk melanjutkan.';
        if ($zkEnabled) {
            $message .= ' (ZK-SNARK Authentication aktif)';
        }
        if ($wallet && $wallet->polygon_address) {
            $message .= ' Wallet blockchain Anda telah dibuat.';
        }

        Log::info('[AuthController] User registered successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'zk_enabled' => $zkEnabled,
            'has_wallet' => $wallet !== null,
        ]);

        // Redirect to login page
        return redirect()->route('login')->with('success', $message);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        
        // First check if user exists and get their ZK status
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()->withErrors([
                'email' => 'Kredensial yang diberikan tidak sesuai dengan catatan kami.',
            ]);
        }

        // Check if user has ZK enabled
        if ($user->zk_enabled) {
            Log::info('[AuthController] ZK login attempt', [
                'email' => $request->email,
                'has_zk_proof' => $request->has('zk_proof'),
                'zk_checkbox' => $request->has('zk_enabled'),
            ]);
            
            // If ZK enabled but no proof provided, show error
            if (!$request->has('zk_proof') || empty($request->zk_proof)) {
                return back()->withErrors([
                    'email' => 'Akun ini menggunakan ZK-SNARK Authentication. Silakan aktifkan opsi "Gunakan ZK-SNARK Login" dan coba lagi.',
                ])->withInput();
            }
            
            // Verify ZK proof BEFORE attempting login
            $zkService = app(ZKSNARKService::class);
            $storedCommitment = $user->zk_login_commitment;
            
            // Also generate expected commitment from credentials for verification
            $expectedCommitment = $this->generateCommitment($request->email, $request->password);
            
            Log::info('[AuthController] ZK verification', [
                'stored_commitment' => substr($storedCommitment ?? '', 0, 16) . '...',
                'expected_commitment' => substr($expectedCommitment, 0, 16) . '...',
            ]);
            
            $verified = $zkService->verifyLoginProof(
                $request->zk_proof, 
                $storedCommitment,
                $expectedCommitment
            );
            
            if (!$verified) {
                Log::warning('[AuthController] ZK proof verification failed', [
                    'email' => $request->email,
                ]);
                return back()->withErrors([
                    'email' => 'Verifikasi ZK Proof gagal. Password mungkin salah atau proof tidak valid.',
                ])->withInput();
            }
            
            Log::info('[AuthController] ZK proof verified successfully', [
                'email' => $request->email,
            ]);
        }

        // Now attempt standard authentication
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Sync wallet balance on login
            $this->syncWalletOnLogin(Auth::user());
            
            Log::info('[AuthController] Login successful', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'zk_enabled' => Auth::user()->zk_enabled,
            ]);

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak sesuai dengan catatan kami.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Generate deterministic commitment from email + password
     * MUST match the client-side algorithm in zk-snark.js
     */
    private function generateCommitment(string $email, string $password): string
    {
        // Create secret from credentials (same as client)
        $secret = $this->deterministicHash(strtolower($email) . ':' . $password);
        
        // Create deterministic salt from email (same as client)
        $salt = $this->deterministicHash('zk_salt_' . strtolower($email));
        
        // Create commitment
        return $this->createCommitment($secret, $salt);
    }

    /**
     * Create Pedersen-like commitment: H(secret || salt)
     */
    private function createCommitment(string $secret, string $salt): string
    {
        $input = $secret . '||' . $salt;
        return $this->deterministicHash($input);
    }

    /**
     * Deterministic hash function matching client-side implementation
     */
    private function deterministicHash(string $str): string
    {
        $h1 = 0xdeadbeef;
        $h2 = 0x41c6ce57;
        
        for ($i = 0; $i < strlen($str); $i++) {
            $ch = ord($str[$i]);
            $h1 = $this->imul($h1 ^ $ch, 2654435761);
            $h2 = $this->imul($h2 ^ $ch, 1597334677);
        }
        
        $h1 = $this->imul($h1 ^ ($h1 >> 16), 2246822507) ^ $this->imul($h2 ^ ($h2 >> 13), 3266489909);
        $h2 = $this->imul($h2 ^ ($h2 >> 16), 2246822507) ^ $this->imul($h1 ^ ($h1 >> 13), 3266489909);
        
        $hash = sprintf('%08x%08x', $h2 & 0xffffffff, $h1 & 0xffffffff);
        
        // Extend to 64 characters
        $result = $hash;
        while (strlen($result) < 64) {
            $result .= $this->deterministicHash($result . $str);
        }
        
        return substr($result, 0, 64);
    }

    /**
     * 32-bit integer multiplication (matching JavaScript Math.imul)
     */
    private function imul(int $a, int $b): int
    {
        $a = $a & 0xffffffff;
        $b = $b & 0xffffffff;
        
        $ah = ($a >> 16) & 0xffff;
        $al = $a & 0xffff;
        $bh = ($b >> 16) & 0xffff;
        $bl = $b & 0xffff;
        
        $result = (($al * $bl) + ((($ah * $bl + $al * $bh) << 16) >>> 0)) | 0;
        
        return $result;
    }

    /**
     * Generate public key from commitment
     */
    private function generatePublicKey(string $commitment): string
    {
        return $this->deterministicHash('zk_pk_' . $commitment);
    }

    /**
     * Create wallet for new user with proper blockchain address
     */
    private function createWalletForUser(User $user): ?Wallet
    {
        $existingWallet = Wallet::where('user_id', $user->id)->first();
        if ($existingWallet) {
            return $existingWallet;
        }
        
        try {
            $polygonService = app(PolygonService::class);
            $blockchainWallet = $polygonService->createBlockchainWallet();
            
            if ($blockchainWallet['success']) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'wallet_address' => $this->generateInternalWalletId(),
                    'polygon_address' => $blockchainWallet['address'],
                    'public_key' => $blockchainWallet['public_key'],
                    'encrypted_private_key' => encrypt($blockchainWallet['private_key']),
                    'balance' => 0,
                    'is_active' => true,
                ]);
                
                Log::info('[AuthController] Wallet created', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'polygon_address' => $wallet->polygon_address,
                ]);
                
                return $wallet;
            }
            
            Log::error('[AuthController] Failed to create blockchain wallet', [
                'user_id' => $user->id,
                'error' => $blockchainWallet['error'] ?? 'Unknown error',
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('[AuthController] Wallet creation exception: ' . $e->getMessage());
            return null;
        }
    }

    private function generateInternalWalletId(): string
    {
        return 'ZKWALLET' . strtoupper(bin2hex(random_bytes(16)));
    }

    private function syncWalletOnLogin(User $user): void
    {
        try {
            $wallet = $user->wallet;
            
            if ($wallet && $wallet->polygon_address && $wallet->needsSync()) {
                $polygonService = app(PolygonService::class);
                $polygonService->syncWalletBalance($wallet->polygon_address);
                
                Log::info('[AuthController] Wallet balance synced on login', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[AuthController] Balance sync on login failed: ' . $e->getMessage());
        }
    }
}
