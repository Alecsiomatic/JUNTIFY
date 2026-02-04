<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\IntegrantesEmpresa;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials with DDU validation.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Validar directamente sin usar AuthService temporalmente
        $email = $this->input('email');
        $password = $this->input('password');

        // Verificar credenciales en juntify y membership DDU
        $userData = $this->validateUserDirectly($email, $password);

        if (!$userData) {
            RateLimiter::hit($this->throttleKey());

            // Verificar específicamente por qué falló la validación
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('email', $this->input('email'))
                ->first();

            if (!$juntifyUser) {
                // Usuario no existe en juntify
                throw ValidationException::withMessages([
                    'email' => 'Usuario no encontrado en el sistema.',
                ]);
            }

            // Usuario existe, verificar contraseña
            if (!password_verify($this->input('password'), $juntifyUser->password)) {
                // Contraseña incorrecta
                throw ValidationException::withMessages([
                    'email' => 'Contraseña incorrecta.',
                ]);
            }

            // Credenciales correctas, verificar membership DDU
            $isDduMember = IntegrantesEmpresa::isDduMember($juntifyUser->id);

            if (!$isDduMember) {
                // No es miembro DDU
                throw ValidationException::withMessages([
                    'ddu_access' => 'Acceso denegado: No perteneces a la organización DDU.',
                ]);
            }

            // Si llegamos aquí, hay otro error
            throw ValidationException::withMessages([
                'email' => 'Error de sistema. Contacte al administrador.',
            ]);
        }

        // Paso 3: Crear o actualizar usuario local con datos de juntify
        $localUser = User::updateOrCreate(
            ['id' => $userData['id']],
            [
                'username' => $userData['username'],
                'full_name' => $userData['full_name'],
                'email' => $userData['email'],
                'current_organization_id' => $userData['current_organization_id'],
                'roles' => json_encode([
                    'ddu_member',
                    'ddu_role:' . $userData['ddu_role']
                ]),
                'password' => '', // No almacenamos password localmente
            ]
        );

        // Paso 4: Iniciar sesión con el usuario local
        Auth::login($localUser, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Validate user directly without external service
     */
    private function validateUserDirectly($email, $password)
    {
        try {
            $juntifyUser = DB::connection('juntify')
                ->table('users')
                ->where('email', $email)
                ->first();

            if (!$juntifyUser) {
                return null;
            }

            if (!password_verify($password, $juntifyUser->password)) {
                return null;
            }

            $dduMembership = IntegrantesEmpresa::getDduMembership($juntifyUser->id);

            if (!$dduMembership) {
                return null;
            }

            return [
                'id' => $juntifyUser->id,
                'username' => $juntifyUser->username,
                'full_name' => $juntifyUser->full_name,
                'email' => $juntifyUser->email,
                'current_organization_id' => $juntifyUser->current_organization_id,
                'ddu_role' => $dduMembership->rol,
                'ddu_permissions' => $dduMembership->permisos,
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
}
