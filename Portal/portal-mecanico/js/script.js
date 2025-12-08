/*
 * Portal/portal-mecanico/js/script.js
 * VERSIÓN BLINDADA (SALIDAS): Validación estricta de fotos y procesos de cierre.
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




    // Variable global para guardar los datos crudos
    let todosLosPendientes = [];

    // Referencia al filtro
    const filtroSelect = document.getElementById('filtro-estatus');

    // Listener para el filtro
    if (filtroSelect) {
        filtroSelect.addEventListener('change', () => {
            renderizarTabla(todosLosPendientes);
        });
    }

    // =========================================================
    // 2. BANDEJA DE ENTRADA (Cargar Pendientes)
    // =========================================================
    async function cargarPendientes() {
        if (!tablaPendientes) return;
        
        try {
            const res = await fetch('php/listar_pendientes_mecanico.php');
            const data = await res.json();
            
            if (data.success) {
                todosLosPendientes = data.data; // Guardamos en memoria
                renderizarTabla(todosLosPendientes); // Dibujamos
            } else {
                tablaPendientes.innerHTML = '<tr><td colspan="6" style="text-align:center">No hay trabajos pendientes.</td></tr>';
            }
        } catch (error) { 
            console.error(error); 
            tablaPendientes.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red;">Error de conexión.</td></tr>';
        }
    }

    // 2. Función para dibujar la tabla (con filtro aplicado)
    function renderizarTabla(datos) {
        const filtro = filtroSelect ? filtroSelect.value : 'todos';
        tablaPendientes.innerHTML = '';

        // Filtramos según el select
        const datosFiltrados = datos.filter(t => {
            if (filtro === 'todos') return true;
            return t.estatus_entrada === filtro;
        });

        if (datosFiltrados.length === 0) {
            tablaPendientes.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px;">No hay vehículos con este estatus.</td></tr>';
            return;
        }

        datosFiltrados.forEach(t => {
            const tr = document.createElement('tr');
            // Escapar comillas para el JSON
            const jsonData = JSON.stringify(t).replace(/'/g, "&apos;");
            
            let botonHTML = '';
            let estatusBadge = '';
            let claseFila = '';

            // Lógica visual según estatus
            if (t.estatus_entrada === 'Recibido') {
                estatusBadge = '<span class="badge badge-espera">⏳ Por Iniciar</span>';
                botonHTML = `<button class="btn-accion btn-iniciar" data-id="${t.id}">▶ INICIAR</button>`;
                claseFila = 'fila-espera';
            } else if (t.estatus_entrada === 'En Proceso') {
                estatusBadge = '<span class="badge badge-proceso">🔨 En Trabajo</span>';
                botonHTML = `<button class="btn-accion btn-finalizar" data-json='${jsonData}'>✅ FINALIZAR</button>`;
                claseFila = 'fila-proceso'; // Para resaltar ligeramente
            }

            // Formateo de fecha
            const fechaObj = new Date(t.fecha_ingreso);
            const fechaFmt = fechaObj.toLocaleDateString() + ' ' + fechaObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

            tr.className = claseFila;
            tr.innerHTML = `
                <td><span style="font-weight:bold; color:#555;">${t.folio}</span></td>
                <td>📍 ${t.origen_taller || 'N/A'}</td> <td>
                    <div style="font-weight:bold; font-size:1.1em;">${t.numero_economico}</div>
                    <div style="font-size:0.85em; color:#777;">${t.placas}</div>
                </td>
                <td>
                    <div>${t.tipo_mantenimiento_solicitado}</div>
                    <div style="margin-top:5px;">${estatusBadge}</div>
                </td>
                <td style="font-size:0.9em;">${fechaFmt}</td>
                <td style="text-align:center;">${botonHTML}</td>
            `;
            tablaPendientes.appendChild(tr);
        });
    }

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

    cargarPendientes();

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
        
        // Limpiar inputs de archivo y mensajes previos al abrir
        document.querySelectorAll('.mensaje-validacion').forEach(msg => msg.innerHTML = '');
        document.querySelectorAll('input[type="file"]').forEach(inp => inp.value = '');
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
    if(inputNuevoFiltroAceite) {
        inputNuevoFiltroAceite.addEventListener('change', () => {
            if(inputNuevoFiltroAceite.value.toUpperCase().includes('CENT')) {
                alert("⚠️ Este parece ser un filtro Centrífugo. Verifica el campo.");
            }
        });
    }

    // B. Validar Mezcla de Aceites (Cubetas deben parecerse)
    function validarMezclaAceites() {
        if (!inputCubeta1 || !inputCubeta2) return;
        const c1 = inputCubeta1.value.trim().toUpperCase();
        const c2 = inputCubeta2.value.trim().toUpperCase();
        
        if (c1 && c2) {
            const prefijo1 = c1.split('-')[0]; // Toma lo que está antes del primer guion
            const prefijo2 = c2.split('-')[0];

            if (prefijo1 !== prefijo2) {
                inputCubeta2.style.borderColor = "orange";
                // Solo advertencia, el bloqueo real lo hace el backend
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
    // 6. VALIDACIÓN ESTRICTA DE FOTOS (CERO TOLERANCIA)
    // =========================================================

    function validarInputFoto(inputElement) {
        inputElement.addEventListener('change', async function(e) {
            const archivo = e.target.files[0];
            if (!archivo) return;

            const parentDiv = inputElement.closest('.campo-form-evidencia') || inputElement.parentElement;
            
            // Crear o limpiar caja de mensaje
            let msgBox = parentDiv.querySelector('.mensaje-validacion');
            if (!msgBox) {
                msgBox = document.createElement('div');
                msgBox.className = 'mensaje-validacion';
                msgBox.style.fontSize = '0.85em';
                msgBox.style.marginTop = '5px';
                parentDiv.appendChild(msgBox);
            }
            msgBox.innerHTML = '🔄 Analizando metadatos...';
            msgBox.style.color = '#666';

            // 1. Validar Tipo
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!tiposPermitidos.includes(archivo.type)) {
                inputElement.value = ""; // Borrar
                msgBox.innerHTML = '⛔ <strong>Error:</strong> Solo JPG o PNG.';
                msgBox.style.color = 'red';
                return;
            }

            // 2. Validar Metadatos EXIF
            if (typeof EXIF !== 'undefined') {
                EXIF.getData(archivo, function() {
                    const meta = EXIF.getAllTags(this);
                    
                    // VALIDACIÓN CRÍTICA: WhatsApp borra este dato. Si no existe, es foto "falsa" o descargada.
                    if (!meta.DateTimeOriginal) {
                        
                        // 1. Limpiar el input para que no se pueda enviar
                        inputElement.value = ""; 
                        
                        // 2. Mostrar mensaje visual rojo
                        msgBox.innerHTML = '⛔ <strong>FOTO RECHAZADA:</strong> Es de WhatsApp o captura.';
                        msgBox.style.color = 'red';
                        
                        // 3. Alerta explicativa
                        alert("🚫 IMAGEN NO VÁLIDA\n\n" +
                            "Esta foto no tiene fecha original. El sistema detecta que:\n" +
                            "- Fue enviada por WhatsApp (WhatsApp borra los datos).\n" +
                            "- O es una captura de pantalla.\n\n" +
                            "SOLUCIÓN: Toma la foto directamente con la cámara o sube el archivo original.");
                    } else {
                        // ACEPTADA
                        msgBox.innerHTML = '✅ Foto válida (Original: ' + meta.DateTimeOriginal + ').';
                        msgBox.style.color = 'green';
                    }
                });
            } else {
                // Esto pasa si olvidaste el Paso 1
                console.error("Error: La librería EXIF no está cargada.");
                msgBox.innerHTML = '⚠️ Error del sistema: Librería faltante.';
            }
        });
    }

    // Aplicar validador a TODOS los inputs de archivo en el formulario
    const inputsFotos = document.querySelectorAll('#form-salida input[type="file"]');
    inputsFotos.forEach(input => validarInputFoto(input));


    // =========================================================
    // 7. ENVÍO DEL FORMULARIO (FINALIZAR TRABAJO)
    // =========================================================
    if (formSalida) {
        formSalida.addEventListener('submit', async (e) => {
            e.preventDefault();

            // 1. Validar ID
            if(!inputIdEntrada.value) {
                alert("⚠️ Error: No hay una orden seleccionada.");
                return;
            }

            // 2. Verificar que todos los inputs de archivo tengan valor
            // (Si el validador borró alguno por ser inválido, el required nativo lo detendrá, 
            // pero hacemos doble check aquí)
            let archivosValidos = true;
            inputsFotos.forEach(inp => {
                if(!inp.value) archivosValidos = false;
            });
            
            if(!archivosValidos) {
                alert("⚠️ Faltan evidencias válidas. Asegúrate de que todas las fotos sean originales.");
                return;
            }

            // 3. Preparar UI
            const btnSubmit = formSalida.querySelector('button[type="submit"]');
            const textoOriginal = btnSubmit.textContent;
            btnSubmit.disabled = true;
            btnSubmit.textContent = "Guardando...";

            const formData = new FormData(formSalida);

            try {
                const res = await fetch('php/finalizar_mantenimiento.php', {
                    method: 'POST',
                    body: formData
                });
                
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
    // 8. UTILIDADES
    // =========================================================
    window.cancelarServicio = function() {
        formContainer.style.display = 'none';
        formSalida.reset();
        inputIdEntrada.value = "";
        
        // Limpiar mensajes de validación
        document.querySelectorAll('.mensaje-validacion').forEach(el => el.innerHTML = '');
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

});