document.addEventListener('DOMContentLoaded', function() {

    const btnBuscarTicket = document.getElementById('btn-buscar-ticket');
    const formIntercambio = document.getElementById('form-intercambio');

    // Evento para el botón "Buscar" ticket
    btnBuscarTicket.addEventListener('click', function() {
        const ticketId = document.getElementById('ticket-id').value;

        if (!ticketId) {
            alert('Por favor, ingrese un número de ticket.');
            return;
        }

        // --- SIMULACIÓN DE LLAMADA AL BACKEND ---
        // En una aplicación real, aquí harías una llamada (fetch) a tu API:
        // fetch(`/api/tickets/${ticketId}`)
        
        alert(`Buscando información para el ticket: ${ticketId}...`);

        // Simulamos una respuesta exitosa del backend
       setTimeout(() => {
    const camionIdInput = document.getElementById('camion-id');
    const filtroViejoMarcaInput = document.getElementById('filtro-viejo-marca');
    const mecanicoIdInput = document.getElementById('mecanico-id');
    const almacenSelect = document.getElementById('almacen');

    // Llenamos los campos con datos simulados del ticket
    camionIdInput.value = 'ECO-112';
    mecanicoIdInput.value = 'MEC-045';
    filtroViejoMarcaInput.value = 'PATITO1';
    almacenSelect.value = 'poniente'; // Puede ser 'magdalena' u 'otro'

    alert('Información del ticket cargada.');
}, 1000);

    // Evento para manejar el envío del formulario
    formIntercambio.addEventListener('submit', function(event) {
        event.preventDefault(); // Evitamos que la página se recargue

        // Aquí iría la lógica para enviar los datos y las fotos al backend.
        // Se usaría FormData para poder incluir los archivos de las fotos.
        
        // const formData = new FormData(formIntercambio);
        // fetch('/api/inventory/exchange', { method: 'POST', body: formData });
        
        alert('Registro de intercambio enviado con éxito. La salida de inventario ha sido registrada.');

        // Limpiamos el formulario después del envío
        formIntercambio.reset();
    });
    const inputSerieNuevo = document.getElementById("filtro-nuevo-serie");
    const inputMarcaNuevo = document.getElementById("filtro-nuevo-marca");

    inputSerieNuevo.addEventListener("input", function () {
        const serie = inputSerieNuevo.value.trim().toUpperCase(); // Aseguramos mayúsculas

        // Detectar marca por prefijo del número de serie
        if (serie.startsWith("FF")) {
            inputMarcaNuevo.value = "Fleetguard";
        } else if (serie.startsWith("DC")) {
            inputMarcaNuevo.value = "Donaldson";
        } else if (serie.startsWith("MAH")) {
            inputMarcaNuevo.value = "Mahle";
        } else if (serie.startsWith("FR")) {
            inputMarcaNuevo.value = "Fram";
        } else {
            inputMarcaNuevo.value = "Desconocida";
        }
    });
});