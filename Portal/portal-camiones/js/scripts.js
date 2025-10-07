document.addEventListener('DOMContentLoaded', function() {

    // --- LÓGICA PARA EL MENÚ DROPDOWN ---
    document.addEventListener('click', function(e) {
        // Si el clic no es en un toggle, cierra todos los submenús
        if (!e.target.matches('.dropdown-toggle')) {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            return;
        }

        const parent = e.target.closest('.dropdown');
        
        // Cierra otros submenús que puedan estar abiertos
        document.querySelectorAll('.dropdown.active').forEach(dropdown => {
            if (dropdown !== parent) {
                dropdown.classList.remove('active');
            }
        });

        // Alterna la clase 'active' solo en el dropdown clickeado
        parent.classList.toggle('active');
    });

    // --- LÓGICA PARA EL MODAL DE ALTA DE CAMIÓN ---
    const modal = document.getElementById('modal-formulario');
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const btnCancelarModal = document.getElementById('btn-cancelar-modal');

    // Función para abrir el modal
    function abrirModal() {
        if (modal) {
            modal.classList.remove('oculto');
        }
    }

    // Función para cerrar el modal
    function cerrarModal() {
        if (modal) {
            modal.classList.add('oculto');
        }
    }

    // Asignar eventos a los botones
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModal);
    }
    
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
    }

    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', cerrarModal);
    }

    // Opcional: Cerrar el modal si se hace clic en el fondo oscuro
    if (modal) {
        modal.addEventListener('click', function(e) {
            // Si el clic fue directamente en el fondo (overlay)
            if (e.target === modal) {
                cerrarModal();
            }
        });
    }});