@props(['meeting' => null])

@if ($meeting)
<div class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50" x-data="{ open: true }" x-show="open" x-cloak>
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl mx-4" @click.away="open = false; window.location.href = '{{ route('dashboard') }}';">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Detalles de la Reunión</h2>
                <p class="text-sm text-gray-500">Información completa y transcripción</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </a>
        </div>

        <div class="p-6" x-data="{ activeTab: 'resumen' }">
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-6">
                    <a href="#" @click.prevent="activeTab = 'resumen'"
                       :class="{ 'border-blue-500 text-blue-600': activeTab === 'resumen', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'resumen' }"
                       class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm flex items-center">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 11-2 0V4H6v12a1 1 0 11-2 0V4zm4 11a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd" /></svg>
                        Resumen
                    </a>
                    <a href="#" @click.prevent="activeTab = 'puntos_clave'"
                       :class="{ 'border-blue-500 text-blue-600': activeTab === 'puntos_clave', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'puntos_clave' }"
                       class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm flex items-center">
                       <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        Puntos Clave
                    </a>
                    <a href="#" @click.prevent="activeTab = 'transcripcion'"
                       :class="{ 'border-blue-500 text-blue-600': activeTab === 'transcripcion', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'transcripcion' }"
                       class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm flex items-center">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        Transcripción
                    </a>
                </nav>
            </div>

            <div>
                <div x-show="activeTab === 'resumen'">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 11-2 0V4H6v12a1 1 0 11-2 0V4zm4 11a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd" /></svg>
                            Resumen de la Reunión
                        </h3>
                        <p class="text-gray-600">{{ $meeting['summary'] ?? 'Resumen no disponible.' }}</p>
                        <p class="text-sm text-gray-500 mt-3">Reunión completada el {{ \Carbon\Carbon::parse($meeting['created_at'])->format('d/m/Y') }}.</p>
                    </div>

                    @if(!empty($meeting['audio_base64']) || !empty($meeting['audio_url']))
                    <div class="mt-6 bg-white rounded-lg border border-gray-200 p-4">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 00-3 3v2a3 3 0 003 3h3.763l7.79 3.894A1 1 0 0018 17V3zM5 8a1 1 0 011-1h2.394l2-1v10l-2-1H6a1 1 0 01-1-1V8z" /></svg>
                            Audio de la Reunión
                        </h3>
                        @if(!empty($meeting['audio_base64']))
                            <audio controls class="w-full" src="data:audio/mp3;base64,{{ $meeting['audio_base64'] }}">
                                Tu navegador no soporta el elemento de audio.
                            </audio>
                        @else
                            <audio controls class="w-full" src="{{ $meeting['audio_url'] }}">
                                Tu navegador no soporta el elemento de audio.
                            </audio>
                        @endif
                    </div>
                    @endif
                </div>

                <div x-show="activeTab === 'puntos_clave'">
                    <ul class="space-y-3">
                        @forelse($meeting['key_points'] as $point)
                            <li class="bg-gray-50 p-3 rounded-md flex items-start">
                                <svg class="w-5 h-5 mr-3 text-blue-500 flex-shrink-0 mt-1" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                <p class="text-gray-700">{{ is_array($point) ? ($point['description'] ?? $point['text'] ?? $point) : $point }}</p>
                            </li>
                        @empty
                            <p class="text-gray-500">No hay puntos clave disponibles.</p>
                        @endforelse
                    </ul>
                </div>

                <div x-show="activeTab === 'transcripcion'">
                    <div class="prose max-w-none text-gray-700 bg-gray-50 p-4 rounded-md max-h-96 overflow-y-auto">
                        {!! nl2br(e($meeting['transcription'] ?? 'Transcripción no disponible.')) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
