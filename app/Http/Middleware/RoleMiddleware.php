<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Dùng: ->middleware('role:ADMIN') hoặc ->middleware('role:ADMIN,AGENCY')
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Eager-load role nếu chưa load
        $user->loadMissing('role');

        $userRole = $user->role?->code ?? '';

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
