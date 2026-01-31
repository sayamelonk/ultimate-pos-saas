<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $permission  Permission slug yang dicek (contoh: 'users.view', 'products.create')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 1. Ambil user yang sedang login
        $user = $request->user();

        // 2. Jika tidak ada user, redirect ke login
        if (! $user) {
            return redirect()->route('login');
        }

        // 3. Super Admin memiliki semua permissions
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // 4. Cek apakah user memiliki permission yang diperlukan
        if (! $user->hasPermission($permission)) {
            // 5. Handle JSON request (API/AJAX)
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }

            // 6. Handle web request
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
