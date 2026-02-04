<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\IntegrantesEmpresa;

class VerifyDduAccess
{
    /**
     * Handle an incoming request with proper DDU verification
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        try {
            // Get user from juntify database
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('email', $user->email)
                ->first();

            if (!$juntifyUser) {
                Auth::logout();
                return redirect()->route('login')
                    ->withErrors(['ddu_access' => 'Usuario no encontrado en el sistema principal.']);
            }

            // Check DDU membership using new model
            if (!IntegrantesEmpresa::isDduMember($juntifyUser->id)) {
                Auth::logout();
                return redirect()->route('login')
                    ->withErrors(['ddu_access' => 'Acceso restringido: Solo personal autorizado de DDU puede acceder.']);
            }

        } catch (\Exception $e) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['ddu_access' => 'Error de verificación de acceso.']);
        }

        return $next($request);
    }
}
