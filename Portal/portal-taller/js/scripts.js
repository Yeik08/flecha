// Este script maneja la lógica para abrir y cerrar la ventana modal.

// 1. Esperar a que todo el contenido del DOM esté cargado.
document.addEventListener('DOMContentLoaded', function() {

    // 2. Obtener referencias a los elementos del DOM que necesitamos.
    const modal = document.getElementById('modal-registro');
    const btnAbrirModal = document.getElementById('btn-registrar-entrada');
    const btnCerrarModal = document.getElementById('cerrar-modal');
    const form = document.getElementById('form-registro');
    const puntualidadSelect = document.getElementById('puntualidad');

    // 3. Función para abrir el modal.
    // Simplemente cambia el estilo 'display' de 'none' a 'block'.
    function abrirModal() {
        modal.style.display = 'block';
    }

    // 4. Función para cerrar el modal.
    // Cambia el estilo 'display' de 'block' a 'none'.
    function cerrarModal() {
        modal.style.display = 'none';
    }

    // 5. Asignar los eventos a los botones.
    btnAbrirModal.addEventListener('click', abrirModal);
    btnCerrarModal.addEventListener('click', cerrarModal);

    // 6. Cerrar el modal si el usuario hace clic fuera del contenido del modal.
    window.addEventListener('click', function(event) {
        // Si el objetivo del clic es el fondo del modal...
        if (event.target == modal) {
            cerrarModal();
        }
    });

    // 7. Manejar el envío del formulario.
    form.addEventListener('submit', function(event) {
        // Prevenir el comportamiento por defecto (recargar la página).
        event.preventDefault(); 
        
        // Aquí es donde enviaremos los datos al backend en el futuro.
        // Por ahora, solo mostramos una alerta y cerramos el modal.
        const ticketId = 'TK-' + Date.now() + Math.floor(Math.random() * 100);

        alert(`Solicitud generada con éxito.\n\nNúmero de Ticket: ${ticketId}\n\nEl mecánico debe presentar este ticket y el filtro usado para recibir el nuevo.`);
        
        form.reset(); // Limpia el formulario
        cerrarModal(); // Cierra el modal
    });

});