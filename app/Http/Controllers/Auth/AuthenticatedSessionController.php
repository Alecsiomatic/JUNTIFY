<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\IntegrantesEmpresa;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // La autenticación y validación DDU ya se hace en LoginRequest
        $request->authenticate();

        $user = Auth::user();

        // Verificación adicional de seguridad: confirmar que el usuario es miembro DDU
        // usando la nueva conexión a juntify_panels
        $juntifyUser = DB::connection('juntify')
            ->table('users')
            ->where('email', $user->email)
            ->first();

        if ($juntifyUser && !IntegrantesEmpresa::isDduMember($juntifyUser->id)) {
            Auth::logout();

            return redirect()->back()
                ->withErrors(['ddu_access' => 'Lo sentimos, no perteneces a DDU y tu acceso está restringido a este panel.'])
                ->withInput($request->except('password'));
        }

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
