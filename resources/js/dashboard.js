/**
 * Dashboard JavaScript - DDU Panel
 * Funcionalidades interactivas para el dashboard
 */

class DashboardManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupSidebarToggle();
        this.setupNotifications();
        this.setupResponsiveLayout();
        this.setupFormValidation();

        console.log('DDU Dashboard inicializado correctamente');
    }

    /**
     * Toggle del sidebar en móvil
     */
    setupSidebarToggle() {
        const toggleButton = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('nav');
        const overlay = document.getElementById('sidebar-overlay');

        if (toggleButton && sidebar) {
            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                if (overlay) {
                    overlay.classList.toggle('hidden');
                }
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.add('hidden');
            });
        }
    }

    /**
     * Sistema de notificaciones
     */
    setupNotifications() {
        // Auto-hide de notificaciones después de 5 segundos
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                this.hideNotification(notification);
            }, 5000);
        });

        // Botones de cerrar notificación
        document.addEventListener('click', (e) => {
            if (e.target.matches('.notification-close')) {
                const notification = e.target.closest('.notification');
                if (notification) {
                    this.hideNotification(notification);
                }
            }
        });
    }

    hideNotification(notification) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }

    /**
     * Layout responsivo
     */
    setupResponsiveLayout() {
        const handleResize = () => {
            const isMobile = window.innerWidth < 768;
            const sidebar = document.querySelector('nav');

            if (!isMobile && sidebar) {
                sidebar.classList.remove('open');
            }
        };

        window.addEventListener('resize', handleResize);
        handleResize(); // Ejecutar al cargar
    }

    /**
     * Validación de formularios
     */
    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');

        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });

            // Validación en tiempo real
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
            });
        });
    }

    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        const isRequired = field.hasAttribute('required');
        let isValid = true;
        let errorMessage = '';

        // Limpiar errores previos
        this.clearFieldError(field);

        // Validar campo requerido
        if (isRequired && !value) {
            isValid = false;
            errorMessage = 'Este campo es requerido';
        }

        // Validar email
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Ingrese un email válido';
            }
        }

        // Mostrar error si hay
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        }

        return isValid;
    }

    showFieldError(field, message) {
        field.classList.add('border-red-500', 'bg-red-50');
        field.classList.remove('border-gray-300');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-600 text-sm mt-1 field-error';
        errorDiv.textContent = message;

        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500', 'bg-red-50');
        field.classList.add('border-gray-300');

        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    /**
     * Utilidades públicas
     */
    showLoading(element, text = 'Cargando...') {
        if (element) {
            element.innerHTML = `
                <div class="flex items-center justify-center space-x-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-ddu-lavanda"></div>
                    <span>${text}</span>
                </div>
            `;
            element.disabled = true;
        }
    }

    hideLoading(element, originalText) {
        if (element) {
            element.innerHTML = originalText;
            element.disabled = false;
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform translate-x-0 opacity-100 transition-all duration-300`;

        const bgColor = {
            'success': 'bg-green-500',
            'error': 'bg-red-500',
            'warning': 'bg-yellow-500',
            'info': 'bg-blue-500'
        }[type] || 'bg-blue-500';

        notification.className += ` ${bgColor} text-white`;

        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium">${message}</span>
                <button class="notification-close ml-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        if (duration > 0) {
            setTimeout(() => {
                this.hideNotification(notification);
            }, duration);
        }
    }
}

// Clase para manejo de búsqueda de usuarios
class UserSearchManager {
    constructor(searchInputId, resultsContainerId) {
        this.searchInput = document.getElementById(searchInputId);
        this.resultsContainer = document.getElementById(resultsContainerId);
        this.debounceTimer = null;

        if (this.searchInput && this.resultsContainer) {
            this.init();
        }
    }

    init() {
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.searchUsers(e.target.value);
            }, 300);
        });
    }

    async searchUsers(query) {
        if (query.length < 2) {
            this.resultsContainer.innerHTML = '';
            return;
        }

        try {
            this.showSearchLoading();

            const response = await fetch(`/admin/members/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();
            this.displayResults(data.users || []);

        } catch (error) {
            console.error('Error buscando usuarios:', error);
            this.resultsContainer.innerHTML = '<p class="text-red-600 text-sm">Error al buscar usuarios</p>';
        }
    }

    showSearchLoading() {
        this.resultsContainer.innerHTML = `
            <div class="flex items-center justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-ddu-lavanda"></div>
                <span class="ml-2 text-sm text-gray-600">Buscando...</span>
            </div>
        `;
    }

    displayResults(users) {
        if (users.length === 0) {
            this.resultsContainer.innerHTML = '<p class="text-gray-500 text-sm py-4">No se encontraron usuarios</p>';
            return;
        }

        const resultsHtml = users.map(user => `
            <div class="user-result p-3 border rounded-lg hover:bg-gray-50 cursor-pointer" data-user-id="${user.id}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">${user.name || 'Sin nombre'}</p>
                        <p class="text-sm text-gray-500">${user.email}</p>
                        ${user.is_member ? '<span class="text-xs text-green-600">Ya es miembro</span>' : ''}
                    </div>
                    ${!user.is_member ? `
                        <button class="btn-ddu text-xs px-3 py-1 add-member-btn" data-user-id="${user.id}">
                            Agregar
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');

        this.resultsContainer.innerHTML = resultsHtml;

        // Agregar eventos a los botones
        this.resultsContainer.addEventListener('click', this.handleResultClick.bind(this));
    }

    handleResultClick(e) {
        if (e.target.matches('.add-member-btn')) {
            const userId = e.target.dataset.userId;
            this.addMember(userId);
        }
    }

    async addMember(userId) {
        // Esta función será implementada en el controlador específico
        console.log('Agregar miembro:', userId);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardManager = new DashboardManager();

    // Inicializar búsqueda de usuarios si existe
    if (document.getElementById('user-search')) {
        window.userSearchManager = new UserSearchManager('user-search', 'search-results');
    }
});

// Hacer disponible globalmente
window.DashboardManager = DashboardManager;
window.UserSearchManager = UserSearchManager;
