@extends('layouts.dashboard')

@section('page-title', 'Administrar Miembros')
@section('page-description', 'Gestionar usuarios y permisos del sistema DDU')

@section('content')
<div class="space-y-6 fade-in">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Administrar Miembros</h2>
        <p class="text-gray-600 mt-1">Gestiona usuarios, roles y permisos del sistema DDU</p>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="ddu-card">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar miembro</label>
                    <input type="text"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Nombre o email..."
                           id="searchInput">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            id="roleFilter">
                        <option value="">Todos los roles</option>
                        <option value="administrador">Administrador</option>
                        <option value="administracion">Administración</option>
                        <option value="ventas">Ventas</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permiso</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            id="permissionFilter">
                        <option value="">Todos los permisos</option>
                        <option value="colaborador">Colaborador</option>
                        <option value="lector">Lector</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            id="statusFilter">
                        <option value="">Todos</option>
                        <option value="active">Activos</option>
                        <option value="inactive">Inactivos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="stat-card primary">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total Miembros</div>
                </div>
                <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number text-green-600">{{ $stats['active'] }}</div>
                    <div class="stat-label text-gray-600">Activos</div>
                </div>
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number text-amber-600">{{ $stats['admins'] }}</div>
                    <div class="stat-label text-gray-600">Administradores</div>
                </div>
                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-number text-blue-600">{{ $stats['collaborators'] }}</div>
                    <div class="stat-label text-gray-600">Colaboradores</div>
                </div>
                <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Buscar y Añadir Usuarios de Juntify -->
    <div class="ddu-card">
        <div class="ddu-card-header">
            <div>
                <h3 class="ddu-card-title">Añadir Usuarios desde Juntify</h3>
                <p class="ddu-card-subtitle">Busca usuarios de Juntify para añadirlos como integrantes de DDU</p>
            </div>
        </div>

        <div class="p-6">
            <!-- Sección de Mis Contactos -->
            @if(isset($contacts) && count($contacts) > 0)
            <div class="mb-8">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                    <svg class="w-5 h-5 text-ddu-aqua mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Mis Contactos ({{ count($contacts) }})
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($contacts as $contact)
                    <div class="flex items-center justify-between p-4 rounded-lg transition-colors border 
                        {{ $contact['is_added_to_empresa'] ? 'bg-green-50 border-green-300' : 'bg-white border-gray-200 hover:bg-gray-50' }}">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-ddu-aqua to-ddu-lavanda flex items-center justify-center">
                                <span class="text-white font-semibold">
                                    {{ strtoupper(substr($contact['name'], 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900">{{ $contact['name'] }}</p>
                                    @if($contact['is_added_to_empresa'])
                                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                            ✓ Agregado
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-600">{{ $contact['email'] }}</p>
                            </div>
                        </div>
                        @if(!$contact['is_added_to_empresa'])
                        <div class="flex items-center gap-2">
                            <select 
                                id="contact-rol-{{ $contact['id'] }}"
                                class="px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-ddu-aqua bg-white"
                            >
                                <option value="miembro">Miembro</option>
                                <option value="administrador">Admin</option>
                            </select>
                            <button 
                                onclick="addMemberFromJuntify('{{ $contact['id'] }}', '{{ addslashes($contact['name']) }}', 'contact-rol-{{ $contact['id'] }}')"
                                class="px-3 py-1 bg-ddu-aqua text-white rounded hover:bg-opacity-90 transition-colors text-xs font-medium"
                            >
                                ➕ Añadir
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200"></div>
            </div>
            @elseif(isset($contacts))
            <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-gray-600 text-sm">No tienes contactos en Juntify</p>
                </div>
            </div>
            @endif

            <!-- Formulario de búsqueda -->
            <form action="{{ route('admin.members.index') }}" method="GET" class="mb-6">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search ?? '' }}"
                            placeholder="Buscar por nombre o email en Juntify..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ddu-lavanda focus:border-transparent"
                        >
                    </div>
                    <button type="submit" class="px-6 py-2 bg-ddu-lavanda text-white rounded-lg hover:bg-opacity-90 transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Buscar en Juntify
                    </button>
                </div>
            </form>

            @if(isset($error))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-red-800">{{ $error }}</p>
                </div>
            </div>
            @endif

            <!-- Lista de usuarios disponibles de Juntify -->
            @if(isset($availableUsers) && count($availableUsers) > 0)
            <div class="space-y-3">
                <h4 class="font-semibold text-gray-700 mb-3">
                    <svg class="w-5 h-5 inline text-ddu-lavanda mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Usuarios disponibles en Juntify ({{ count($availableUsers) }})
                </h4>
                
                @foreach($availableUsers as $user)
                <div class="flex items-center justify-between p-4 rounded-lg transition-colors border 
                    {{ isset($user['is_added']) && $user['is_added'] ? 'bg-green-50 border-green-300' : 'bg-gray-50 border-gray-200 hover:bg-gray-100' }}">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-ddu-lavanda to-ddu-aqua flex items-center justify-center">
                            <span class="text-white font-semibold text-lg">
                                {{ strtoupper(substr($user['name'] ?? $user['username'], 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-900 text-lg">{{ $user['name'] ?? $user['username'] }}</p>
                                @if(isset($user['is_added']) && $user['is_added'])
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                        ✓ Ya agregado
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">📧 {{ $user['email'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if(!isset($user['is_added']) || !$user['is_added'])
                            <select 
                                id="rol-{{ $user['id'] }}"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-ddu-lavanda bg-white"
                            >
                                <option value="miembro">Miembro</option>
                                <option value="administrador">Administrador</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button 
                                onclick="addMemberFromJuntify('{{ $user['id'] }}', '{{ addslashes($user['name'] ?? $user['username']) }}')"
                                class="px-6 py-2 bg-ddu-lavanda text-white rounded-lg hover:bg-opacity-90 transition-colors text-sm font-medium shadow-sm"
                            >
                                ➕ Añadir a DDU
                            </button>
                        @else
                            <span class="px-4 py-2 text-sm text-gray-600 italic">
                                Este usuario ya es miembro de DDU
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @elseif(isset($search) && $search)
            <div class="text-center py-12 bg-yellow-50 rounded-lg border border-yellow-200">
                <svg class="mx-auto h-12 w-12 text-yellow-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-gray-700 text-lg font-medium">No se encontraron usuarios con "{{ $search }}"</p>
                <p class="text-gray-600 text-sm mt-2">Intenta con otro término de búsqueda</p>
            </div>
            @else
            <div class="text-center py-12 bg-blue-50 rounded-lg border border-blue-200">
                <svg class="mx-auto h-12 w-12 text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-gray-700 text-lg font-medium">Busca usuarios en Juntify</p>
                <p class="text-gray-600 text-sm mt-2">Escribe un nombre, username o email en el buscador para encontrar usuarios disponibles</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Lista de miembros actuales de DDU -->
    <div class="ddu-card">
        <div class="ddu-card-header">
            <div>
                <h3 class="ddu-card-title">Miembros del Sistema</h3>
                <p class="ddu-card-subtitle">Lista completa de usuarios registrados</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="ddu-table">
                <thead>
                    <tr>
                        <th class="text-left">Usuario</th>
                        <th class="text-left">Email</th>
                        <th class="text-left">Rol</th>
                        <th class="text-left">Fecha Agregado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="membersTableBody">
                    @forelse($members as $member)
                    <tr data-member-id="{{ $member->id }}">
                        <td>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-ddu-lavanda to-ddu-aqua rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $member->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-gray-900">{{ $member->email }}</td>
                        <td>
                            <span class="px-3 py-1 text-xs font-medium rounded-full
                                @if($member->role === 'Dueño')
                                    bg-gradient-to-r from-yellow-100 to-amber-100 text-amber-900 border border-amber-300
                                @elseif(in_array($member->role, ['administracion', 'administrador', 'admin']))
                                    bg-red-100 text-red-800
                                @else
                                    bg-purple-100 text-purple-800
                                @endif">
                                @if($member->role === 'Dueño')
                                    👑 Dueño
                                @elseif($member->role === 'administrador' || $member->role === 'admin')
                                    Administrador
                                @elseif($member->role === 'administracion')
                                    Administración
                                @else
                                    {{ ucfirst($member->role) }}
                                @endif
                            </span>
                        </td>
                        <td class="text-gray-500">{{ \Carbon\Carbon::parse($member->fecha_agregado)->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex justify-center space-x-2">
                                @if($member->role !== 'Dueño' && !$member->is_owner)
                                    <!-- Dropdown de rol -->
                                    <select 
                                        id="member-rol-{{ $member->id }}"
                                        class="px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-ddu-lavanda bg-white"
                                        onchange="updateMemberRole('{{ $member->id }}', '{{ $member->name }}')"
                                    >
                                        <option value="miembro" {{ $member->role === 'miembro' ? 'selected' : '' }}>Miembro</option>
                                        <option value="admin" {{ in_array($member->role, ['admin', 'administrador']) ? 'selected' : '' }}>Admin</option>
                                        <option value="colaborador" {{ $member->role === 'colaborador' ? 'selected' : '' }}>Colaborador</option>
                                    </select>
                                    
                                    <!-- Botón eliminar -->
                                    <button class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors" 
                                            onclick="removeMemberFromDDU('{{ $member->id }}', '{{ $member->name }}')" 
                                            title="Eliminar de DDU">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-xs text-gray-500 italic">Usuario principal</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">No hay miembros registrados</h3>
                                <p class="text-gray-600">Los miembros aparecerán aquí una vez que sean añadidos desde Juntify</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>



<script>

// Filtros en tiempo real (para implementar más adelante)
document.getElementById('searchInput')?.addEventListener('input', function() {
    console.log('Buscar en tabla:', this.value);
});

document.getElementById('roleFilter')?.addEventListener('change', function() {
    console.log('Filtrar por rol:', this.value);
});

document.getElementById('permissionFilter')?.addEventListener('change', function() {
    console.log('Filtrar por permiso:', this.value);
});

document.getElementById('statusFilter')?.addEventListener('change', function() {
    console.log('Filtrar por estado:', this.value);
});

// Función para añadir miembro desde Juntify
function addMemberFromJuntify(userId, userName, rolInputId = null) {
    const rol = rolInputId ? 
        document.getElementById(rolInputId).value : 
        document.getElementById(`rol-${userId}`).value;
    
    if (!confirm(`¿Añadir a ${userName} como ${rol} de DDU?`)) {
        return;
    }

    // Deshabilitar el botón mientras se procesa
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="animate-pulse">⏳ Agregando...</span>';

    fetch('{{ route("admin.members.add") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_id: userId,
            rol: rol
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✓ ${userName} ha sido añadido exitosamente a DDU como ${rol}`);
            location.reload();
        } else {
            alert(data.message || 'Error al añadir el usuario');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al añadir el usuario a DDU');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// Función para actualizar rol de miembro
function updateMemberRole(userId, userName) {
    const select = document.getElementById(`member-rol-${userId}`);
    const newRole = select.value;
    const oldRole = select.getAttribute('data-old-role') || 'miembro';
    
    if (oldRole === newRole) {
        return; // No cambió nada
    }
    
    if (!confirm(`¿Cambiar el rol de ${userName} a ${newRole}?`)) {
        select.value = oldRole;
        return;
    }

    // Guardar el rol anterior por si falla
    select.setAttribute('data-old-role', oldRole);
    select.disabled = true;

    fetch(`{{ url('admin/members') }}/${userId}/role`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            rol: newRole
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✓ Rol de ${userName} actualizado a ${newRole}`);
            select.setAttribute('data-old-role', newRole);
            select.disabled = false;
            location.reload();
        } else {
            alert(data.message || 'Error al actualizar el rol');
            select.value = oldRole;
            select.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el rol del miembro');
        select.value = oldRole;
        select.disabled = false;
    });
}

// Función para eliminar miembro de DDU
function removeMemberFromDDU(userId, userName) {
    if (!confirm(`¿Estás seguro de eliminar a ${userName} de DDU?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }

    fetch(`{{ url('admin/members') }}/${userId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`✓ ${userName} ha sido eliminado de DDU`);
            location.reload();
        } else {
            alert(data.message || 'Error al eliminar el miembro');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar el miembro de DDU');
    });
}

</script>
@endsection
