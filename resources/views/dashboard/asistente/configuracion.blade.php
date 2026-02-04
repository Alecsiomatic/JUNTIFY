@extends('layouts.dashboard')

@section('page-title', 'Configuración del Asistente')
@section('page-description', 'Configura las opciones de tu asistente inteligente')

@section('content')
<div class="space-y-6 fade-in">
    <div class="ddu-card border-l-4 {{ $apiConnected ? 'border-green-500' : 'border-red-500' }}">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $apiConnected ? 'API de OpenAI conectada' : 'API de OpenAI no conectada' }}
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $apiConnected
                        ? 'El asistente está listo para responder usando tu propia API de ChatGPT.'
                        : 'Configura tu API key de ChatGPT para activar las respuestas inteligentes del asistente.' }}
                </p>
            </div>
            <div class="text-xs text-gray-500">
                Última actualización: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="ddu-card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">API de OpenAI</h3>

            @if(session('status'))
                <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('assistant.settings.update') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="openai_api_key" class="block text-sm font-medium text-gray-700 mb-2">
                        API Key de ChatGPT
                    </label>
                    <input type="password"
                           id="openai_api_key"
                           name="openai_api_key"
                           value=""
                           placeholder="sk-..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ddu-lavanda focus:border-ddu-lavanda"
                           autocomplete="off">
                    <p class="text-xs text-gray-500 mt-1">
                        La clave se almacena cifrada y solo se usa para tus solicitudes.
                        Deja el campo vacío para mantener la clave actual.
                    </p>
                    @error('openai_api_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center space-x-3">
                    <input type="checkbox"
                           id="enable_drive_calendar"
                           name="enable_drive_calendar"
                           value="1"
                           @checked($settings->enable_drive_calendar)
                           class="rounded border-gray-300 text-ddu-lavanda focus:ring-ddu-lavanda">
                    <label for="enable_drive_calendar" class="text-sm text-gray-700">
                        Usar token de Google Drive para integrar Google Calendar
                    </label>
                </div>
                <p class="text-xs text-gray-500 ml-6">
                    Permite al asistente crear y consultar eventos de calendario usando tu token de Google Drive existente.
                </p>

                <button type="submit" class="btn btn-primary w-full">
                    Guardar configuración
                </button>
            </form>
        </div>

        <div class="ddu-card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Información del servicio</h3>

            <div class="space-y-4">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-blue-900">¿Cómo obtener una API Key?</h4>
                    <p class="text-sm text-blue-700 mt-2">
                        1. Ve a <a href="https://platform.openai.com/api-keys" target="_blank" class="underline">OpenAI API Keys</a><br>
                        2. Inicia sesión en tu cuenta de OpenAI<br>
                        3. Crea una nueva API key<br>
                        4. Copia la clave y pégala en el campo anterior
                    </p>
                </div>

                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-yellow-900">Privacidad y seguridad</h4>
                    <p class="text-sm text-yellow-700 mt-2">
                        • Tu API key se almacena cifrada en nuestros servidores<br>
                        • Solo tú puedes ver y usar tu propia configuración<br>
                        • Las conversaciones no se comparten entre usuarios<br>
                        • Puedes eliminar tu configuración en cualquier momento
                    </p>
                </div>

                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-green-900">Características del asistente</h4>
                    <p class="text-sm text-green-700 mt-2">
                        • Acceso a transcripciones completas de reuniones<br>
                        • Integración con Google Calendar para agendar eventos<br>
                        • Análisis de documentos cargados<br>
                        • Respuestas contextuales basadas en tu información
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-center">
        <a href="{{ route('assistant.index') }}" class="btn btn-secondary">
            Volver al asistente
        </a>
    </div>
</div>
@endsection
