<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class CheckJuntifyAuth
{
    /**
     * Verificar que el usuario esté autenticado vía Juntify y pertenezca a DDU
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar autenticación básica
        if (!Session::has('authenticated') || !Session::get('authenticated')) {
            Log::warning('Intento de acceso sin autenticación');
            return redirect()->route('login')
                ->withErrors(['message' => 'Debes iniciar sesión para acceder.']);
        }

        // Verificar que exista información del usuario
        if (!Session::has('juntify_user') || !Session::get('juntify_user')) {
            Log::warning('Sesión sin información de usuario');
            Session::flush();
            return redirect()->route('login')
                ->withErrors(['message' => 'Sesión inválida. Inicia sesión nuevamente.']);
        }

        // Verificar que sea empresa DDU
        $company = Session::get('juntify_company');
        if (!$company || !isset($company['nombre']) || strtolower($company['nombre']) !== 'ddu') {
            Log::warning('Intento de acceso de usuario no perteneciente a DDU', [
                'user' => Session::get('juntify_user.email'),
                'company' => $company['nombre'] ?? 'N/A'
            ]);
            
            Session::flush();
            return redirect()->route('login')
                ->withErrors(['message' => 'Acceso denegado. Solo usuarios de DDU pueden acceder.']);
        }

        // Verificar tiempo de sesión (opcional: 8 horas)
        $authTime = Session::get('juntify_auth_time');
        if ($authTime && (now()->timestamp - $authTime) > 28800) { // 8 horas
            Log::info('Sesión expirada por tiempo para: ' . Session::get('juntify_user.email'));
            Session::flush();
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['message' => 'Tu sesión ha expirado. Inicia sesión nuevamente.']);
        }

        // Verificar y sincronizar usuario de Laravel Auth si es necesario
        if (!Auth::check()) {
            $juntifyUser = Session::get('juntify_user');
            
            // Buscar o crear el usuario local basado en los datos de Juntify
            $localUser = User::find($juntifyUser['id']);
            
            if (!$localUser) {
                // Crear el usuario si no existe
                $localUser = User::create([
                    'id' => $juntifyUser['id'],
                    'username' => $juntifyUser['username'] ?? $juntifyUser['email'],
                    'full_name' => $juntifyUser['full_name'] ?? $juntifyUser['username'] ?? '',
                    'email' => $juntifyUser['email'],
                    'current_organization_id' => $juntifyUser['current_organization_id'] ?? null,
                    'roles' => json_encode(['ddu_member']),
                    'password' => '',
                ]);
            }
            
            // Re-autenticar al usuario en Laravel
            Auth::login($localUser);
            Log::info('Usuario re-autenticado en Laravel Auth', ['user_id' => $localUser->id]);
        }

        return $next($request);
    }
}
