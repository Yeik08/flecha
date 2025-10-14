// Espera a que todo el contenido del HTML esté cargado antes de ejecutar el script
document.addEventListener('DOMContentLoaded', function() {

    // --- LÓGICA PARA EL MODAL DE PERSONAL ---

    // Seleccionamos los elementos del DOM que necesitamos manipular
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const modalOverlay = document.getElementById('modal-personal');
    const formPersonal = document.getElementById('form-alta-personal');

    // Función para mostrar el modal
    function abrirModal() {
        // Le quitamos la clase 'oculto' para que el CSS lo haga visible
        if (modalOverlay) {
            modalOverlay.classList.remove('oculto');
        }
    }

    // Función para ocultar el modal
    function cerrarModal() {
        // Le añadimos la clase 'oculto' para que el CSS lo esconda
        if (modalOverlay) {
            modalOverlay.classList.add('oculto');
        }
    }

    // --- EVENT LISTENERS ---

    // 1. Cuando el usuario hace clic en el botón "+ Agregar Personal"
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModal);
    }

    // 2. Cuando el usuario hace clic en la 'X' para cerrar
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
    }
    
    // 3. (Opcional) Cerrar el modal si el usuario hace clic fuera del formulario (en el fondo oscuro)
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(evento) {
            // Si el clic fue directamente en el fondo y no en el contenido del modal
            if (evento.target === modalOverlay) {
                cerrarModal();
            }
        });
    }

    // 4. Manejar el envío del formulario
    if (formPersonal) {
        formPersonal.addEventListener('submit', function(evento) {
            // Prevenimos que la página se recargue, que es el comportamiento por defecto de un formulario
            evento.preventDefault(); 
            
            // Recolectamos los datos del formulario
            const datos = {
                idEmpleado: document.getElementById('id_empleado').value,
                nombre: document.getElementById('nombre').value,
                rol: document.getElementById('rol').value,
                fechaIngreso: document.getElementById('fecha_ingreso').value,
            };

            // Por ahora, solo mostraremos los datos en la consola para verificar.
            // ¡Aquí es donde en el futuro enviaremos los datos al backend (Python)!
            console.log('Datos del formulario a enviar:', datos);
            
            alert(`Empleado "${datos.nombre}" registrado (simulación).`);
            
            // Limpiamos el formulario y cerramos el modal después de enviar
            formPersonal.reset();
            cerrarModal();
        });
    }

});