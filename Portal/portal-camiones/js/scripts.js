/**
 * scripts.js - VERSIÓN DEFINITIVA (v7)
 * - Separa la lógica de los modales (Alta vs Telemetría).
 * - Añade listener para el nuevo botón 'btn-guardar-archivo-masivo'.
 * - Corrige el listener de 'btn-guardar-csv' para que solo maneje telemetría.
 * - Corrige el botón 'btn-abrir-modal-telemetria' para que abra el modal correcto.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. LÓGICA PARA EL MENÚ DROPDOWN ---
    document.addEventListener('click', function(e) {
        const isDropdownToggle = e.target.matches('.dropdown-toggle');
        if (!isDropdownToggle && e.target.closest('.dropdown') === null) {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => dropdown.classList.remove('active'));
        }
        if (isDropdownToggle) {
            const parent = e.target.closest('.dropdown');
            parent.classList.toggle('active');
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                if (dropdown !== parent) dropdown.classList.remove('active');
            });
        }
    });

    // --- 2. LÓGICA DEL MODAL DE ALTA DE CAMIÓN ---
    const modalAltaCamion = document.getElementById('modal-formulario');
    const btnAbrirModalAlta = document.getElementById('btn-abrir-modal');
    const btnsCerrarModal = document.querySelectorAll('.modal-cerrar, .btn-cerrar-modal');

    if (modalAltaCamion && btnAbrirModalAlta) {
        const abrirModal = () => modalAltaCamion.classList.remove('oculto');
        const cerrarModal = () => modalAltaCamion.classList.add('oculto');
        
        btnAbrirModalAlta.addEventListener('click', abrirModal);

        //btnsCerrarModal.forEach(btn => btn.addEventListener('click', cerrarModal));
        //modalAltaCamion.addEventListener('click', e => {
          //  if (e.target === modalAltaCamion) cerrarModal();
          btnsCerrarModal.forEach(btn => btn.addEventListener('click', () => {
            modalAltaCamion.classList.add('oculto');
            modalTelemetria.classList.add('oculto');
        }));
                modalAltaCamion.addEventListener('click', e => {
            if (e.target === modalAltaCamion) cerrarModal();
        });
    }


    // --- 2b. LÓGICA DEL MODAL DE SUBIR TELEMETRÍA ---
    const modalTelemetria = document.getElementById('modal-subir-telemetria');
    const btnAbrirModalTelemetria = document.getElementById('btn-abrir-modal-telemetria');
    
    if (modalTelemetria && btnAbrirModalTelemetria) {
        // Abre el modal de telemetría
        btnAbrirModalTelemetria.addEventListener('click', (e) => {
            e.preventDefault();
            modalTelemetria.classList.remove('oculto');
        });
        
        modalTelemetria.addEventListener('click', e => {
            if (e.target === modalTelemetria) {
                modalTelemetria.classList.add('oculto');
            }
        });
    }

    // --- 2c. LÓGICA PARA ENVIAR EL ARCHIVO DE TELEMETRÍA (CON ALERTA DUPLICADOS) ---
    const btnGuardarCsvTelemetria = document.getElementById('btn-guardar-csv-telemetria');
    const inputCsvRecorridos = document.getElementById('input-csv-recorridos');

    if (btnGuardarCsvTelemetria && inputCsvRecorridos) {
        
        inputCsvRecorridos.addEventListener('change', () => {
            btnGuardarCsvTelemetria.disabled = (inputCsvRecorridos.files.length === 0);
        });

        btnGuardarCsvTelemetria.addEventListener('click', async (e) => {
            e.preventDefault();
            if (inputCsvRecorridos.files.length === 0) {
                alert("Por favor, selecciona un archivo CSV de telemetría para subir.");
                return;
            }
            const file = inputCsvRecorridos.files[0];
            const formData = new FormData();
            formData.append('archivo_recorridos', file); 

            btnGuardarCsvTelemetria.disabled = true;
            btnGuardarCsvTelemetria.textContent = 'Procesando...';

            try {
                const response = await fetch('procesar_telemetria.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    // --- BLOQUE DE ALERTA INTELIGENTE ---
                    let mensajeFinal = "¡Éxito! " + data.message;

                    // Si el PHP reportó duplicados, los mostramos
                    if (data.lista_duplicados && data.lista_duplicados.length > 0) {
                        mensajeFinal += "\n\n⚠️ ADVERTENCIA: Se detectaron datos repetidos (Mismo Mes/Año).";
                        mensajeFinal += "\nSe actualizaron los registros de los siguientes camiones:\n";
                        // Mostramos solo los primeros 5 para no saturar la alerta
                        mensajeFinal += data.lista_duplicados.slice(0, 5).join("\n");
                        
                        if (data.lista_duplicados.length > 5) {
                            mensajeFinal += "\n... y " + (data.lista_duplicados.length - 5) + " más.";
                        }
                    }
                    
                    alert(mensajeFinal);
                    modalTelemetria.classList.add('oculto');
                } else {
                    alert("Error al procesar el archivo:\n" + data.message);
                }

            } catch (error) {
                console.error('Error en el fetch de telemetría:', error);
                alert('Error de conexión. No se pudo contactar al servidor.');
            } finally {
                btnGuardarCsvTelemetria.disabled = false;
                btnGuardarCsvTelemetria.textContent = 'Confirmar y Guardar';
                inputCsvRecorridos.value = ''; 
            }
        });
    }

    // --- 3. LÓGICA DE PESTAÑAS (MANUAL / ARCHIVO) ---
    const tabLinks = document.querySelectorAll('.carga-link');
    const tabContents = document.querySelectorAll('.tab-content');
    if (tabLinks.length > 0) {
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const tabId = link.getAttribute('data-tab');
                if (!tabId) return;
                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => item.classList.remove('active'));
                link.classList.add('active');
                const activeTab = document.getElementById(tabId);
                if (activeTab) activeTab.classList.add('active');
            });
        });
    }

    // --- 4. LÓGICA FORMULARIO MANUAL: OCULTAR CAMPOS ---
    const condicionVehiculo = document.getElementById('condicion');
    const camposSoloParaUsado = document.querySelectorAll('.ocultar-si-es-nuevo');
    if (condicionVehiculo && camposSoloParaUsado.length > 0) {
        const toggleCamposUsado = () => {
            // Quitamos la lógica del 'display:flex' y usamos una clase
            if (condicionVehiculo.value === 'nuevo') {
                camposSoloParaUsado.forEach(campo => { campo.classList.add('oculto-simple'); });
            } else {
                camposSoloParaUsado.forEach(campo => { campo.classList.remove('oculto-simple'); });
            }
        };
        toggleCamposUsado(); 
        condicionVehiculo.addEventListener('change', toggleCamposUsado); 
    }

    // --- 5. LÓGICA DE CARGA DINÁMICA DE CATÁLOGOS ---
    // (Esta sección está bien y no necesita cambios)
    let conductoresData = []; 
    const selectTecnologia = document.getElementById("tipo_unidad");
    const selectAnio = document.getElementById("anio");
    const selectMarcaAceite = document.getElementById("marca_filtro");
    const selectMarcaCentrifugo = document.getElementById("marca_filtro_centrifugo");
    const selectLubricante = document.getElementById("tipo_aceite"); 
    async function cargarCatalogos() {
        if (selectTecnologia) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=tecnologias');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const tecnologias = await response.json();
                selectTecnologia.innerHTML = '<option value="">Selecciona tipo de tecnologia</option>';
                tecnologias.forEach(tec => {
                    const opcion = document.createElement("option");
                    opcion.value = tec.id; 
                    opcion.textContent = tec.nombre.toUpperCase();
                    selectTecnologia.appendChild(opcion);
                });
            } catch (error) { console.error('Error en fetch Tecnologías:', error); }
        }
        try {
            const response = await fetch('fetch_catalogos.php?tipo=conductores');
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const conductores = await response.json();
            conductoresData = conductores.map(c => ({ id: c.id_usuario, nombre: c.nombre_completo.toUpperCase() }));
        } catch (error) { console.error('Error en fetch Conductores:', error); }
        if (selectAnio) {
            const anioActual = new Date().getFullYear();
            selectAnio.innerHTML = '<option value="">Selecciona año</option>'; 
            for (let i = anioActual; i >= 1990; i--) {
                const opcion = document.createElement("option");
                opcion.value = i;
                opcion.textContent = i;
                selectAnio.appendChild(opcion);
            }
        }
        if (selectMarcaAceite) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=filtros_aceite');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const marcas = await response.json();
                selectMarcaAceite.innerHTML = '<option value="">Selecciona una marca</option>';
                marcas.forEach(item => {
                    const opcion = document.createElement("option");
                    opcion.value = item.marca;
                    opcion.textContent = item.marca.toUpperCase();
                    selectMarcaAceite.appendChild(opcion);
                });
            } catch (error) { console.error('Error en fetch Filtros Aceite:', error); }
        }
        if (selectMarcaCentrifugo) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=filtros_centrifugo');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const marcas = await response.json();
                selectMarcaCentrifugo.innerHTML = '<option value="">Selecciona una marca</option>';
                marcas.forEach(item => {
                    const opcion = document.createElement("option");
                    opcion.value = item.marca;
                    opcion.textContent = item.marca.toUpperCase();
                    selectMarcaCentrifugo.appendChild(opcion);
                });
            } catch (error) { console.error('Error en fetch Filtros Centrifugo:', error); }
        }
        if (selectLubricante) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=lubricantes');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const lubricantes = await response.json();
                selectLubricante.innerHTML = '<option value="">Selecciona un lubricante</option>';
                lubricantes.forEach(item => {
                    const opcion = document.createElement("option");
                    opcion.value = item.nombre; 
                    opcion.textContent = item.nombre.toUpperCase();
                    selectLubricante.appendChild(opcion);
                });
            } catch (error) { console.error('Error en fetch Lubricantes:', error); }
        }
    }
    cargarCatalogos();


    cargarTablaCamiones();

    // --- 5b. LÓGICA PARA CARGAR LA TABLA PRINCIPAL DE CAMIONES ---
    async function cargarTablaCamiones() {
        const tbody = document.querySelector(".tabla-contenido tbody");
        if (!tbody) return; // Si no hay tabla, no hacemos nada

        tbody.innerHTML = '<tr><td colspan="6">Cargando camiones...</td></tr>';

        try {
            const response = await fetch('api_camiones.php');
            if (!response.ok) throw new Error(`Error HTTP ${response.status}`);
            
            const datos = await response.json();

            if (!datos.success) throw new Error(datos.message);

            if (datos.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No se encontraron camiones registrados.</td></tr>';
                return;
            }

            // Limpiamos la tabla
            tbody.innerHTML = '';

            // Construimos cada fila
            datos.data.forEach(camion => {
                // Preparamos los datos
                let estatusClase = '';
                switch (camion.estatus) {
                    case 'Activo': estatusClase = 'estatus-activo'; break;
                    case 'En Taller': estatusClase = 'estatus-taller'; break;
                    case 'Inactivo': estatusClase = 'estatus-inactivo'; break;
                }

                // Determinamos el próximo mantenimiento
                let proximoMto = '<span style="color: #999;">Pendiente cálculo</span>'; // Default
                if (camion.mantenimiento_requerido === 'Si') {
                    // Si ya urge, mostramos alerta ROJA con la fecha
                    const fechaVenc = camion.fecha_estimada_mantenimiento || 'Fecha desc.';
                    proximoMto = `<span class="mto-requerido">¡URGENTE! (${fechaVenc})</span>`;
                } else if (camion.fecha_estimada_mantenimiento) {
                    // Si hay fecha calculada, la mostramos normal
                    proximoMto = `<strong>${camion.fecha_estimada_mantenimiento}</strong>`; 
                }
                // Creamos la fila
                const filaHTML = `
                    <tr>
                        <td><strong>${camion.numero_economico}</strong></td>
                        <td>${camion.placas}</td>
                        <td><span class="estatus-tag ${estatusClase}">${camion.estatus}</span></td>
                        <td>${camion.fecha_ult_mantenimiento || 'N/A'}</td>
                        <td>${proximoMto}</td>
                        <td class="acciones">
                            <button class="btn-editar" data-id="${camion.id}">Ver/Editar</button>
                            <button class="btn-historial" data-id="${camion.id}">Historial</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += filaHTML;
            });

        } catch (error) {
            console.error('Error al cargar la tabla de camiones:', error);
            tbody.innerHTML = `<tr><td colspan="6">Error al cargar los datos: ${error.message}</td></tr>`;
        }
    }


    // --- 6. BÚSQUEDA INTELIGENTE DE CONDUCTOR ---
    // (Esta sección está bien y no necesita cambios)
    const inputConductor = document.getElementById("id_conductor");
    const listaSugerencias = document.getElementById("sugerencias-conductor");
    if (inputConductor && listaSugerencias) {
        inputConductor.addEventListener("input", () => {
            const valor = inputConductor.value.trim().toUpperCase();
            listaSugerencias.innerHTML = ""; 
            if (valor === "") { listaSugerencias.style.display = "none"; return; }
            const filtrados = conductoresData.filter(c => c.id.toUpperCase().includes(valor) || c.nombre.includes(valor));
            if (filtrados.length > 0) {
                listaSugerencias.style.display = "block";
                filtrados.forEach(c => {
                    const item = document.createElement("div");
                    item.textContent = c.nombre; 
                    item.addEventListener("click", () => {
                        inputConductor.value = c.id; 
                        listaSugerencias.style.display = "none";
                    });
                    listaSugerencias.appendChild(item);
                });
            } else {
                listaSugerencias.style.display = "block";
                const noRes = document.createElement("div");
                noRes.textContent = "Sin coincidencias";
                noRes.style.color = "#888";
                listaSugerencias.appendChild(noRes);
            }
        });
        document.addEventListener("click", (e) => {
            if (!listaSugerencias.contains(e.target) && e.target !== inputConductor) {
                listaSugerencias.style.display = "none";
            }
        });
    }

    // --- 7. LÓGICA DE DESCARGA DE PLANTILLA (ALTA MASIVA) ---
    // (Esta sección está bien, solo corregimos el ID del botón)
    const btnDescargarAlta = document.getElementById('btn-descargar-plantilla-alta');
    const selectCondicionArchivo = document.getElementById('condicion-archivo');
    if (btnDescargarAlta && selectCondicionArchivo) {
        btnDescargarAlta.addEventListener('click', (e) => {
            e.preventDefault();
            const tipoPlantilla = selectCondicionArchivo.value;
            let csvContent = "";
            let fileName = "";
            const headersNuevos = ["numero_economico", "condicion", "placas", "vin", "Marca", "Anio", "id_tecnologia", "ID_Conductor", "estatus", "kilometraje_total", "marca_filtro_aceite_actual", "serie_filtro_aceite_actual", "marca_filtro_centrifugo_actual", "serie_filtro_centrifugo_actual", "lubricante_actual"];
            const headersViejos = ["numero_economico", "condicion", "placas", "vin", "Marca", "Anio", "id_tecnologia", "ID_Conductor", "estatus", "kilometraje_total", "fecha_ult_mantenimiento", "fecha_ult_cambio_aceite", "marca_filtro_aceite_actual", "serie_filtro_aceite_actual", "fecha_ult_cambio_centrifugo", "marca_filtro_centrifugo_actual", "serie_filtro_centrifugo_actual", "lubricante_actual"];
            if (tipoPlantilla === 'nuevo') {
                fileName = "plantilla_camiones_nuevos.csv";
                csvContent = headersNuevos.join(",") + "\n";
                csvContent += "ECO-1180,nuevo,PLACA123,VIN123456789,Scania,2025,ID 1=SCANIA i5 13.0 MT / ID 2=Irizar i6/ ID 3=Irizar i6s 15 MT,CON-011,Activo/Inactivo/En Taller/Vendido,1500,SCANIA,SCASERIE123,SCANIA,SCASERIE456,SAE 10W30 MULTIGRADO/SAE 15W30\n";
            } else if (tipoPlantilla === 'usado') {
                fileName = "plantilla_camiones_usados.csv";
                csvContent = headersViejos.join(",") + "\n";
                csvContent += "ECO-1130,usado,PLACA456,VIN987654321,Scania,2021,ID 1=SCANIA i5 13.0 MT / ID 2=Irizar i6/ ID 3=Irizar i6s 15 MT,CON-012,Activo/Inactivo/En Taller/Vendido,450000,2025-01-01,2025-05-01,SCANIA,SCASERIE789,2025-05-01,SCANIA,SCASERIE101,SAE 10W30 MULTIGRADO/SAE 15W30\n";
            } else {
                alert("Por favor, selecciona una condición (Nuevo o Usado) para descargar la plantilla.");
                return;
            }
            descargarCSV(csvContent, fileName);
        });
    }

    // --- 8. LÓGICA DE DESCARGA DE PLANTILLA (TELEMETRÍA) ---
    // (Esta sección está bien y no necesita cambios)
    const btnDescargarRecorridos = document.getElementById('btn-descargar-recorridos-archivo');
    if (btnDescargarRecorridos) {
        btnDescargarRecorridos.addEventListener('click', (e) => {
            e.preventDefault();
            const headers = ["UNIDAD", "ANIO", "MES", "KILOMETRAJE_MES", "TIEMPO_CONDUCIENDO_HORAS", "TIEMPO_DETENIDO_HORAS", "TIEMPO_RALENTI_HORAS"];
            let csvContent = headers.join(",") + "\n";
            csvContent += "ECO-1133,2025,10,9500,180.5,40.2,30.0\n";
            descargarCSV(csvContent, "plantilla_telemetria.csv");
        });
    }
    
    // --- FUNCIÓN AUXILIAR PARA CREAR Y DESCARGAR CSV ---
    function descargarCSV(csvContent, fileName) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", fileName);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
// --- 9. LÓGICA DE UI ADICIONAL (KPI Y BÚSQUEDA EN TABLA) ---
    
    // Lógica del KPI (Desplegable)
    const kpi = document.getElementById("kpi-aprobaciones");
    const lista = document.getElementById("lista-aprobaciones");
    if (kpi && lista) { 
        kpi.addEventListener("click", () => { lista.classList.toggle("mostrar"); }); 
    }

    // --- LÓGICA DE BUSCADOR (FILTRO EN TIEMPO REAL) ---
    const inputBuscar = document.getElementById("buscar-eco");
    const tablaBody = document.querySelector(".tabla-contenido tbody");

    if (inputBuscar && tablaBody) {
        inputBuscar.addEventListener("keyup", function() {
            // 1. Obtenemos el texto y lo convertimos a minúsculas (para que no importen mayúsculas)
            const textoBusqueda = inputBuscar.value.toLowerCase();
            const filas = tablaBody.getElementsByTagName("tr");

            // 2. Recorremos todas las filas de la tabla
            for (let i = 0; i < filas.length; i++) {
                const fila = filas[i];
                
                // Si la fila es un mensaje de "Cargando..." o "No hay datos", no la filtramos
                if (fila.cells.length < 2) continue;

                // 3. Obtenemos todo el texto de la fila (ID + Placas + Estatus + Fecha...)
                const textoFila = fila.textContent.toLowerCase();

                // 4. Comparamos: ¿El texto de la fila incluye lo que escribió el usuario?
                if (textoFila.includes(textoBusqueda)) {
                    fila.style.display = ""; // Mostrar fila
                } else {
                    fila.style.display = "none"; // Ocultar fila
                }
            }
        });
    }








    // --- 10. LÓGICA DE ENVÍO DEL FORMULARIO DE ALTA MANUAL ---
    // (Esta sección está bien y no necesita cambios)
    const formAltaCamion = document.getElementById('form-alta-camion');
    if (formAltaCamion) {
        formAltaCamion.addEventListener('submit', async function(e) {
            e.preventDefault(); 
            const formData = new FormData(formAltaCamion);
            if (formData.get('identificador') === '' || formData.get('placas') === '') {
                alert('Por favor, llena los campos de ID y Placas.');
                return;
            }
            try {
                const response = await fetch('registrar_camion.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    formAltaCamion.reset();
                    const btnCerrar = document.querySelector('#modal-formulario .modal-cerrar');
                    if (btnCerrar) btnCerrar.click();
                } else {
                    alert(`Error al registrar: ${data.message}`);
                }
            } catch (error) {
                console.error('Error en el fetch:', error);
                alert('Error de conexión. No se pudo contactar al servidor.');
            }
        });
    }

    // --- 11. (NUEVO) LÓGICA DE ENVÍO DE ALTA MASIVA (CSV CAMIONES) ---
    const btnGuardarArchivoMasivo = document.getElementById('btn-guardar-archivo-masivo');
    const inputCsvAlta = document.getElementById('input-csv-alta');
    const formAltaArchivo = document.getElementById('form-alta-archivo');

    if (btnGuardarArchivoMasivo && inputCsvAlta && formAltaArchivo) {
        
        inputCsvAlta.addEventListener('change', () => {
            btnGuardarArchivoMasivo.disabled = (inputCsvAlta.files.length === 0);
        });

        btnGuardarArchivoMasivo.addEventListener('click', async (e) => {
            e.preventDefault();
            if (inputCsvAlta.files.length === 0) {
                alert("Por favor, selecciona un archivo CSV de camiones para subir.");
                return;
            }
            
            const formData = new FormData(formAltaArchivo); 
            // El archivo ya está en el formData gracias al 'name="archivo_camiones"'

            btnGuardarArchivoMasivo.disabled = true;
            btnGuardarArchivoMasivo.textContent = 'Procesando...';

            try {
                // Asumimos que creaste 'registrar_camion_masivo.php'
                const response = await fetch('registrar_camion_masivo.php', { 
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    alert("¡Éxito! " + data.message);
                    formAltaArchivo.reset();
                    modalAltaCamion.classList.add('oculto'); // Cierra el modal principal
                } else {
                    alert("Error al procesar el archivo:\n" + data.message);
                }
            } catch (error) {
                console.error('Error en el fetch de alta masiva:', error);
                alert('Error de conexión. No se pudo contactar al servidor.');
            } finally {
                btnGuardarArchivoMasivo.disabled = false;
                btnGuardarArchivoMasivo.textContent = 'Confirmar y Guardar';
                inputCsvAlta.value = ''; 
            }
        });
    }


// --- 12. LÓGICA DE EDICIÓN DE CAMIÓN ---
    
    const modalEditar = document.getElementById('modal-editar');
    
    if (modalEditar) {
        // 1. Lógica para CERRAR el modal (Botón X y Cancelar)
        // Seleccionamos los botones DENTRO de este modal específico
        const btnsCerrarEditar = modalEditar.querySelectorAll('.modal-cerrar, .btn-cerrar-modal');
        
        btnsCerrarEditar.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault(); // Prevenir comportamientos raros
                modalEditar.classList.add('oculto');
            });
        });

        // Cerrar al dar clic fuera (en el fondo oscuro)
        modalEditar.addEventListener('click', e => {
            if (e.target === modalEditar) modalEditar.classList.add('oculto');
        });
    }
    
    // 2. Abrir modal y cargar datos (Event Delegation en la tabla)
    const tablaCuerpo = document.querySelector(".tabla-contenido tbody");
    if (tablaCuerpo) {
        tablaCuerpo.addEventListener('click', async (e) => {
            if (e.target.classList.contains('btn-editar')) {
                const idCamion = e.target.getAttribute('data-id');
                
                // Abrir modal
                if(modalEditar) modalEditar.classList.remove('oculto');
                
                // Cargar datos
                try {
                    const response = await fetch(`api_camiones.php?id=${idCamion}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        const c = data.data;
                        document.getElementById('titulo-eco-editar').textContent = c.numero_economico;
                        document.getElementById('edit_id_camion').value = c.id;
                        document.getElementById('edit_estatus').value = c.estatus;
                        document.getElementById('edit_kilometraje').value = c.kilometraje_total;
                        document.getElementById('edit_placas').value = c.placas;
                        document.getElementById('edit_conductor').value = c.id_interno_conductor || ''; 
                        
                        // Sugerencias de conductor (Reutilizamos la lógica si existe)
                        // (Opcional: Podrías copiar la lógica de sugerencias aquí si la necesitas en el edit también)
                    } else {
                        alert("Error al cargar datos: " + data.message);
                        modalEditar.classList.add('oculto');
                    }
                } catch (error) {
                    console.error(error);
                    alert("Error de conexión al cargar camión.");
                    modalEditar.classList.add('oculto');
                }
            }
        });
    }

    // 3. Guardar cambios
    const formEditar = document.getElementById('form-editar-camion');
    if (formEditar) {
        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formEditar);
            
            try {
                const response = await fetch('editar_camion.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Verificar si la respuesta es válida antes de parsear
                if (!response.ok) throw new Error("Error HTTP: " + response.status);
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    modalEditar.classList.add('oculto');
                    cargarTablaCamiones(); // Recargar la tabla para ver los cambios
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) {
                console.error(error);
                alert("Error de conexión al guardar. Revisa la consola para más detalles.");
            }
        });
    }



// --- 13. BÚSQUEDA INTELIGENTE EN EDICIÓN ---
    const inputConductorEdit = document.getElementById("edit_conductor");
    const listaSugerenciasEdit = document.getElementById("sugerencias-conductor-edit");

    if (inputConductorEdit && listaSugerenciasEdit) {
        
        // Evento al escribir
        inputConductorEdit.addEventListener("input", () => {
            const valor = inputConductorEdit.value.trim().toUpperCase();
            listaSugerenciasEdit.innerHTML = ""; 
            
            if (valor === "") {
                listaSugerenciasEdit.style.display = "none";
                return;
            }

            // Filtramos de la lista global 'conductoresData' que ya cargamos al inicio
            const filtrados = conductoresData.filter(c =>
                c.id.toUpperCase().includes(valor) || c.nombre.includes(valor)
            );

            if (filtrados.length > 0) {
                listaSugerenciasEdit.style.display = "block";
                filtrados.forEach(c => {
                    const item = document.createElement("div");
                    // Mostramos Nombre y ID para mayor claridad
                    item.textContent = `${c.nombre} (${c.id})`; 
                    
                    item.addEventListener("click", () => {
                        inputConductorEdit.value = c.id; // Al hacer clic, ponemos el ID (CON-XXX)
                        listaSugerenciasEdit.style.display = "none";
                    });
                    listaSugerenciasEdit.appendChild(item);
                });
            } else {
                listaSugerenciasEdit.style.display = "block";
                const noRes = document.createElement("div");
                noRes.textContent = "Sin coincidencias";
                noRes.style.color = "#888";
                noRes.style.padding = "8px";
                listaSugerenciasEdit.appendChild(noRes);
            }
        });

        // Cerrar lista al hacer clic fuera
        document.addEventListener("click", (e) => {
            if (!listaSugerenciasEdit.contains(e.target) && e.target !== inputConductorEdit) {
                listaSugerenciasEdit.style.display = "none";
            }
        });
    }







}); // FIN DEL DOMContentLoaded