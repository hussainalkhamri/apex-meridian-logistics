<?php

namespace App\Http\Middleware;

use App\Support\AuthorizedRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware('permission:manage_users')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super admin bypasses all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $hasAnyPermission = false;

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasAnyPermission = true;
                break;
            }
        }

        if (! $hasAnyPermission) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'ليس لديك صلاحية لتنفيذ هذا الإجراء'], 403);
            }

            $fallbackUrl = AuthorizedRedirect::urlFor($user);

            if ($fallbackUrl === $request->fullUrl()) {
                return redirect()->route('home')->with('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة.');
            }

            return redirect()
                ->to($fallbackUrl)
                ->with('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة، تم تحويلك إلى صفحة مسموحة لحسابك.');
        }

        return $next($request);
    }
}
