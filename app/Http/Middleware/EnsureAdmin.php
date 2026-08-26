<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route aux utilisateurs `type === 'admin'`. À appliquer à
 * toute route véritablement admin-only (ex. traitement des certifications) —
 * contrairement à OptionController::store/update/destroy qui, historiquement,
 * n'a jamais eu cette vérification (faille existante, non reproduite ici).
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->type !== 'admin') {
            abort(403, "Accès réservé aux administrateurs.");
        }

        return $next($request);
    }
}
