<?php

namespace App\Http\Controllers;

use App\Services\JuntifyApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantSettingsController extends Controller
{
    protected JuntifyApiService $juntifyApi;

    public function __construct(JuntifyApiService $juntifyApi)
    {
        $this->juntifyApi = $juntifyApi;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        
        // Obtener configuración desde Juntify API
        $result = $this->juntifyApi->getAssistantSettings($user->id);
        
        $settings = null;
        $apiConnected = false;
        
        if ($result['success'] && $result['data']) {
            $settings = (object) $result['data'];
            $apiConnected = $settings->openai_api_key_configured ?? false;
        } else {
            // Valores por defecto si no hay configuración
            $settings = (object) [
                'enable_drive_calendar' => true,
                'openai_api_key_configured' => false,
            ];
        }

        return view('dashboard.asistente.configuracion', compact('settings', 'apiConnected'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'enable_drive_calendar' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        
        $settingsData = [
            'enable_drive_calendar' => $request->boolean('enable_drive_calendar'),
        ];

        // Solo incluir la API key si se proporcionó un valor no vacío
        if ($request->filled('openai_api_key')) {
            $settingsData['openai_api_key'] = $validated['openai_api_key'];
        }

        // Guardar configuración en Juntify API
        $result = $this->juntifyApi->saveAssistantSettings($user->id, $settingsData);

        if (!$result['success']) {
            return back()->withErrors(['message' => $result['error'] ?? 'Error al guardar la configuración']);
        }

        return back()->with('status', 'Configuración del asistente actualizada correctamente.');
    }
}
