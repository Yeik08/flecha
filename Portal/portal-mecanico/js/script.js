document.addEventListener('DOMContentLoaded', function() {

    // =========================================================
    // 1. REFERENCIAS DOM
    // =========================================================
    const tablaPendientes = document.getElementById('tabla-pendientes-body');
    const formContainer = document.getElementById('contenedor-servicio');
    const formSalida = document.getElementById('form-salida');
    
    // Referencias Búsqueda Manual
    const btnBuscar = document.getElementById('btn-buscar-ticket');
    const inputTicket = document.getElementById('ticket-id');

    // Referencias Inputs Ocultos y Visuales
    const inputIdEntrada = document.getElementById('id_entrada_hidden');
    const inputIdCamion = document.getElementById('id_camion_hidden');
    const inputCamionInfo = document.getElementById('camion-info');
    const inputFolioInfo = document.getElementById('folio-info');
    const inputTipoMto = document.getElementById('tipo-mantenimiento'); // Agregado para que se llene también
    
    // Referencias Filtros (Para mostrar qué retirar)
    const inputFiltroAceiteActual = document.getElementById('filtro-aceite-actual');
    const inputFiltroCentActual = document.getElementById('filtro-centrifugo-actual');

    // =========================================================
    // 2. BANDEJA DE ENTRADA (Cargar Pendientes)
    // =========================================================
    async function cargarPendientes() {
        if (!tablaPendientes) return;
        
        try {
            const res = await fetch('php/listar_pendientes_mecanico.php');
            const data = await res.json();
            
            tablaPendientes.innerHTML = '';
            
            if (data.success && data.data.length > 0) {
                data.data.forEach(t => {
                    const tr = document.createElement('tr');
                    // Guardamos todo el objeto 't' en el botón para usarlo al dar click
                    // Usamos .replace para evitar errores con comillas simples en los datos
                    const jsonData = JSON.stringify(t).replace(/'/g, "&apos;");
                    
                    tr.innerHTML = `
                        <td><strong>${t.folio}</strong></td>
                        <td>${t.numero_economico}<br><small>${t.placas}</small></td>
                        <td>${t.tipo_mantenimiento_solicitado}</td>
                        <td>${new Date(t.fecha_ingreso).toLocaleDateString()}</td>
                        <td>
                            <button class="btn-primario btn-atender" data-json='${jsonData}'>
                                Atender
                            </button>
                        </td>
                    `;
                    tablaPendientes.appendChild(tr);
                });
            } else {
                tablaPendientes.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay vehículos esperando servicio.</td></tr>';
            }
        } catch (error) { 
            console.error("Error cargando pendientes:", error); 
            tablaPendientes.innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">Error de conexión.</td></tr>';
        }
    }
    
    // Cargar la tabla al iniciar la página
    cargarPendientes();

    // =========================================================
    // 3. SELECCIÓN DE TRABAJO (Click en "Atender" de la tabla)
    // =========================================================
    if (tablaPendientes) {
        tablaPendientes.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-atender')) {
                const data = JSON.parse(e.target.dataset.json);
                iniciarServicio(data);
            }
        });
    }

    // --- FUNCIÓN CENTRAL: PREPARAR EL FORMULARIO ---
    function iniciarServicio(data) {
        // A. Mostrar formulario con animación suave
        formContainer.style.display = 'block';
        formContainer.scrollIntoView({ behavior: 'smooth' });

        // B. Llenar IDs críticos (Ocultos)
        inputIdEntrada.value = data.id;
        // Nota: Asegúrate que tu PHP envíe 'id_camion' o usa 'id' si el objeto es el camión
        // En listar_pendientes.php hicimos JOIN, así que el ID principal suele ser el de la entrada
        // pero necesitamos el ID del camión. Ajustaremos esto si falla, por ahora asumimos que el backend está bien.
        // Si data.id es la entrada, el backend debe saber buscar el camión asociado, o enviamos el id_camion en el JSON.
        inputIdCamion.value = data.id_camion || data.id; 

        // C. Llenar Datos Visuales
        inputCamionInfo.value = `${data.numero_economico} - ${data.placas}`;
        if(inputFolioInfo) inputFolioInfo.value = data.folio;
        if(inputTipoMto) inputTipoMto.value = data.tipo_mantenimiento_solicitado;

        // D. Llenar Series de Filtros Actuales (Para que sepa qué retirar)
        if(inputFiltroAceiteActual) inputFiltroAceiteActual.value = data.serie_filtro_aceite_actual || 'Sin registro';
        if(inputFiltroCentActual) inputFiltroCentActual.value = data.serie_filtro_centrifugo_actual || 'Sin registro';
        
        // E. Limpiar búsqueda manual para evitar confusión
        if(inputTicket) inputTicket.value = "";
    }

    // =========================================================
    // 4. BÚSQUEDA MANUAL (Respaldo por Ticket)
    // =========================================================
    if (btnBuscar) {
        btnBuscar.addEventListener('click', async () => {
            const ticket = inputTicket.value.trim();
            if(!ticket) return alert("Escribe un folio.");

            btnBuscar.textContent = "Buscando...";
            btnBuscar.disabled = true;

            try {
                const res = await fetch(`php/buscar_ticket.php?ticket=${ticket}`);
                const data = await res.json();

                if(data.success) {
                    // Reutilizamos la función iniciarServicio para no duplicar código
                    iniciarServicio(data.data);
                    // Limpiamos errores previos si los hubiera
                } else {
                    alert("❌ " + data.message);
                    formSalida.reset();
                    formContainer.style.display = 'none';
                }
            } catch(e) {
                console.error(e);
                alert("Error de conexión al buscar.");
            } finally {
                btnBuscar.textContent = "🔍 Buscar";
                btnBuscar.disabled = false;
            }
        });
    }

    // =========================================================
    // 5. ENVÍO DEL FORMULARIO (FINALIZAR TRABAJO)
    // =========================================================
    if (formSalida) {
        formSalida.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validación básica: Debe haber una orden seleccionada
            if(!inputIdEntrada.value) {
                alert("⚠️ Error: No hay una orden seleccionada. Busca un ticket o selecciona de la lista.");
                return;
            }

            const btnSubmit = formSalida.querySelector('button[type="submit"]');
            const textoOriginal = btnSubmit.textContent;
            
            btnSubmit.disabled = true;
            btnSubmit.textContent = "Procesando...";

            const formData = new FormData(formSalida);

            try {
                // Llamada al backend REAL
                const res = await fetch('php/finalizar_mantenimiento.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Manejo robusto de respuesta
                const textoRespuesta = await res.text();
                let data;
                try {
                    data = JSON.parse(textoRespuesta);
                } catch (errJSON) {
                    console.error("Respuesta no JSON:", textoRespuesta);
                    throw new Error("El servidor devolvió una respuesta inválida.");
                }

                if (data.success) {
                    alert("🎉 ¡ÉXITO! " + data.message);
                    // Recargamos la página para actualizar la lista de pendientes y limpiar el formulario
                    location.reload(); 
                } else {
                    alert("⚠️ Error: " + data.message);
                }

            } catch(e) {
                console.error(e);
                alert("Error crítico de conexión o servidor.");
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = textoOriginal;
            }
        });
    }

    // =========================================================
    // 6. UTILIDADES
    // =========================================================
    
    // Función global para el botón "Cancelar" del HTML
    window.cancelarServicio = function() {
        formContainer.style.display = 'none';
        formSalida.reset();
        inputIdEntrada.value = ""; // Limpiar ID para seguridad
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

});