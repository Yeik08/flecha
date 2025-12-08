/*
 * Portal/portal-mecanico/js/script.js
 * VERSIÓN DEFINITIVA: Filtros Avanzados + Validación de Fotos Estricta + Trazabilidad
 */

document.addEventListener('DOMContentLoaded', function() {

    // =========================================================
    // 1. REFERENCIAS DOM Y VARIABLES GLOBALES
    // =========================================================
    
    // Contenedores principales
    const tablaPendientes = document.getElementById('tabla-pendientes-body');
    const formContainer = document.getElementById('contenedor-servicio');
    const formSalida = document.getElementById('form-salida');
    
    // Filtros de la Tabla
    const filtroSelect = document.getElementById('filtro-estatus'); // Filtro de Estado
    const filtroTaller = document.getElementById('filtro-taller');   // Filtro de Origen
    
    // Variable para almacenar los datos crudos del servidor
    let todosLosPendientes = [];

    // Referencias Búsqueda Manual (Respaldo)
    const btnBuscar = document.getElementById('btn-buscar-ticket');
    const inputTicket = document.getElementById('ticket-id');

    // Referencias Inputs del Formulario (Ocultos y Visuales)
    const inputIdEntrada = document.getElementById('id_entrada_hidden');
    const inputIdCamion = document.getElementById('id_camion_hidden');
    const inputCamionInfo = document.getElementById('camion-info');
    const inputFolioInfo = document.getElementById('folio-info');
    const inputTipoMto = document.getElementById('tipo-mantenimiento'); 
    
    // Referencias Filtros (Inventario)
    const inputFiltroAceiteActual = document.getElementById('filtro-aceite-actual');
    const inputFiltroCentActual = document.getElementById('filtro-centrifugo-actual');
    const inputNuevoFiltroAceite = document.querySelector('input[name="nuevo_filtro_aceite"]');
    const inputCubeta1 = document.querySelector('input[name="serie_cubeta_1"]');
    const inputCubeta2 = document.querySelector('input[name="serie_cubeta_2"]');


    // =========================================================
    // 2. LÓGICA DE TABLA Y FILTROS
    // =========================================================

    // Listener para los filtros (Redibujan la tabla al cambiar)
    if (filtroSelect) filtroSelect.addEventListener('change', () => renderizarTabla(todosLosPendientes));
    if (filtroTaller) filtroTaller.addEventListener('change', () => renderizarTabla(todosLosPendientes));

    // A. Cargar datos del servidor
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

    // B. Renderizar Tabla (Con lógica de doble filtro)
    function renderizarTabla(datos) {
        if (!tablaPendientes) return;
        tablaPendientes.innerHTML = '';

        // 1. Obtener valores actuales de los filtros
        const valEstatus = filtroSelect ? filtroSelect.value : 'todos';
        const valTaller = filtroTaller ? filtroTaller.value : 'todos';

        // 2. Filtrar
        const datosFiltrados = datos.filter(t => {
            // Condición 1: Estatus
            const cumpleEstatus = (valEstatus === 'todos') || (t.estatus_entrada === valEstatus);
            
            // Condición 2: Taller (Origen)
            // Nota: t.origen_taller viene del PHP.
            const tallerData = t.origen_taller || ''; 
            const cumpleTaller = (valTaller === 'todos') || (tallerData === valTaller);

            return cumpleEstatus && cumpleTaller;
        });

        if (datosFiltrados.length === 0) {
            tablaPendientes.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px; color: #777;">No se encontraron coincidencias.</td></tr>';
            return;
        }

        // 3. Dibujar Filas
        datosFiltrados.forEach(t => {
            const tr = document.createElement('tr');
            // Escapar comillas para el JSON
            const jsonData = JSON.stringify(t).replace(/'/g, "&apos;");
            
            let botonHTML = '';
            let estatusBadge = '';
            let claseFila = '';
            
            const tieneMaterial = t.filtro_aceite_entregado ? true : false;
            const badgeMaterial = tieneMaterial 
                ? '<span class="badge badge-material-ok" title="Material entregado por almacén">📦 Material Listo</span>' 
                : '<span class="badge badge-material-no" title="Debes ir al almacén a solicitar piezas">⚪ Sin Material</span>';


            // Configuración visual según estatus
            if (t.estatus_entrada === 'Recibido') {
                estatusBadge = '<span class="badge badge-espera">⏳ Por Iniciar</span>';
                botonHTML = `<button class="btn-accion btn-iniciar" data-id="${t.id}">▶ INICIAR</button>`;
                claseFila = 'fila-espera';
            } else if (t.estatus_entrada === 'En Proceso') {
                estatusBadge = '<span class="badge badge-proceso">🔨 En Trabajo</span>';
                botonHTML = `<button class="btn-accion btn-finalizar" data-json='${jsonData}'>✅ FINALIZAR</button>`;
                claseFila = 'fila-proceso';
            }

            // Formateo de fecha
            const fechaObj = new Date(t.fecha_ingreso);
            const fechaFmt = fechaObj.toLocaleDateString() + ' ' + fechaObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

tr.className = claseFila;
            tr.innerHTML = `
                <td><span style="font-weight:bold; color:#555;">${t.folio}</span></td>
                <td>${t.origen_taller || 'N/A'}</td>
                <td>
                    <div style="font-weight:bold; font-size:1.1em;">${t.numero_economico}</div>
                    <div style="font-size:0.85em; color:#777;">${t.placas}</div>
                </td>
                <td>
                    <div style="font-weight:bold; font-size:0.9em;">${t.tipo_mantenimiento_solicitado}</div>
                    <div style="margin-top:5px; display:flex; gap:5px; flex-wrap:wrap;">
                        ${estatusBadge}
                        ${badgeMaterial} </div>
                </td>
                <td style="font-size:0.9em;">${fechaFmt}</td>
                <td style="text-align:center;">${botonHTML}</td>
            `;
            tablaPendientes.appendChild(tr);
        });
    }


    // =========================================================
    // 3. ACCIONES DE LA TABLA (Delegación de Eventos)
    // =========================================================
    if (tablaPendientes) {
        tablaPendientes.addEventListener('click', async (e) => {
            
            // CASO A: INICIAR REPARACIÓN
            if (e.target.classList.contains('btn-iniciar')) {
                const idEntrada = e.target.dataset.id;
                if(!confirm("¿Confirmas que vas a iniciar la reparación?")) return;

                try {
                    const formData = new FormData();
                    formData.append('id', idEntrada);
                    
                    const res = await fetch('php/iniciar_reparacion.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.success) {
                        alert("✅ " + data.message);
                        cargarPendientes(); // Recargar tabla
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

    // --- Función para preparar y mostrar el formulario ---
    function iniciarServicio(data) {
        formContainer.style.display = 'block';
        formContainer.scrollIntoView({ behavior: 'smooth' });

        inputIdEntrada.value = data.id;
        inputIdCamion.value = data.id_camion || data.id; 

        inputCamionInfo.value = `${data.numero_economico} - ${data.placas}`;
        if(inputFolioInfo) inputFolioInfo.value = data.folio;
        if(inputTipoMto) inputTipoMto.value = data.tipo_mantenimiento_solicitado;

        // Filtros ACTUALES del camión (Info)
        if(inputFiltroAceiteActual) inputFiltroAceiteActual.value = data.serie_filtro_aceite_actual || 'N/A';
        if(inputFiltroCentActual) inputFiltroCentActual.value = data.serie_filtro_centrifugo_actual || 'N/A';

        // =========================================================
        // ✅ AUTO-LLENADO Y BLOQUEO DE MATERIAL (DEL ALMACÉN)
        // =========================================================
        
        // Referencias a los inputs del formulario de cierre
        const inNuevoAceite = document.querySelector('input[name="nuevo_filtro_aceite"]');
        const inNuevoCent = document.querySelector('input[name="nuevo_filtro_centrifugo"]');
        const inCubeta1 = document.querySelector('input[name="serie_cubeta_1"]');
        const inCubeta2 = document.querySelector('input[name="serie_cubeta_2"]');

        const aplicarCandado = (input, valor) => {
            if (input) {
                // 1. SIEMPRE BLOQUEADO (Nadie escribe a mano)
                input.setAttribute('readonly', true);
                input.style.pointerEvents = "none"; // Evita clicks
                
                // 2. Lógica visual según si hay dato o no
                if (valor && valor !== "null" && valor !== "") {
                    // CASO A: Almacén entregó material
                    input.value = valor;
                    input.style.backgroundColor = "#e9ecef"; // Gris sólido (Datos OK)
                    input.style.border = "1px solid #ced4da";
                    input.style.color = "#495057";
                    input.style.fontWeight = "bold";
                } else {
                    // CASO B: No se entregó material (No aplica o falta)
                    input.value = ""; 
                    input.placeholder = "⛔ No asignado por almacén";
                    input.style.backgroundColor = "#f2f2f2"; // Gris claro
                    input.style.border = "1px dashed #ccc";  // Borde punteado (Inactivo)
                    input.style.color = "#aaa";
                }
            }
        };

        aplicarCandado(inNuevoAceite, data.filtro_aceite_entregado);
        aplicarCandado(inNuevoCent, data.filtro_centrifugo_entregado); // Ahora este se bloqueará aunque venga vacío
        aplicarCandado(inCubeta1, data.cubeta_1_entregada);
        aplicarCandado(inCubeta2, data.cubeta_2_entregada);

        // =========================================================

        if(inputTicket) inputTicket.value = "";
        
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
    // 5. VALIDACIONES DE FORMULARIO (Frontend)
    // =========================================================
    
    // A. Validar que no mezcle filtros (Lógica simple)
    if(inputNuevoFiltroAceite) {
        inputNuevoFiltroAceite.addEventListener('change', () => {
            if(inputNuevoFiltroAceite.value.toUpperCase().includes('CENT')) {
                alert("⚠️ Este parece ser un filtro Centrífugo. Verifica el campo.");
            }
        });
    }

    // B. Validar Mezcla de Aceites
    function validarMezclaAceites() {
        if (!inputCubeta1 || !inputCubeta2) return;
        const c1 = inputCubeta1.value.trim().toUpperCase();
        const c2 = inputCubeta2.value.trim().toUpperCase();
        
        if (c1 && c2) {
            const prefijo1 = c1.split('-')[0];
            const prefijo2 = c2.split('-')[0];

            if (prefijo1 !== prefijo2) {
                inputCubeta2.style.borderColor = "orange";
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
    // 6. VALIDACIÓN ESTRICTA DE FOTOS (ANTI-WHATSAPP)
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
                    
                    // VALIDACIÓN CRÍTICA: ¿Tiene fecha original?
                    if (!meta.DateTimeOriginal) {
                        // RECHAZO TOTAL
                        inputElement.value = ""; // Borramos el archivo
                        msgBox.innerHTML = '⛔ <strong>FOTO RECHAZADA:</strong> Es de WhatsApp o captura.';
                        msgBox.style.color = 'red';
                        
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
                msgBox.innerHTML = '⚠️ Advertencia: Librería EXIF no cargada en el sistema.';
                msgBox.style.color = 'orange';
                console.error("Falta librería EXIF.js en el HTML");
            }
        });
    }

    // Aplicar validador a TODOS los inputs de archivo
    const inputsFotos = document.querySelectorAll('#form-salida input[type="file"]');
    inputsFotos.forEach(input => validarInputFoto(input));


    // =========================================================
    // 7. ENVÍO DEL FORMULARIO (FINALIZAR)
    // =========================================================
    if (formSalida) {
        formSalida.addEventListener('submit', async (e) => {
            e.preventDefault();

            // 1. Validar ID
            if(!inputIdEntrada.value) {
                alert("⚠️ Error: No hay una orden seleccionada.");
                return;
            }

            // 2. Verificar archivos vacíos (Doble check)
            let archivosValidos = true;
            inputsFotos.forEach(inp => { if(!inp.value) archivosValidos = false; });
            
            if(!archivosValidos) {
                alert("⚠️ Faltan evidencias válidas o alguna foto fue rechazada. Verifica los mensajes en rojo.");
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
                
                // Manejo robusto de JSON
                const textoRespuesta = await res.text();
                let data;
                try {
                    data = JSON.parse(textoRespuesta);
                } catch (errJSON) {
                    console.error("Respuesta no JSON:", textoRespuesta);
                    throw new Error("El servidor devolvió una respuesta inválida.");
                }

                if (data.success) {
                    alert("🎉 ¡MANTENIMIENTO FINALIZADO!\n\n" + data.message);
                    location.reload(); 
                } else {
                    alert("⛔ NO SE PUDO GUARDAR:\n" + data.message);
                }

            } catch(e) {
                console.error(e);
                alert("Error crítico: " + e.message);
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

    // =========================================================
    // 9. INICIALIZAR (IMPORTANTE)
    // =========================================================
    // Llamamos a la función de carga al iniciar la página
    cargarPendientes();

});