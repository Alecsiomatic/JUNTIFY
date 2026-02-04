<x-guest-layout>
    <div class="text-center mb-8">
        <h1 class="font-area-inktrap text-3xl font-bold text-ddu-lavanda mb-2">
            Crear Cuenta
        </h1>
        <p class="font-lato text-gray-600 text-sm">
            Regístrate para acceder al panel DDU
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="font-lato block text-sm font-semibold text-gray-700 mb-2">
                Nombre Completo
            </label>
            <input id="name"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   autofocus
                   autocomplete="name"
                   class="font-lato block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ddu-aqua focus:border-ddu-aqua transition-all duration-200 bg-gray-50 focus:bg-white"
                   placeholder="Tu nombre completo">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="font-lato block text-sm font-semibold text-gray-700 mb-2">
                Correo Electrónico
            </label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="username"
                   class="font-lato block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ddu-aqua focus:border-ddu-aqua transition-all duration-200 bg-gray-50 focus:bg-white"
                   placeholder="tu@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="font-lato block text-sm font-semibold text-gray-700 mb-2">
                Contraseña
            </label>
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="new-password"
                   class="font-lato block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ddu-aqua focus:border-ddu-aqua transition-all duration-200 bg-gray-50 focus:bg-white"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="font-lato block text-sm font-semibold text-gray-700 mb-2">
                Confirmar Contraseña
            </label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   class="font-lato block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ddu-aqua focus:border-ddu-aqua transition-all duration-200 bg-gray-50 focus:bg-white"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <button type="submit"
                class="w-full bg-ddu-lavenda hover:bg-ddu-aqua text-white font-lato font-semibold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-ddu-aqua focus:ring-opacity-50 shadow-lg"
                style="background: linear-gradient(135deg, #6F78E4 0%, #6DDEDD 100%);">
            <span class="font-zuume tracking-wider">REGISTRARSE</span>
        </button>

        <!-- Login Link -->
        <div class="text-center mt-6 pt-6 border-t border-gray-200">
            <p class="font-lato text-sm text-gray-600">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}"
                   class="text-ddu-lavanda hover:text-ddu-aqua font-semibold transition-colors duration-200">
                    Inicia sesión aquí
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
