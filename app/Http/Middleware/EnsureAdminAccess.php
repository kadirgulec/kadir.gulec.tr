<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards /admin: the user needs the admin.access permission and, when
 * auth.admin_strong_login is on (production), a strong login (two-factor
 * authentication or a passkey). Without the permission the panel does not
 * exist for them (404); with a weak login they are sent to the security
 * settings.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->can(Permission::AccessAdmin->value), 404);

        if (config('auth.admin_strong_login') && ! $user->hasStrongLogin($request->session())) {
            return redirect()->route('security.edit')->with(
                'status',
                'Admin paneli için passkey ile giriş yap ya da iki adımlı doğrulamayı aç.',
            );
        }

        return $next($request);
    }
}
