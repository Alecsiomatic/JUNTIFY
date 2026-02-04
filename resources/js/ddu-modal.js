/**
 * DDU Modal Handler
 * Maneja la funcionalidad del modal de acceso denegado DDU
 */

class DduModalHandler {
    constructor() {
        this.modal = null;
        this.init();
    }

    init() {
        // Buscar modal existente
        this.modal = document.getElementById('ddu-access-modal');

        if (this.modal) {
            console.log('DDU Modal: Modal encontrado, vinculando eventos...');
            this.bindEvents();
        } else {
            console.log('DDU Modal: No se encontró modal en el DOM');
        }
    }    bindEvents() {
        // Cerrar modal con Escape
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeModal();
            }
        });

        // Cerrar modal al hacer click fuera
        this.modal.addEventListener('click', (event) => {
            if (event.target === this.modal) {
                this.closeModal();
            }
        });

        // Buscar botón de cerrar de múltiples formas
        const closeButton = this.modal.querySelector('.ddu-modal-button') ||
                           this.modal.querySelector('[onclick="closeDduModal()"]') ||
                           this.modal.querySelector('button');

        if (closeButton) {
            // Limpiar cualquier evento onclick anterior
            closeButton.removeAttribute('onclick');
            // Agregar nuevo evento de click
            closeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeModal();
            });

            console.log('DDU Modal: Botón de cerrar vinculado correctamente');
        } else {
            console.warn('DDU Modal: No se encontró botón de cerrar');
        }
    }

    closeModal() {
        if (!this.modal) return;

        // Agregar clase de cierre para transición
        this.modal.classList.add('ddu-modal-closing');

        // Aplicar estilos de cierre
        this.modal.style.opacity = '0';
        const content = this.modal.querySelector('.ddu-modal-content, .bg-white');
        if (content) {
            content.style.transform = 'scale(0.95)';
        }

        // Remover modal después de la transición
        setTimeout(() => {
            if (this.modal && this.modal.parentNode) {
                this.modal.remove();
            }
        }, 200);
    }

    // Método público para cerrar modal (compatible con onclick anterior)
    static closeModal() {
        const instance = window.dduModalHandler;
        if (instance) {
            instance.closeModal();
        }
    }
}

// Función global para compatibilidad con onclick existente
function closeDduModal() {
    DduModalHandler.closeModal();
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.dduModalHandler = new DduModalHandler();
});

// También inicializar inmediatamente si el DOM ya está cargado
if (document.readyState === 'loading') {
    // DOM aún cargando, usar DOMContentLoaded
} else {
    // DOM ya cargado
    window.dduModalHandler = new DduModalHandler();
}
