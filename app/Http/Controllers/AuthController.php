<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'zk_enabled' => false,
        ]);

        // Buat wallet otomatis untuk user baru
        $wallet = $this->createWalletForUser($user);

        // Auto-distribute test MATIC untuk testing
        if ($wallet && $wallet->polygon_address) {
            $faucetService = app(\App\Services\FaucetService::class);
            $result = $faucetService->distributeTestMatic($wallet->polygon_address);
            
            if ($result['success']) {
                $message = 'Akun berhasil dibuat! Test MATIC (' . number_format($result['amount'], 4) . ' MATIC) telah dikirim ke wallet Anda.';
                if ($result['simulation']) {
                    $message .= ' [Mode Simulasi - Master wallet belum dikonfigurasi]';
                }
                Auth::login($user);
                return redirect()->route('dashboard')->with('success', $message);
            } else {
                // Jika gagal distribute (misal balance master wallet kurang), tetap login tapi kasih peringatan
                Auth::login($user);
                return redirect()->route('dashboard')
                    ->with('warning', 'Akun berhasil dibuat, namun gagal mendistribusikan test MATIC: ' . ($result['error'] ?? 'Unknown error'));
            }
        }

        Auth::login($user);
        return redirect()->route('dashboard')->with('success', 'Akun berhasil dibuat!');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Jika user menggunakan zk-SNARK, verifikasi proof
            if (Auth::user()->zk_enabled && $request->has('zk_proof')) {
                $zkService = app(\App\Services\ZKSNARKService::class);
                $user = Auth::user();

                // Get user commitment (this should be stored securely)
                $commitment = $user->zk_commitment ?? hash('sha256', $user->email . $user->id);

                $verified = $zkService->verifyLoginProof($request->zk_proof, $commitment);
                if (!$verified) {
                    Auth::logout();
                    return back()->withErrors(['email' => 'Verifikasi ZK Proof gagal. Silakan coba lagi.']);
                }

                \Log::info('[AuthController] ZK login proof verified successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak sesuai dengan catatan kami.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function createWalletForUser(User $user)
    {
        // Cek apakah user sudah memiliki wallet
        if (Wallet::where('user_id', $user->id)->exists()) {
            return Wallet::where('user_id', $user->id)->first();
        }
        
        // Generate blockchain wallet using PolygonService
        $polygonService = app(\App\Services\PolygonService::class);
        $blockchainWallet = $polygonService->createBlockchainWallet();
        
        if ($blockchainWallet['success']) {
            // Create wallet with blockchain address
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'wallet_address' => 'ZKWALLET' . strtoupper(bin2hex(random_bytes(20))),
                'public_key' => $blockchainWallet['public_key'],
                'encrypted_private_key' => encrypt($blockchainWallet['private_key']),
                'polygon_address' => $blockchainWallet['address'],
                'balance' => 0, // Balance awal 0, akan di-top up via faucet
            ]);
            
            return $wallet;
        }
        
        // Fallback: create wallet without blockchain address
        $walletAddress = 'ZKWALLET' . strtoupper(bin2hex(random_bytes(20)));
        $publicKey = bin2hex(random_bytes(32));
        $privateKey = bin2hex(random_bytes(32));
        
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_address' => $walletAddress,
            'public_key' => $publicKey,
            'encrypted_private_key' => encrypt($privateKey),
            'balance' => 0,
        ]);
        
        return $wallet;
    }

    /**
     * Legacy method - now handled by ZKSNARKService
     * @deprecated Use ZKSNARKService::verifyLoginProof instead
     */
    private function verifyZkLoginProof($proof)
    {
        // This method is deprecated - using ZKSNARKService instead
        $zkService = app(\App\Services\ZKSNARKService::class);
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $commitment = $user->zk_commitment ?? hash('sha256', $user->email . $user->id);
        return $zkService->verifyLoginProof($proof, $commitment);
    }
}

