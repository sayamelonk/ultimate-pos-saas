<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    /**
     * Handle an incoming request.
     *
     * Memastikan user memiliki tenant dan set tenant scope ke seluruh request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil user yang sedang login
        $user = $request->user();

        // 2. Jika tidak ada user, redirect ke login
        if (! $user) {
            return redirect()->route('login');
        }

        // 3. Super Admin bypass tenant check
        // Super Admin bisa akses semua tenant
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // 4. Cek apakah user memiliki tenant
        if (! $user->tenant_id) {
            abort(403, 'You are not associated with any tenant.');
        }

        // 5. Merge current_tenant_id ke request
        // Ini bisa digunakan di controller untuk query filtering
        $request->merge(['current_tenant_id' => $user->tenant_id]);

        // 6. Share tenant ke semua views
        // Jadi bisa diakses di Blade: {{ $currentTenant->name }}
        view()->share('currentTenant', $user->tenant);

        return $next($request);
    }
}
