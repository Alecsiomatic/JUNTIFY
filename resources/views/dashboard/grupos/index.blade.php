@extends('layouts.dashboard')

@section('page-title', 'Mis grupos')
@section('page-description', 'Organiza equipos y comparte las reuniones con otros miembros de la plataforma')

@php
    use Illuminate\Support\Str;
@endphp

@push('styles')
<style>
    .shared-tab-button {
        border-color: transparent;
        color: #6b7280;
    }
    .shared-tab-button:hover {
        color: #374151;
        border-color: #d1d5db;
    }
    .shared-tab-button.shared-tab-active {
        color: #8B5CF6;
        border-color: #8B5CF6;
    }
</style>
@endpush

@section('content')
    <div class="space-y-6">
        <!-- Mensajes de estado dinámicos -->
        <div id="status-message" class="hidden px-4 py-3 rounded-lg shadow-sm"></div>

        <div class="ddu-card">
            <div class="ddu-card-header">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Crear nuevo grupo</h2>
                    <p class="text-sm text-gray-500">Diseña espacios colaborativos para compartir tus reuniones con miembros específicos.</p>
                </div>
            </div>

            <form id="createGroupForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="groupName" class="block text-sm font-medium text-gray-700 mb-1">Nombre del grupo</label>
                        <input type="text" id="groupName" name="nombre" required maxlength="255"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda focus:border-ddu-lavanda"
                               placeholder="Equipo de proyectos">
                    </div>
                    <div>
                        <label for="groupDescription" class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
                        <input type="text" id="groupDescription" name="descripcion" maxlength="1000"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda focus:border-ddu-lavanda"
                               placeholder="Define el propósito del grupo">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-ddu inline-flex items-center" id="createGroupBtn">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Crear grupo
                    </button>
                </div>
            </form>
        </div>

        <!-- Contenedor de grupos - se carga dinámicamente -->
        <div id="groupsContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="col-span-full flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-4 border-ddu-lavanda border-t-transparent"></div>
                <span class="ml-3 text-gray-500">Cargando grupos...</span>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar grupo -->
    <div id="deleteGroupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                </div>

                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2" id="deleteGroupTitle">
                        ¿Eliminar grupo?
                    </h3>
                    <div class="text-sm text-gray-500 space-y-2 mb-6">
                        <p class="font-medium">Esta acción:</p>
                        <ul class="text-left space-y-1">
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                Eliminará el grupo permanentemente
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                Sacará a todos los miembros del grupo
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                Quitará el acceso a todas las reuniones compartidas
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                No se puede deshacer
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button"
                            onclick="closeDeleteGroupModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda">
                        Cancelar
                    </button>
                    <button type="button"
                            onclick="confirmDeleteGroup()"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Eliminar grupo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para dejar de compartir reunión -->
    <div id="unshareMeetingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100">
                        <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L5.732 15.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                </div>

                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        ¿Dejar de compartir reunión?
                    </h3>
                    <p class="text-sm text-gray-500 mb-6" id="unshareMeetingMessage">
                        Los miembros del grupo perderán acceso a esta reunión.
                    </p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button"
                            onclick="closeUnshareMeetingModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda">
                        Cancelar
                    </button>
                    <button type="button"
                            onclick="confirmUnshareMeeting()"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        Dejar de compartir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles de reunión compartida -->
    <div id="sharedMeetingDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900" id="sharedMeetingTitle">Detalles de la Reunión</h3>
                        <p class="text-sm text-gray-500 mt-1" id="sharedMeetingSubtitle">Cargando...</p>
                    </div>
                    <button onclick="closeSharedMeetingDetailsModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Loader -->
            <div id="sharedMeetingLoader" class="p-8 flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-4 border-ddu-lavanda border-t-transparent"></div>
                <span class="ml-3 text-gray-500">Cargando detalles de la reunión...</span>
            </div>

            <!-- Contenido con pestañas -->
            <div id="sharedMeetingTabsContent" class="flex-1 overflow-hidden flex flex-col" style="display: none;">
                <!-- Navegación de pestañas -->
                <div class="border-b border-gray-200 px-6">
                    <nav class="flex space-x-8">
                        <button type="button"
                                class="shared-tab-button shared-tab-active py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                data-tab="shared-resumen">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Resumen</span>
                            </div>
                        </button>
                        <button type="button"
                                class="shared-tab-button py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                data-tab="shared-puntos-clave">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <span>Puntos Clave</span>
                            </div>
                        </button>
                        <button type="button"
                                class="shared-tab-button py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                data-tab="shared-transcripcion">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                <span>Transcripción</span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Contenido de las pestañas -->
                <div class="p-6 overflow-y-auto flex-1">
                    <!-- Pestaña 1: Resumen -->
                    <div id="shared-tab-resumen" class="shared-tab-pane">
                        <div class="space-y-6">
                            <!-- Permisos -->
                            <div id="sharedMeetingPermisos" class="bg-ddu-aqua/10 border border-ddu-aqua/30 rounded-lg p-4">
                                <!-- Se llena dinámicamente -->
                            </div>

                            <!-- Resumen del contenido -->
                            <div class="bg-gradient-to-r from-ddu-lavanda/10 via-ddu-aqua/5 to-ddu-lavanda/10 rounded-xl p-6 border border-ddu-lavanda/20">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-ddu-lavanda" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Resumen de la Reunión
                                </h4>
                                <p id="sharedMeetingSummary" class="text-gray-700 leading-relaxed"></p>
                            </div>

                            <!-- Reproductor de audio -->
                            <div id="sharedMeetingAudioContainer" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-ddu-aqua" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                                    </svg>
                                    Audio de la Reunión
                                </h4>
                                <div class="bg-gradient-to-r from-ddu-aqua/5 to-ddu-lavanda/5 rounded-lg p-4">
                                    <audio id="sharedMeetingAudioPlayer" controls class="w-full"></audio>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña 2: Puntos Clave -->
                    <div id="shared-tab-puntos-clave" class="shared-tab-pane" style="display: none;">
                        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-6 border border-green-200">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Puntos Clave de la Reunión
                            </h4>
                            <ul id="sharedMeetingKeyPoints" class="space-y-3"></ul>
                        </div>
                    </div>

                    <!-- Pestaña 3: Transcripción -->
                    <div id="shared-tab-transcripcion" class="shared-tab-pane" style="display: none;">
                        <div class="bg-gradient-to-r from-ddu-lavanda/5 to-ddu-aqua/5 rounded-xl p-6 border border-ddu-lavanda/20">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-ddu-lavanda" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                Transcripción Completa
                            </h4>
                            <div id="sharedMeetingSegments" class="space-y-4 max-h-96 overflow-y-auto"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer con botón cerrar -->
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <div class="flex justify-end">
                    <button onclick="closeSharedMeetingDetailsModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para añadir miembro -->
    <div id="addMemberModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Añadir miembro al grupo</h3>
                    <button onclick="closeAddMemberModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-500 mb-4" id="addMemberGroupName">Selecciona un miembro de la empresa.</p>

                <div class="space-y-4">
                    <div>
                        <label for="memberSelect" class="block text-sm font-medium text-gray-700 mb-1">Miembro</label>
                        <select id="memberSelect" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda focus:border-ddu-lavanda">
                            <option value="">Selecciona un miembro...</option>
                        </select>
                    </div>

                    <div>
                        <label for="memberRole" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select id="memberRole" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda focus:border-ddu-lavanda">
                            <option value="colaborador">Colaborador</option>
                            <option value="administrador">Administrador</option>
                            <option value="invitado">Invitado</option>
                        </select>
                    </div>
                </div>

                <div id="addMemberFeedback" class="hidden mt-4 px-3 py-2 rounded-lg text-sm"></div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button"
                            onclick="closeAddMemberModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda">
                        Cancelar
                    </button>
                    <button type="button"
                            onclick="confirmAddMember()"
                            id="addMemberBtn"
                            class="px-4 py-2 text-sm font-medium text-white bg-ddu-lavanda rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-ddu-lavanda">
                        Añadir miembro
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        let groups = [];
        let companyMembers = [];
        let currentGroupData = null;
        let currentMeetingData = null;
        let currentAddMemberGroupId = null;

        // Mostrar mensaje de estado
        function showStatusMessage(message, type = 'success') {
            const statusEl = document.getElementById('status-message');
            statusEl.textContent = message;
            statusEl.className = `px-4 py-3 rounded-lg shadow-sm ${
                type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' :
                type === 'error' ? 'bg-red-50 border border-red-200 text-red-700' :
                'bg-blue-50 border border-blue-200 text-blue-700'
            }`;
            statusEl.classList.remove('hidden');

            setTimeout(() => {
                statusEl.classList.add('hidden');
            }, 5000);
        }

        // Cargar grupos de la empresa
        async function loadGroups() {
            try {
                const response = await fetch('/api/company-groups/', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error al cargar grupos');
                }

                groups = data.groups || [];
                renderGroups();

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('groupsContainer').innerHTML = `
                    <div class="col-span-full ddu-card text-center">
                        <p class="text-red-500">${error.message || 'Error al cargar los grupos'}</p>
                        <button onclick="loadGroups()" class="mt-4 btn-ddu">Reintentar</button>
                    </div>
                `;
            }
        }

        // Cargar miembros de la empresa
        async function loadCompanyMembers() {
            try {
                const response = await fetch('/api/company-groups/members', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json();

                if (data.success && Array.isArray(data.members)) {
                    companyMembers = data.members;
                }
            } catch (error) {
                console.error('Error cargando miembros:', error);
            }
        }

        // Escapar HTML para prevenir XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Renderizar grupos
        function renderGroups() {
            const container = document.getElementById('groupsContainer');

            if (!groups.length) {
                container.innerHTML = `
                    <div class="col-span-full ddu-card text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M9 7a3 3 0 106 0 3 3 0 00-6 0z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Aún no tienes grupos creados</h3>
                        <p class="text-sm text-gray-500">Crea tu primer grupo para compartir reuniones con otros usuarios de la plataforma.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = groups.map(group => renderGroupCard(group)).join('');
        }

        // Renderizar tarjeta de grupo
        function renderGroupCard(group) {
            const membersCount = group.miembros?.length || group.miembros_count || 0;
            const meetingsCount = group.reuniones_compartidas?.length || 0;
            const members = group.miembros || [];
            const meetings = group.reuniones_compartidas || [];

            return `
                <div class="ddu-card" data-group-id="${group.id}">
                    <div class="ddu-card-header">
                        <div>
                            <h3 class="ddu-card-title">${escapeHtml(group.nombre)}</h3>
                            <p class="ddu-card-subtitle">
                                ${membersCount} ${membersCount === 1 ? 'miembro' : 'miembros'} · ${meetingsCount} ${meetingsCount === 1 ? 'reunión' : 'reuniones'}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white border border-ddu-aqua text-ddu-navy-dark">
                                Grupo activo
                            </span>
                            <button onclick="showDeleteGroupModal(${group.id}, '${escapeHtml(group.nombre)}')"
                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                    title="Eliminar grupo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    ${group.descripcion ? `
                        <div class="p-4 mb-4 bg-ddu-aqua/10 border border-ddu-aqua/30 rounded-xl text-sm text-ddu-navy-dark">
                            ${escapeHtml(group.descripcion)}
                        </div>
                    ` : ''}

                    <div class="space-y-5">
                        <!-- Miembros -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Miembros</h4>
                            <div class="flex flex-wrap gap-2">
                                ${members.length > 0 ? members.map(member => `
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-ddu-lavanda/10 text-ddu-lavanda text-xs font-semibold">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        ${escapeHtml(member.nombre || member.username || member.user_id)}
                                        <span class="ml-1 text-xs text-gray-400">(${member.rol || 'miembro'})</span>
                                    </span>
                                `).join('') : '<span class="text-sm text-gray-500">Sin miembros aún</span>'}
                            </div>
                        </div>

                        <!-- Añadir miembro -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Añadir miembro</h4>
                            <button onclick="showAddMemberModal(${group.id}, '${escapeHtml(group.nombre)}')"
                                    class="btn-ddu inline-flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                Añadir miembro
                            </button>
                        </div>

                        <!-- Reuniones compartidas -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Reuniones compartidas</h4>
                            ${meetings.length > 0 ? `
                                <ul class="space-y-3 text-sm text-gray-700">
                                    ${meetings.slice(0, 4).map(meeting => `
                                        <li class="bg-gray-50 border border-gray-200 rounded-lg p-3 hover:bg-gray-100 transition-colors cursor-pointer"
                                            onclick="openSharedMeetingDetails(${meeting.meeting_id || meeting.id}, ${group.id}, '${escapeHtml(group.nombre)}')">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <span class="font-medium text-gray-900 block truncate">${escapeHtml(meeting.nombre || meeting.meeting_name || 'Reunión')}</span>
                                                    ${meeting.compartido_por ? `
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            Compartida por <span class="font-medium text-ddu-lavanda">${escapeHtml(meeting.compartido_por)}</span>
                                                        </p>
                                                    ` : ''}
                                                    <p class="text-xs text-ddu-aqua mt-1 flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                        Clic para ver detalles
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2 ml-3">
                                                    <button onclick="event.stopPropagation(); showUnshareMeetingModal(${meeting.meeting_id || meeting.id}, ${group.id}, '${escapeHtml(meeting.nombre || meeting.meeting_name || 'Reunión')}', '${escapeHtml(group.nombre)}')"
                                                            class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-600 font-semibold hover:bg-red-100 transition-colors"
                                                            title="Dejar de compartir">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    `).join('')}
                                </ul>
                                ${meetings.length > 4 ? `<p class="mt-2 text-xs text-gray-500">y ${meetings.length - 4} reuniones más compartidas.</p>` : ''}
                            ` : `<p class="text-sm text-gray-500">Aún no has compartido reuniones con este grupo. Desde la sección de reuniones podrás añadirlas.</p>`}
                        </div>
                    </div>
                </div>
            `;
        }

        // Crear grupo
        document.getElementById('createGroupForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const nombre = document.getElementById('groupName').value.trim();
            const descripcion = document.getElementById('groupDescription').value.trim();

            if (!nombre) {
                showStatusMessage('El nombre del grupo es obligatorio', 'error');
                return;
            }

            const btn = document.getElementById('createGroupBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Creando...';

            try {
                const response = await fetch('/api/company-groups/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ nombre, descripcion }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Error al crear el grupo');
                }

                showStatusMessage(data.message || 'Grupo creado exitosamente', 'success');

                document.getElementById('groupName').value = '';
                document.getElementById('groupDescription').value = '';

                await loadGroups();

            } catch (error) {
                showStatusMessage(error.message || 'Error al crear el grupo', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Crear grupo';
            }
        });

        // Modal de eliminar grupo
        function showDeleteGroupModal(groupId, groupName) {
            currentGroupData = { id: groupId, name: groupName };
            document.getElementById('deleteGroupTitle').textContent = `¿Eliminar grupo "${groupName}"?`;

            const modal = document.getElementById('deleteGroupModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteGroupModal() {
            const modal = document.getElementById('deleteGroupModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            currentGroupData = null;
        }

        async function confirmDeleteGroup() {
            if (!currentGroupData) return;

            try {
                const response = await fetch(`/api/company-groups/${currentGroupData.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Error al eliminar el grupo');
                }

                showStatusMessage(data.message || 'Grupo eliminado exitosamente', 'success');
                closeDeleteGroupModal();
                await loadGroups();

            } catch (error) {
                showStatusMessage(error.message || 'Error al eliminar el grupo', 'error');
                closeDeleteGroupModal();
            }
        }

        // Modal de añadir miembro
        async function showAddMemberModal(groupId, groupName) {
            currentAddMemberGroupId = groupId;
            document.getElementById('addMemberGroupName').textContent = `Añadir miembro al grupo "${groupName}"`;

            if (companyMembers.length === 0) {
                await loadCompanyMembers();
            }

            const select = document.getElementById('memberSelect');
            select.innerHTML = '<option value="">Selecciona un miembro...</option>';

            companyMembers.forEach(member => {
                const option = document.createElement('option');
                option.value = member.user_id || member.id;
                option.textContent = member.nombre || member.username || member.email || member.user_id;
                select.appendChild(option);
            });

            const feedback = document.getElementById('addMemberFeedback');
            feedback.classList.add('hidden');
            feedback.textContent = '';

            const modal = document.getElementById('addMemberModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeAddMemberModal() {
            const modal = document.getElementById('addMemberModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            currentAddMemberGroupId = null;
        }

        async function confirmAddMember() {
            if (!currentAddMemberGroupId) return;

            const userId = document.getElementById('memberSelect').value;
            const rol = document.getElementById('memberRole').value;

            if (!userId) {
                const feedback = document.getElementById('addMemberFeedback');
                feedback.textContent = 'Por favor, selecciona un miembro';
                feedback.className = 'mt-4 px-3 py-2 rounded-lg text-sm bg-red-50 text-red-600';
                feedback.classList.remove('hidden');
                return;
            }

            const btn = document.getElementById('addMemberBtn');
            btn.disabled = true;
            btn.textContent = 'Añadiendo...';

            try {
                const response = await fetch(`/api/company-groups/${currentAddMemberGroupId}/members`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ user_id: userId, rol }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Error al añadir el miembro');
                }

                showStatusMessage(data.message || 'Miembro añadido exitosamente', 'success');
                closeAddMemberModal();
                await loadGroups();

            } catch (error) {
                const feedback = document.getElementById('addMemberFeedback');
                feedback.textContent = error.message || 'Error al añadir el miembro';
                feedback.className = 'mt-4 px-3 py-2 rounded-lg text-sm bg-red-50 text-red-600';
                feedback.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Añadir miembro';
            }
        }

        // Modal de dejar de compartir reunión
        function showUnshareMeetingModal(meetingId, groupId, meetingName, groupName) {
            currentMeetingData = { meetingId, groupId, meetingName, groupName };

            document.getElementById('unshareMeetingMessage').textContent =
                `¿Dejar de compartir "${meetingName}" con el grupo "${groupName}"? Los miembros del grupo perderán acceso a esta reunión.`;

            const modal = document.getElementById('unshareMeetingModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeUnshareMeetingModal() {
            const modal = document.getElementById('unshareMeetingModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            currentMeetingData = null;
        }

        async function confirmUnshareMeeting() {
            if (!currentMeetingData) return;

            try {
                const response = await fetch(`/api/company-groups/${currentMeetingData.groupId}/shared-meetings/${currentMeetingData.meetingId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Error al dejar de compartir');
                }

                showStatusMessage(data.message || 'Se dejó de compartir la reunión', 'success');
                closeUnshareMeetingModal();
                await loadGroups();

            } catch (error) {
                showStatusMessage(error.message || 'Error al dejar de compartir', 'error');
                closeUnshareMeetingModal();
            }
        }

        // Cerrar modales con Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeleteGroupModal();
                closeAddMemberModal();
                closeUnshareMeetingModal();
                closeSharedMeetingDetailsModal();
            }
        });

        // Cerrar modales haciendo clic fuera
        ['deleteGroupModal', 'addMemberModal', 'unshareMeetingModal', 'sharedMeetingDetailsModal'].forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === this) {
                        if (modalId === 'deleteGroupModal') closeDeleteGroupModal();
                        if (modalId === 'addMemberModal') closeAddMemberModal();
                        if (modalId === 'unshareMeetingModal') closeUnshareMeetingModal();
                        if (modalId === 'sharedMeetingDetailsModal') closeSharedMeetingDetailsModal();
                    }
                });
            }
        });

        // Variables para reunión compartida
        let currentSharedMeetingData = null;

        // Inicializar pestañas del modal compartido
        function initSharedTabs() {
            document.querySelectorAll('.shared-tab-button').forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.dataset.tab;

                    // Desactivar todas las pestañas
                    document.querySelectorAll('.shared-tab-button').forEach(btn => {
                        btn.classList.remove('shared-tab-active', 'border-ddu-lavanda', 'text-ddu-lavanda');
                        btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    });

                    // Activar pestaña seleccionada
                    this.classList.add('shared-tab-active', 'border-ddu-lavanda', 'text-ddu-lavanda');
                    this.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');

                    // Ocultar todos los paneles
                    document.querySelectorAll('.shared-tab-pane').forEach(pane => {
                        pane.style.display = 'none';
                    });

                    // Mostrar panel seleccionado
                    document.getElementById('shared-tab-' + tabId.replace('shared-', '')).style.display = 'block';
                });
            });
        }

        // Formatear tiempo en mm:ss
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Abrir detalles de reunión compartida
        async function openSharedMeetingDetails(meetingId, groupId, groupName) {
            currentSharedMeetingData = { meetingId, groupId, groupName };

            // Mostrar modal con loading
            const modal = document.getElementById('sharedMeetingDetailsModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            // Inicializar pestañas
            initSharedTabs();

            document.getElementById('sharedMeetingTitle').textContent = 'Cargando reunión...';
            document.getElementById('sharedMeetingSubtitle').textContent = `Grupo: ${groupName}`;

            // Mostrar loader, ocultar contenido
            document.getElementById('sharedMeetingLoader').style.display = 'flex';
            document.getElementById('sharedMeetingTabsContent').style.display = 'none';

            try {
                // Cargar DETALLES completos de la reunión compartida (resumen, puntos clave, transcripción)
                const response = await fetch(`/api/company-groups/${groupId}/shared-meetings/${meetingId}/details`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'No se pudieron cargar los detalles');
                }

                const data = result.data;
                currentSharedMeetingData.detailsData = data;

                // Actualizar título
                document.getElementById('sharedMeetingTitle').textContent = data.meeting_name || 'Reunión compartida';
                document.getElementById('sharedMeetingSubtitle').textContent = `Compartida por ${data.shared_by || 'usuario'} en grupo: ${groupName}`;

                // Mostrar permisos
                const permisos = data.permisos || {};
                let permisosHtml = `
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Permisos de esta reunión</h4>
                    <div class="flex flex-wrap gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${permisos.ver_audio ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${permisos.ver_audio ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'}"></path>
                            </svg>
                            Ver audio
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${permisos.ver_transcript ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${permisos.ver_transcript ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'}"></path>
                            </svg>
                            Ver transcripción
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${permisos.descargar ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${permisos.descargar ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'}"></path>
                            </svg>
                            Descargar
                        </span>
                    </div>
                `;
                document.getElementById('sharedMeetingPermisos').innerHTML = permisosHtml;

                // Mostrar resumen
                const summary = data.summary || 'No hay resumen disponible para esta reunión.';
                document.getElementById('sharedMeetingSummary').textContent = summary;

                // Mostrar audio si está disponible
                const audioContainer = document.getElementById('sharedMeetingAudioContainer');
                if (data.audio_base64 && permisos.ver_audio) {
                    const audioPlayer = document.getElementById('sharedMeetingAudioPlayer');
                    audioPlayer.src = `data:audio/mpeg;base64,${data.audio_base64}`;
                    audioContainer.style.display = 'block';
                } else {
                    audioContainer.style.display = 'none';
                }

                // Mostrar puntos clave
                const keyPointsList = document.getElementById('sharedMeetingKeyPoints');
                const keyPoints = data.key_points || [];
                if (keyPoints.length > 0) {
                    keyPointsList.innerHTML = keyPoints.map(point => `
                        <li class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-gray-700">${point}</span>
                        </li>
                    `).join('');
                } else {
                    keyPointsList.innerHTML = '<li class="text-gray-500 italic">No hay puntos clave disponibles</li>';
                }

                // Mostrar transcripción/segmentos
                const segmentsContainer = document.getElementById('sharedMeetingSegments');
                const segments = data.segments || [];
                if (segments.length > 0 && permisos.ver_transcript) {
                    segmentsContainer.innerHTML = segments.map(segment => `
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-ddu-lavanda">${segment.speaker || 'Hablante'}</span>
                                <span class="text-xs text-gray-400">${formatTime(segment.start || 0)} - ${formatTime(segment.end || 0)}</span>
                            </div>
                            <p class="text-gray-700 text-sm leading-relaxed">${segment.text || ''}</p>
                        </div>
                    `).join('');
                } else if (!permisos.ver_transcript) {
                    segmentsContainer.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <p>No tienes permiso para ver la transcripción de esta reunión</p>
                        </div>
                    `;
                } else {
                    segmentsContainer.innerHTML = '<p class="text-gray-500 italic text-center py-4">No hay transcripción disponible</p>';
                }

                // Ocultar loader, mostrar contenido
                document.getElementById('sharedMeetingLoader').style.display = 'none';
                document.getElementById('sharedMeetingTabsContent').style.display = 'flex';

                // Resetear a primera pestaña
                document.querySelectorAll('.shared-tab-button').forEach((btn, index) => {
                    if (index === 0) {
                        btn.classList.add('shared-tab-active', 'border-ddu-lavanda', 'text-ddu-lavanda');
                        btn.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        btn.classList.remove('shared-tab-active', 'border-ddu-lavanda', 'text-ddu-lavanda');
                        btn.classList.add('border-transparent', 'text-gray-500');
                    }
                });
                document.querySelectorAll('.shared-tab-pane').forEach((pane, index) => {
                    pane.style.display = index === 0 ? 'block' : 'none';
                });

            } catch (error) {
                console.error('Error cargando detalles:', error);
                document.getElementById('sharedMeetingLoader').innerHTML = `
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-red-600 font-medium">${error.message || 'Error al cargar los detalles'}</p>
                    </div>
                `;
            }
        }

        function closeSharedMeetingDetailsModal() {
            const modal = document.getElementById('sharedMeetingDetailsModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            currentSharedMeetingData = null;

            // Resetear estados
            document.getElementById('sharedMeetingLoader').style.display = 'flex';
            document.getElementById('sharedMeetingLoader').innerHTML = `
                <div class="animate-spin rounded-full h-8 w-8 border-4 border-ddu-lavanda border-t-transparent"></div>
                <span class="ml-3 text-gray-500">Cargando detalles de la reunión...</span>
            `;
            document.getElementById('sharedMeetingTabsContent').style.display = 'none';

            // Limpiar audio
            const audioPlayer = document.getElementById('sharedMeetingAudioPlayer');
            if (audioPlayer) {
                audioPlayer.pause();
                audioPlayer.src = '';
            }
        }

        // Cargar grupos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadGroups();
            loadCompanyMembers();
        });
    </script>
@endsection
