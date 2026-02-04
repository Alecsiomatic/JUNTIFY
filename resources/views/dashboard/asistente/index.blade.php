@extends('layouts.dashboard')

@section('page-title', 'Asistente DDU')
@section('page-description', 'Tu asistente inteligente para reuniones, documentos y calendario')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
<div class="space-y-6 fade-in">
    <style>
        .suggestion-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(209, 213, 219, 1);
            background-color: rgba(243, 244, 246, 1);
            color: rgba(55, 65, 81, 1);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .suggestion-btn:hover {
            background-color: rgba(84, 108, 177, 0.12);
            border-color: rgba(84, 108, 177, 1);
            color: rgba(84, 108, 177, 1);
        }
    </style>

    <div class="ddu-card border-l-4 {{ $apiConnected ? 'border-green-500' : 'border-red-500' }}">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $apiConnected ? 'API de OpenAI conectada' : 'API de OpenAI no conectada' }}
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $apiConnected
                        ? 'El asistente está listo para responder usando tu propia API de ChatGPT.'
                        : 'Configura tu API key de ChatGPT en la página de configuración para activar las respuestas inteligentes.' }}
                </p>
            </div>
            <div class="text-xs text-gray-500">
                Última actualización: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>


    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <aside class="space-y-4 lg:col-span-1">
            <div class="ddu-card">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Conversaciones</h3>
                    <button id="newConversationBtn" class="btn btn-secondary text-xs px-3 py-1">Nueva</button>
                </div>
                <ul id="conversationList" class="mt-4 space-y-2 max-h-64 overflow-y-auto">
                    @forelse($conversations as $conversation)
                        <li class="conversation-item-container" data-id="{{ $conversation->id }}">
                            <div class="group relative">
                                <button class="conversation-item w-full text-left px-3 py-2 rounded-lg border transition"
                                        data-id="{{ $conversation->id }}"
                                        data-title="{{ $conversation->title ?? 'Conversación sin título' }}"
                                        @class([
                                            'border-ddu-lavanda bg-ddu-lavanda/10 text-ddu-lavenda' => optional($activeConversation)->id === $conversation->id,
                                            'border-transparent hover:border-ddu-lavanda/40 text-gray-700' => optional($activeConversation)->id !== $conversation->id,
                                        ])>
                                    <p class="text-sm font-medium truncate pr-8">{{ $conversation->title ?? 'Conversación sin título' }}</p>
                                    <p class="text-xs text-gray-500">{{ $conversation->messages_count }} mensajes</p>
                                </button>

                                <!-- Menú de opciones -->
                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="conversation-menu-toggle text-gray-400 hover:text-gray-600 p-1"
                                            data-conversation-id="{{ $conversation->id }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500">Inicia tu primera conversación con el asistente.</li>
                    @endforelse
                </ul>
            </div>



            <div class="ddu-card">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Contexto de reuniones</h3>
                <p class="text-xs text-gray-500 mb-3">Selecciona reuniones o contenedores para que el asistente use sus resúmenes, puntos clave y transcripciones.</p>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Reuniones</p>
                        <div class="mt-2 max-h-36 overflow-y-auto space-y-2" id="meetingSelector">
                            @foreach($meetings as $meeting)
                                <label class="flex items-start space-x-2 text-sm text-gray-700">
                                    <input type="checkbox" value="{{ $meeting->id }}" class="meeting-checkbox mt-1 rounded border-gray-300 text-ddu-lavanda focus:ring-ddu-lavanda">
                                    <span>
                                        <span class="font-medium">{{ $meeting->meeting_name ?? 'Reunión sin título' }}</span>
                                        <span class="block text-xs text-gray-500">{{ optional($meeting->created_at)->format('d/m/Y H:i') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Contenedores</p>
                        <div class="mt-2 max-h-36 overflow-y-auto space-y-2" id="containerSelector">
                            @forelse($containers as $container)
                                <label class="flex items-start space-x-2 text-sm text-gray-700">
                                    <input type="checkbox" value="{{ $container->id }}" class="container-checkbox mt-1 rounded border-gray-300 text-ddu-lavanda focus:ring-ddu-lavanda">
                                    <span>
                                        <span class="font-medium">{{ $container->name }}</span>
                                        <span class="block text-xs text-gray-500">{{ Str::limit($container->description, 70) }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-xs text-gray-500">No tienes contenedores configurados aún.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="ddu-card">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Documentos</h3>
                <p class="text-xs text-gray-500">Sube Word, Excel, PowerPoint, TXT o imágenes. El asistente analizará su contenido y lo integrará al contexto.</p>
                <form id="documentUploadForm" class="mt-3 space-y-3">
                    @csrf
                    <input type="hidden" name="conversation_id" id="documentConversationId" value="{{ optional($activeConversation)->id }}">
                    <label class="w-full flex flex-col items-center justify-center px-3 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-ddu-lavanda hover:text-ddu-lavanda transition">
                        <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span class="text-sm font-medium">Selecciona un archivo</span>
                        <span class="text-xs text-gray-500">Máximo 10 MB</span>
                        <input type="file" name="document" id="assistantDocument" class="hidden" accept=".docx,.pptx,.xlsx,.txt,.csv,.md,.png,.jpg,.jpeg,.gif">
                    </label>
                    <div id="documentUploadStatus" class="text-xs text-gray-500"></div>
                </form>
            </div>

            <div class="ddu-card">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Eventos próximos</h3>
                <p class="text-xs text-gray-500 mb-2">El asistente puede programar y consultar eventos usando tu token de Google.</p>
                <ul class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse($calendarEvents as $event)
                        <li class="text-sm text-gray-700">
                            <p class="font-medium">{{ $event['summary'] }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($event['start'])->translatedFormat('d M Y H:i') }} - {{ \Carbon\Carbon::parse($event['end'])->format('H:i') }}</p>
                            @if(!empty($event['description']))
                                <p class="text-xs text-gray-500 mt-1">{{ Str::limit($event['description'], 80) }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="text-xs text-gray-500">No hay eventos programados en las próximas dos semanas.</li>
                    @endforelse
                </ul>
            </div>
        </aside>

        <main class="lg:col-span-3">
            <div class="ddu-card flex flex-col" style="height: 600px;">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
                    <div>
                        <h2 id="conversationTitle" class="text-2xl font-bold text-gray-900">
                            {{ optional($activeConversation)->title ?? 'Bienvenido al asistente DDU' }}
                        </h2>
                        <p class="text-sm text-gray-500">El asistente responderá siempre usando el contexto de reuniones, documentos y tu calendario.</p>
                    </div>
                    <div class="text-xs text-gray-500">
                        Conversaciones totales: {{ $conversations->count() }}
                    </div>
                </div>

                <div id="chatMessages" class="flex-1 overflow-y-auto space-y-4 pr-2 min-h-0">
                    @if($activeConversation && $activeConversation->messages->isNotEmpty())
                        @foreach($activeConversation->messages as $message)
                            @continue($message->role === 'system')
                            <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-2xl">
                                    <div class="rounded-lg px-4 py-3 {{ $message->role === 'user' ? 'bg-gradient-to-r from-ddu-lavanda to-ddu-aqua text-white' : 'bg-gray-100 text-gray-800' }}">
                                        <p class="whitespace-pre-line text-sm">{{ $message->content }}</p>
                                        @if(!empty($message->metadata['attachments']))
                                            @foreach($message->metadata['attachments'] as $attachment)
                                                @if($attachment['type'] === 'image_url')
                                                    <img src="{{ $attachment['image_url']['url'] }}" alt="Vista previa" class="mt-3 rounded-lg max-w-xs">
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                    <span class="text-xs text-gray-500 block mt-1">
                                        {{ $message->role === 'user' ? 'Tú' : 'Asistente DDU' }} • {{ $message->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="bg-gray-50 border border-dashed border-gray-200 rounded-lg p-6 text-center text-sm text-gray-600">
                            ¡Comienza escribiendo tu primera consulta para que el asistente prepare el contexto!
                        </div>
                    @endif
                </div>

                <div id="typingIndicator" class="hidden mt-4">
                    <div class="flex items-center space-x-3 text-sm text-gray-500">
                        <div class="w-8 h-8 bg-gradient-to-r from-ddu-lavanda to-ddu-aqua rounded-full flex items-center justify-center text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <span>El asistente está pensando...</span>
                    </div>
                </div>

                <div class="border-t border-gray-200 mt-4 pt-4">
                    <input type="hidden" id="conversationId" value="{{ optional($activeConversation)->id }}">
                    <div class="flex items-center space-x-3">
                        <textarea id="chatInput" rows="2" placeholder="Escribe tu consulta sobre reuniones, documentos o calendar..." class="flex-1 resize-none px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ddu-lavanda focus:border-ddu-lavanda"></textarea>
                        <button id="sendMessageBtn" class="btn btn-primary px-6" {{ $apiConnected ? '' : 'disabled' }}>
                            <span class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span>Enviar</span>
                            </span>
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button class="suggestion-btn" data-suggestion="¿Qué reuniones importantes tengo esta semana?">📅 Reuniones esta semana</button>
                        <button class="suggestion-btn" data-suggestion="Resume los puntos clave de la última reunión de estrategia">📝 Resumen de reunión</button>
                        <button class="suggestion-btn" data-suggestion="Agenda una reunión de seguimiento con el equipo comercial el viernes a las 10am">⏰ Agendar reunión</button>
                        <button class="suggestion-btn" data-suggestion="Analiza el documento más reciente que cargué y dime de qué trata">📂 Analizar documento</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Indicador de escritura -->
    <div id="typingIndicator" class="max-w-4xl mx-auto" style="display: none;">
        <div class="ddu-card">
            <div class="p-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-r from-ddu-lavanda to-ddu-aqua rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <span class="text-gray-500 text-sm">El asistente está escribiendo...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para renombrar conversación -->
<div id="renameModal" class="ddu-modal" style="display: none;">
    <div class="ddu-modal-backdrop"></div>
    <div class="ddu-modal-content">
        <div class="ddu-modal-header">
            <h3 class="text-lg font-semibold text-gray-900">Renombrar conversación</h3>
            <button class="ddu-modal-close" onclick="closeRenameModal()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="ddu-modal-body">
            <form id="renameForm" onsubmit="handleRenameSubmit(event)">
                <div class="mb-4">
                    <label for="conversationTitle" class="block text-sm font-medium text-gray-700 mb-2">
                        Nuevo nombre de la conversación
                    </label>
                    <input type="text"
                           id="conversationTitle"
                           name="title"
                           required
                           maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ddu-lavanda focus:border-ddu-lavanda"
                           placeholder="Ingresa el nuevo nombre...">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRenameModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para eliminar conversación -->
<div id="deleteModal" class="ddu-modal" style="display: none;">
    <div class="ddu-modal-backdrop"></div>
    <div class="ddu-modal-content">
        <div class="ddu-modal-header">
            <h3 class="text-lg font-semibold text-gray-900">Eliminar conversación</h3>
            <button class="ddu-modal-close" onclick="closeDeleteModal()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="ddu-modal-body">
            <div class="mb-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">¿Estás seguro?</h4>
                        <p class="text-sm text-gray-500 mt-1">
                            Esta acción eliminará permanentemente la conversación "<span id="deleteConversationName"></span>"
                            y todos sus mensajes asociados. Esta acción no se puede deshacer.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                    Eliminar conversación
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let conversationId = document.getElementById('conversationId').value || null;

    const state = {
        meetings: new Set(),
        containers: new Set(),
    };

    document.querySelectorAll('.meeting-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', (event) => {
            const id = event.target.value;
            if (event.target.checked) {
                state.meetings.add(id);
            } else {
                state.meetings.delete(id);
            }
        });
    });

    document.querySelectorAll('.container-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', (event) => {
            const id = event.target.value;
            if (event.target.checked) {
                state.containers.add(id);
            } else {
                state.containers.delete(id);
            }
        });
    });

    document.querySelectorAll('.suggestion-btn').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('chatInput').value = button.dataset.suggestion;
            document.getElementById('chatInput').focus();
        });
    });

    const chatMessages = document.getElementById('chatMessages');
    const typingIndicator = document.getElementById('typingIndicator');

    async function sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();

        if (!message) {
            return;
        }

        appendMessage('user', message);
        input.value = '';
        showTyping(true);

        try {
            const response = await fetch('{{ route('assistant.message') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message,
                    conversation_id: conversationId,
                    meetings: Array.from(state.meetings),
                    containers: Array.from(state.containers),
                }),
            });

            if (!response.ok) {
                const error = await response.json();
                appendMessage('assistant', error.error ?? 'No fue posible obtener respuesta del asistente.');
                return;
            }

            const data = await response.json();
            conversationId = data.conversation_id;
            document.getElementById('conversationId').value = conversationId;
            document.getElementById('documentConversationId').value = conversationId;

            refreshMessages(data.messages);
            upsertConversation({
                id: conversationId,
                title: document.getElementById('conversationTitle').textContent,
                messages: data.messages,
            });
        } catch (error) {
            console.error(error);
            appendMessage('assistant', 'Ocurrió un error inesperado. Inténtalo nuevamente.');
        } finally {
            showTyping(false);
        }
    }

    function appendMessage(role, content, attachments = []) {
        const wrapper = document.createElement('div');
        wrapper.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'}`;

        const bubble = document.createElement('div');
        bubble.className = 'max-w-2xl';
        bubble.innerHTML = `
            <div class="rounded-lg px-4 py-3 ${role === 'user' ? 'bg-gradient-to-r from-ddu-lavanda to-ddu-aqua text-white' : 'bg-gray-100 text-gray-800'}">
                <p class="whitespace-pre-line text-sm">${content}</p>
            </div>
            <span class="text-xs text-gray-500 block mt-1">${role === 'user' ? 'Tú' : 'Asistente DDU'} • ahora</span>
        `;

        if (attachments.length > 0) {
            const container = bubble.querySelector('div');
            attachments.forEach((attachment) => {
                if (attachment.type === 'image_url') {
                    const img = document.createElement('img');
                    img.src = attachment.image_url.url;
                    img.alt = 'Vista previa';
                    img.className = 'mt-3 rounded-lg max-w-xs';
                    container.appendChild(img);
                }
            });
        }

        wrapper.appendChild(bubble);
        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function refreshMessages(messages) {
        chatMessages.innerHTML = '';
        messages.forEach((message) => {
            if (message.role === 'system') {
                return;
            }
            appendMessage(message.role, message.content, (message.metadata && message.metadata.attachments) ? message.metadata.attachments : []);
        });
    }

    function showTyping(visible) {
        typingIndicator.classList.toggle('hidden', !visible);
    }

    document.getElementById('sendMessageBtn').addEventListener('click', sendMessage);
    document.getElementById('chatInput').addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    const conversationListElement = document.getElementById('conversationList');

    function markActiveConversation(id) {
        conversationListElement.querySelectorAll('button').forEach((button) => {
            if (button.dataset.id === String(id)) {
                button.classList.add('border-ddu-lavanda', 'bg-ddu-lavanda/10', 'text-ddu-lavanda');
                button.classList.remove('border-transparent', 'text-gray-700');
            } else {
                button.classList.remove('border-ddu-lavanda', 'bg-ddu-lavanda/10', 'text-ddu-lavanda');
                button.classList.add('border-transparent', 'text-gray-700');
            }
        });
    }

    function getDisplayMessageCount(conversation) {
        const rawCount = conversation.messages_count ?? (conversation.messages ? conversation.messages.length : 0);

        return Math.max(0, rawCount - 1);
    }

    function renderConversationItem(conversation) {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.id = conversation.id;
        button.dataset.title = conversation.title || 'Conversación sin título';
        button.className = 'conversation-item w-full text-left px-3 py-2 rounded-lg border transition border-transparent text-gray-700 hover:border-ddu-lavanda/40';
        const count = getDisplayMessageCount(conversation);
        const label = count === 1 ? 'mensaje' : 'mensajes';

        button.innerHTML = `
            <p class="text-sm font-medium truncate">${conversation.title || 'Conversación sin título'}</p>
            <p class="text-xs text-gray-500">${count} ${label}</p>
        `;

        const listItem = document.createElement('li');
        listItem.appendChild(button);

        return listItem;
    }

    function upsertConversation(conversation) {
        let existing = conversationListElement.querySelector(`button[data-id="${conversation.id}"]`);

        if (existing) {
            existing.dataset.title = conversation.title || 'Conversación sin título';
            const count = getDisplayMessageCount(conversation);
            const label = count === 1 ? 'mensaje' : 'mensajes';
            existing.innerHTML = `
                <p class="text-sm font-medium truncate">${conversation.title || 'Conversación sin título'}</p>
                <p class="text-xs text-gray-500">${count} ${label}</p>
            `;
        } else {
            const item = renderConversationItem(conversation);
            conversationListElement.insertBefore(item, conversationListElement.firstChild);
        }

        markActiveConversation(conversation.id);
    }

    conversationListElement.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-id]');
        if (!button) {
            return;
        }

        try {
            const response = await fetch(`{{ url('asistente/conversaciones') }}/${button.dataset.id}`);
            const data = await response.json();
            conversationId = data.conversation.id;
            document.getElementById('conversationId').value = conversationId;
            document.getElementById('documentConversationId').value = conversationId;
            document.getElementById('conversationTitle').textContent = data.conversation.title || 'Conversación sin título';
            refreshMessages(data.conversation.messages);
            upsertConversation(data.conversation);
        } catch (error) {
            console.error(error);
        }
    });

    document.getElementById('newConversationBtn').addEventListener('click', async () => {
        try {
            const response = await fetch('{{ route('assistant.conversations.create') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            conversationId = data.conversation.id;
            document.getElementById('conversationId').value = conversationId;
            document.getElementById('documentConversationId').value = conversationId;
            document.getElementById('conversationTitle').textContent = data.conversation.title || 'Nueva conversación';
            refreshMessages(data.conversation.messages);
            upsertConversation(data.conversation);
        } catch (error) {
            console.error(error);
        }
    });

    if (conversationId) {
        markActiveConversation(conversationId);
    }

    document.getElementById('assistantDocument').addEventListener('change', async (event) => {
        if (!conversationId) {
            document.getElementById('documentUploadStatus').textContent = 'Crea o selecciona una conversación antes de subir documentos.';
            event.target.value = '';
            return;
        }

        const file = event.target.files[0];
        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('document', file);
        formData.append('conversation_id', conversationId);

        document.getElementById('documentUploadStatus').textContent = 'Analizando documento...';

        try {
            const response = await fetch('{{ route('assistant.documents.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            if (!response.ok) {
                const error = await response.json();
                document.getElementById('documentUploadStatus').textContent = error.message ?? 'No se pudo analizar el documento.';
                return;
            }

            const data = await response.json();
            document.getElementById('documentUploadStatus').textContent = 'Documento analizado y agregado al contexto.';

            try {
                const conversationResponse = await fetch(`{{ url('asistente/conversaciones') }}/${conversationId}`);
                const conversationData = await conversationResponse.json();
                refreshMessages(conversationData.conversation.messages);
                upsertConversation(conversationData.conversation);
            } catch (error) {
                console.error(error);
                appendMessage('assistant', data.summary ? `Resumen del documento: ${data.summary}` : 'El documento fue analizado y ahora forma parte del contexto.');
            }
        } catch (error) {
            console.error(error);
            document.getElementById('documentUploadStatus').textContent = 'Ocurrió un error al subir el documento.';
        } finally {
            event.target.value = '';
        }
    });

    // Manejo de menús contextuales para conversaciones
    document.addEventListener('click', function(event) {
        // Cerrar todos los menús abiertos cuando se hace clic fuera
        if (!event.target.closest('.conversation-menu')) {
            document.querySelectorAll('.conversation-menu').forEach(menu => {
                menu.remove();
            });
        }
    });

    // Agregar event listeners a los toggles de menú
    document.addEventListener('click', function(event) {
        if (event.target.closest('.conversation-menu-toggle')) {
            event.preventDefault();
            event.stopPropagation();

            const button = event.target.closest('.conversation-menu-toggle');
            const conversationId = button.getAttribute('data-conversation-id');
            const conversationItem = button.closest('.conversation-item-container');

            // Cerrar otros menús
            document.querySelectorAll('.conversation-menu').forEach(menu => {
                menu.remove();
            });

            // Crear y mostrar menú contextual
            showConversationMenu(button, conversationId, conversationItem);
        }
    });

    function showConversationMenu(button, conversationId, conversationItem) {
        const menu = document.createElement('div');
        menu.className = 'conversation-menu absolute right-0 top-8 bg-white border border-gray-200 rounded-lg shadow-lg z-50 min-w-40';
        menu.innerHTML = `
            <button class="rename-conversation w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-t-lg flex items-center space-x-2" data-conversation-id="${conversationId}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
                <span>Renombrar</span>
            </button>
            <button class="delete-conversation w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-b-lg flex items-center space-x-2" data-conversation-id="${conversationId}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <span>Eliminar</span>
            </button>
        `;

        const buttonRect = button.getBoundingClientRect();
        button.closest('.group').appendChild(menu);
    }

    // Variables globales para los modals
    let currentRenameConversationId = null;
    let currentDeleteConversationId = null;

    // Manejar renombrar conversación
    document.addEventListener('click', function(event) {
        if (event.target.closest('.rename-conversation')) {
            const button = event.target.closest('.rename-conversation');
            const conversationId = button.getAttribute('data-conversation-id');
            const conversationItem = document.querySelector(`[data-id="${conversationId}"]`);
            const currentTitle = conversationItem.getAttribute('data-title');

            // Cerrar menú
            document.querySelectorAll('.conversation-menu').forEach(menu => menu.remove());

            // Abrir modal de renombrar
            openRenameModal(conversationId, currentTitle);
        }
    });

    // Manejar eliminar conversación
    document.addEventListener('click', function(event) {
        if (event.target.closest('.delete-conversation')) {
            const button = event.target.closest('.delete-conversation');
            const conversationId = button.getAttribute('data-conversation-id');
            const conversationItem = document.querySelector(`[data-id="${conversationId}"]`);
            const conversationTitle = conversationItem.getAttribute('data-title');

            // Cerrar menú
            document.querySelectorAll('.conversation-menu').forEach(menu => menu.remove());

            // Abrir modal de eliminar
            openDeleteModal(conversationId, conversationTitle);
        }
    });

    async function updateConversationTitle(conversationId, newTitle) {
        try {
            const response = await fetch(`/asistente/conversaciones/${conversationId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ title: newTitle })
            });

            if (response.ok) {
                const data = await response.json();
                // Actualizar la UI
                const conversationItem = document.querySelector(`[data-id="${conversationId}"]`);
                if (conversationItem) {
                    conversationItem.setAttribute('data-title', newTitle);
                    const titleElement = conversationItem.querySelector('.text-sm.font-medium');
                    if (titleElement) {
                        titleElement.textContent = newTitle;
                    }
                }

                // Actualizar título si es la conversación activa
                if (conversationId == (document.getElementById('conversationId').value)) {
                    document.getElementById('conversationTitle').textContent = newTitle;
                }
            } else {
                alert('Error al actualizar el nombre de la conversación');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al actualizar el nombre de la conversación');
        }
    }

    async function deleteConversation(conversationId) {
        try {
            const response = await fetch(`/asistente/conversaciones/${conversationId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (response.ok) {
                // Eliminar de la UI
                const conversationContainer = document.querySelector(`[data-id="${conversationId}"]`);
                if (conversationContainer) {
                    conversationContainer.remove();
                }

                // Si era la conversación activa, limpiar el chat
                if (conversationId == (document.getElementById('conversationId').value)) {
                    clearChat();
                    document.getElementById('conversationId').value = '';
                    document.getElementById('conversationTitle').textContent = 'Bienvenido al asistente DDU';
                }
            } else {
                alert('Error al eliminar la conversación');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al eliminar la conversación');
        }
    }

    // Función para limpiar el chat
    function clearChat() {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.innerHTML = `
                <div class="bg-gray-50 border border-dashed border-gray-200 rounded-lg p-6 text-center text-sm text-gray-600">
                    ¡Comienza escribiendo tu primera consulta para que el asistente prepare el contexto!
                </div>
            `;
        }
    }

    // Funciones para el modal de renombrar
    function openRenameModal(conversationId, currentTitle) {
        currentRenameConversationId = conversationId;
        document.getElementById('conversationTitle').value = currentTitle;
        document.getElementById('renameModal').style.display = 'flex';
        document.getElementById('conversationTitle').focus();
    }

    function closeRenameModal() {
        document.getElementById('renameModal').style.display = 'none';
        currentRenameConversationId = null;
        document.getElementById('conversationTitle').value = '';
    }

    function handleRenameSubmit(event) {
        event.preventDefault();
        const newTitle = document.getElementById('conversationTitle').value.trim();
        if (newTitle && currentRenameConversationId) {
            updateConversationTitle(currentRenameConversationId, newTitle);
            closeRenameModal();
        }
    }

    // Funciones para el modal de eliminar
    function openDeleteModal(conversationId, conversationTitle) {
        currentDeleteConversationId = conversationId;
        document.getElementById('deleteConversationName').textContent = conversationTitle;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        currentDeleteConversationId = null;
    }

    function confirmDelete() {
        if (currentDeleteConversationId) {
            deleteConversation(currentDeleteConversationId);
            closeDeleteModal();
        }
    }

    // Cerrar modals con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeRenameModal();
            closeDeleteModal();
        }
    });

    // Cerrar modals al hacer clic en el backdrop
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('ddu-modal-backdrop')) {
            closeRenameModal();
            closeDeleteModal();
        }
    });
</script>
@endsection
