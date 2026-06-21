<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * RoleMiddleware
 * ----------------
 * Enforces Role-Based Access Control (RBAC) as described in the
 * project documentation: "Each role has well-defined read and write
 * permissions, consistent with the trust model of the system."
 *
 * Usage in routes:
 *   Route::middleware(['auth:api', 'role:doctor'])->group(...)
 *   Route::middleware(['auth:api', 'role:facility_admin,moh_admin'])->group(...)
 *
 * Any access attempt by a user whose role is not in the allowed
 * list is logged to AuditLog with outcome = 'denied' (NFR-07).
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!in_array($user->role, $roles)) {
            AuditLog::record(
                $user->id,
                'ACCESS_DENIED',
                'denied',
                null,
                null,
                [
                    'route'         => $request->path(),
                    'required_role' => $roles,
                    'actual_role'   => $user->role,
                ]
            );

            return response()->json([
                'error' => 'Forbidden — your role (' . $user->role . ') does not have access to this resource.'
            ], 403);
        }

        return $next($request);
    }
}
