<x-guest-layout>
    <div class="text-center mb-8">
        <h1 class="font-area-inktrap text-3xl font-bold text-ddu-lavanda mb-2">
            Acceso Sistema DDU
        </h1>
        <p class="font-lato text-gray-600 text-sm">
            Autenticación vía Juntify - Solo usuarios autorizados
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <!-- Success Message -->
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
            <p class="font-lato text-sm">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Error Messages -->
    @if ($errors->has('message'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
            <p class="font-lato text-sm">{{ $errors->first('message') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

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
                   autofocus
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
                   autocomplete="current-password"
                   class="font-lato block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ddu-aqua focus:border-ddu-aqua transition-all duration-200 bg-gray-50 focus:bg-white"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <button type="submit"
                class="w-full bg-ddu-lavenda hover:bg-ddu-aqua text-white font-lato font-semibold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-ddu-aqua focus:ring-opacity-50 shadow-lg"
                style="background: linear-gradient(135deg, #6F78E4 0%, #6DDEDD 100%);">
            <span class="font-zuume tracking-wider">INICIAR SESIÓN</span>
        </button>

        <!-- Info Footer -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-lato text-sm text-blue-800 font-semibold mb-1">Acceso Restringido</p>
                        <p class="font-lato text-xs text-blue-700">
                            Este sistema está protegido. Solo usuarios registrados en Juntify y que pertenezcan a la empresa DDU pueden acceder.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-guest-layout>
