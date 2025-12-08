document.addEventListener('DOMContentLoaded', function() {

    const btnBuscar = document.getElementById('btn-buscar-ticket');
    const formIntercambio = document.getElementById('form-intercambio');
    const inputTicket = document.getElementById('ticket-id');

    // BUSCAR TICKET
    if(btnBuscar) {
        btnBuscar.addEventListener('click', async () => {
            const ticket = inputTicket.value.trim();
            if (!ticket) return alert("Ingresa un folio.");

            btnBuscar.textContent = "Buscando...";
            btnBuscar.disabled = true;

            try {
                const res = await fetch(`php/buscar_datos_intercambio.php?ticket=${ticket}`);
                const data = await res.json();

                if (data.success) {
                    const d = data.data;
                    document.getElementById('id_entrada_hidden').value = d.id;
                    document.getElementById('id_camion_hidden').value = d.id_camion;
                    document.getElementById('info-unidad').value = `${d.numero_economico} - ${d.placas}`;
                    document.getElementById('info-mecanico').value = d.nombre_mecanico || "Sin Asignar";
                    document.getElementById('info-filtro-actual').value = d.serie_filtro_aceite_actual;
                    
                    alert("✅ Ticket validado. Procede a escanear las piezas.");
                } else {
                    alert("❌ " + data.message);
                    formIntercambio.reset();
                }
            } catch (e) {
                console.error(e);
                alert("Error de conexión.");
            } finally {
                btnBuscar.textContent = "🔍 Buscar";
                btnBuscar.disabled = false;
            }
        });
    }

    // REGISTRAR SALIDA
    if(formIntercambio) {
        formIntercambio.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validación visual rápida
            const actual = document.getElementById('info-filtro-actual').value.trim().toUpperCase();
            const escaneado = formIntercambio.querySelector('input[name="filtro_viejo_serie"]').value.trim().toUpperCase();
            
            if (actual && actual !== escaneado) {
                alert("⛔ ALERTA: El filtro usado escaneado NO ES el que trae el camión.\n\nEsperado: " + actual + "\nEscaneado: " + escaneado);
                return;
            }

            if(!confirm("¿Confirmas la entrega de este material? Se descontará del inventario.")) return;

            const formData = new FormData(formIntercambio);
            
            try {
                const res = await fetch('php/procesar_salida_material.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    alert("✅ " + data.message);
                    formIntercambio.reset();
                    document.getElementById('info-unidad').value = "";
                } else {
                    alert("❌ Error: " + data.message);
                }
            } catch (e) {
                alert("Error de conexión.");
            }
        });
    }
});