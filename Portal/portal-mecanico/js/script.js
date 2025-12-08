/*
 * Portal/portal-mecanico/js/script.js
 * VERSIÓN FINAL: Validaciones de Filtros, Mezcla de Aceites y Metadatos EXIF.
 */

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
    const inputTipoMto = document.getElementById('tipo-mantenimiento'); 
    
    // Referencias Filtros
    const inputFiltroAceiteActual = document.getElementById('filtro-aceite-actual');
    const inputFiltroCentActual = document.getElementById('filtro-centrifugo-actual');
    
    // Referencias Nuevos Inputs (Para validaciones)
    const inputNuevoFiltroAceite = document.querySelector('input[name="nuevo_filtro_aceite"]');
    const inputNuevoFiltroCentrifugo = document.querySelector('input[name="nuevo_filtro_centrifugo"]');
    const inputCubeta1 = document.querySelector('input[name="serie_cubeta_1"]');
    const inputCubeta2 = document.querySelector('input[name="serie_cubeta_2"]');

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
                    // Escapamos comillas simples para evitar errores en el JSON dentro del HTML
                    const jsonData = JSON.stringify(t).replace(/'/g, "&apos;");
                    
                    // LÓGICA DE BOTONES INTELIGENTES
                    let botonHTML = '';
                    let estatusHTML = '';
                    let responsableHTML = '';

                    if (t.estatus_entrada === 'Recibido') {
                        // Botón INICIAR
                        estatusHTML = '<span style="color:#f39c12; font-weight:bold;">⏳ En Espera</span>';
                        botonHTML = `<button class="btn-primario btn-iniciar" style="background-color:#3498db;" data-id="${t.id}">▶ Iniciar</button>`;
                    } else if (t.estatus_entrada === 'En Proceso') {
                        // Botón FINALIZAR
                        estatusHTML = '<span style="color:#3498db; font-weight:bold;">🔨 Trabajando</span>';
                        botonHTML = `<button class="btn-primario btn-finalizar" data-json='${jsonData}'>✅ Finalizar</button>`;
                    }

                    // Mostrar responsable si existe
                    if (t.nombre_responsable) {
                        responsableHTML = `<br><small style="color:#555;">👷 Asignado: <strong>${t.nombre_responsable}</strong></small>`;
                    }

                    tr.innerHTML = `
                        <td><strong>${t.folio}</strong></td>
                        <td>${t.numero_economico}<br><small>${t.placas}</small></td>
                        <td>${t.tipo_mantenimiento_solicitado}<br>${estatusHTML}${responsableHTML}</td>
                        <td>${new Date(t.fecha_ingreso).toLocaleDateString()} ${new Date(t.fecha_ingreso).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</td>
                        <td>${botonHTML}</td>
                    `;
                    tablaPendientes.appendChild(tr);
                });
            } else {
                tablaPendientes.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay trabajos pendientes.</td></tr>';
            }
        } catch (error) { 
            console.error(error); 
            tablaPendientes.innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">Error de conexión.</td></tr>';
        }
    }
    
    cargarPendientes();

    // =========================================================
    // 3. SELECCIÓN DE TRABAJO (Click en Tabla)
    // =========================================================
    if (tablaPendientes) {
        tablaPendientes.addEventListener('click', async (e) => {
            
            // CASO A: INICIAR REPARACIÓN (Firma Digital)
            if (e.target.classList.contains('btn-iniciar')) {
                const idEntrada = e.target.dataset.id;
                if(!confirm("¿Confirmas que vas a iniciar la reparación? Se registrará tu usuario y la hora actual.")) return;

                try {
                    const formData = new FormData();
                    formData.append('id', idEntrada);
                    
                    const res = await fetch('php/iniciar_reparacion.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.success) {
                        alert("✅ " + data.message);
                        cargarPendientes(); 
                    } else {
                        alert("Error: " + data.message);
                    }
                } catch(err) {
                    alert("Error de conexión al iniciar reparación.");
                }
            }

            // CASO B: FINALIZAR REPARACIÓN (Abrir Formulario)
            if (e.target.classList.contains('btn-finalizar')) {
                const data = JSON.parse(e.target.dataset.json);
                iniciarServicio(data);
            }
        });
    }

    // --- FUNCIÓN CENTRAL: PREPARAR EL FORMULARIO ---
    function iniciarServicio(data) {
        formContainer.style.display = 'block';
        formContainer.scrollIntoView({ behavior: 'smooth' });

        inputIdEntrada.value = data.id;
        // Importante: Usar id_camion real de la BD
        inputIdCamion.value = data.id_camion || data.id; 

        inputCamionInfo.value = `${data.numero_economico} - ${data.placas}`;
        if(inputFolioInfo) inputFolioInfo.value = data.folio;
        if(inputTipoMto) inputTipoMto.value = data.tipo_mantenimiento_solicitado;

        if(inputFiltroAceiteActual) inputFiltroAceiteActual.value = data.serie_filtro_aceite_actual || 'N/A';
        if(inputFiltroCentActual) inputFiltroCentActual.value = data.serie_filtro_centrifugo_actual || 'N/A';
        
        if(inputTicket) inputTicket.value = "";
    }

    // =========================================================
    // 4. BÚSQUEDA MANUAL (Respaldo)
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
                    iniciarServicio(data.data);
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
    // 5. VALIDACIONES PRE-ENVÍO (FRONTEND)
    // =========================================================
    
    // A. Validar que no mezcle filtros (Lógica simple por nombre)
    function validarTipoFiltro(input, tipoProhibido) {
        const valor = input.value.toUpperCase();
        if (valor.includes(tipoProhibido)) {
            alert(`⚠️ CUIDADO: Parece que estás ingresando un filtro de ${tipoProhibido} en el campo incorrecto.`);
            input.style.borderColor = "red";
            return false;
        }
        input.style.borderColor = "#ccc";
        return true;
    }

    if(inputNuevoFiltroAceite) {
        inputNuevoFiltroAceite.addEventListener('change', () => {
            // Si la serie tiene "AIR" o "CENT" y es campo de aceite... alerta
            if(inputNuevoFiltroAceite.value.toUpperCase().includes('CENT')) {
                alert("Este parece ser un filtro Centrífugo. Verifica el campo.");
            }
        });
    }

    // B. Validar Mezcla de Aceites (Cubetas deben parecerse)
    function validarMezclaAceites() {
        const c1 = inputCubeta1.value.trim().toUpperCase();
        const c2 = inputCubeta2.value.trim().toUpperCase();
        
        if (c1 && c2) {
            // Ejemplo muy básico: Si las series tienen prefijos de tipo de aceite
            // Suponiendo series tipo "15W40-001" y "10W30-002"
            const prefijo1 = c1.split('-')[0]; // Toma lo que está antes del primer guion
            const prefijo2 = c2.split('-')[0];

            if (prefijo1 !== prefijo2) {
                // Solo advertencia visual en frontend, el bloqueo real lo hace el backend
                inputCubeta2.style.borderColor = "orange";
                console.warn("Posible mezcla de aceites detectada por prefijo.");
            } else {
                inputCubeta2.style.borderColor = "#ccc";
            }
        }
    }

    if(inputCubeta1 && inputCubeta2) {
        inputCubeta1.addEventListener('change', validarMezclaAceites);
        inputCubeta2.addEventListener('change', validarMezclaAceites);
    }


    // =========================================================
    // 6. ENVÍO DEL FORMULARIO (FINALIZAR TRABAJO)
    // =========================================================
    if (formSalida) {
        formSalida.addEventListener('submit', async (e) => {
            e.preventDefault();

            // 1. Validar ID
            if(!inputIdEntrada.value) {
                alert("⚠️ Error: No hay una orden seleccionada.");
                return;
            }

            // 2. Preparar UI
            const btnSubmit = formSalida.querySelector('button[type="submit"]');
            const textoOriginal = btnSubmit.textContent;
            btnSubmit.disabled = true;
            btnSubmit.textContent = "Validando y Guardando...";

            const formData = new FormData(formSalida);

            try {
                const res = await fetch('php/finalizar_mantenimiento.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Manejo de respuesta (incluyendo el error 409 o 500)
                const textoRespuesta = await res.text();
                let data;
                try {
                    data = JSON.parse(textoRespuesta);
                } catch (errJSON) {
                    console.error("Respuesta no JSON:", textoRespuesta);
                    throw new Error("Error del servidor (Respuesta inválida).");
                }

                if (data.success) {
                    alert("🎉 ¡MANTENIMIENTO FINALIZADO!\n\n" + data.message);
                    location.reload(); 
                } else {
                    // Muestra el mensaje de error específico del backend (Mezcla, Duplicado, etc.)
                    alert("⛔ NO SE PUDO GUARDAR:\n" + data.message);
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
    // 7. UTILIDADES
    // =========================================================
    window.cancelarServicio = function() {
        formContainer.style.display = 'none';
        formSalida.reset();
        inputIdEntrada.value = "";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

});