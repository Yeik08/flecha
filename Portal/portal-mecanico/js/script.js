document.addEventListener('DOMContentLoaded', function() {

    const btnBuscarTicket = document.getElementById('btn-buscar-ticket');
    const formSalida = document.getElementById('form-salida');
    const fechaSalidaInput = document.getElementById('fecha-salida');

    // Función para establecer la fecha y hora actuales en el campo de salida
    function setFechaHoraActual() {
        const now = new Date();
        // Ajustamos la zona horaria para que el formato sea correcto para datetime-local
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        // Formateamos a 'YYYY-MM-DDTHH:mm'
        fechaSalidaInput.value = now.toISOString().slice(0, 16);
    }

    // Evento para el botón "Cargar Datos"
    btnBuscarTicket.addEventListener('click', function() {
        const ticketId = document.getElementById('ticket-id').value;
        if (!ticketId) {
            alert('Por favor, ingrese un número de ticket.');
            return;
        }

        // --- SIMULACIÓN DE LLAMADA AL BACKEND ---
        alert(`Buscando datos del mantenimiento para el ticket: ${ticketId}...`);

        setTimeout(() => {
            document.getElementById('camion-id').value = 'ECO-080';
            document.getElementById('tipo-mantenimiento').value = 'Cambio de Filtro';
            
            // Pre-llenamos la fecha y hora de salida con el momento actual
            setFechaHoraActual();

            alert('Datos cargados. Por favor, complete el registro de salida.');
        }, 1000);
    });

    // Evento para el envío del formulario
    formSalida.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Aquí iría la lógica para enviar los datos y las fotos al backend
        // usando FormData para incluir los archivos.
        alert('Registro de salida enviado. El camión ha sido liberado.');
        
        formSalida.reset();
    });
});