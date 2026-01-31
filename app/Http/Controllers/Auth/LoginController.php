<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. Validasi input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        // 2. Attempt login
        if (Auth::attempt($credentials, $remember)) {
            // Regenerate session ID untuk security
            $request->session()->regenerate();

            // 3. Ambil user yang sedang login
            $user = Auth::user();

            // 4. Cek apakah user aktif
            if (! $user->is_active) {
                // Logout user jika tidak aktif
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ]);
            }

            // 5. Update last_login_at
            $user->update(['last_login_at' => now()]);

            // 6. Redirect ke dashboard
            return redirect()->intended(route('admin.dashboard'));
        }

        // 7. Login gagal
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Proses logout
     */
    public function logout(Request $request): RedirectResponse
    {
        // 1. Logout user
        Auth::logout();

        // 2. Invalidate session
        $request->session()->invalidate();

        // 3. Regenerate CSRF token
        $request->session()->regenerateToken();

        // 4. Redirect ke halaman login
        return redirect()->route('login');
    }
}
