<?php

namespace App\Http\Middleware;

use App\Models\IntegrantesEmpresa;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureDduMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Verificar en la base de datos juntify si el usuario existe
        $juntifyUser = DB::connection('juntify')
            ->table('users')
            ->where('email', $user->email)
            ->first();

        if (!$juntifyUser) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['ddu_access' => 'Usuario no encontrado en el sistema Juntify.']);
        }

        // Verificar si es miembro DDU en juntify_panels usando el nuevo modelo
        if (!IntegrantesEmpresa::isDduMember($juntifyUser->id)) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['ddu_access' => 'Acceso restringido: Solo personal autorizado de DDU puede acceder al sistema.']);
        }

        return $next($request);
    }
}
