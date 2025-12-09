/**
 * scripts.js - VERSIÓN DEFINITIVA (v8)
 * - Gestión completa de Camiones (Alta, Baja, Edición, Masivo).
 * - Telemetría.
 * - Historial Clínico Detallado (Entradas, Salidas, Materiales, Fotos).
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
        const cerrarModal = () => {
            modalAltaCamion.classList.add('oculto');
            if(modalTelemetria) modalTelemetria.classList.add('oculto');
        };
        
        btnAbrirModalAlta.addEventListener('click', abrirModal);

        btnsCerrarModal.forEach(btn => btn.addEventListener('click', cerrarModal));
        
        modalAltaCamion.addEventListener('click', e => {
            if (e.target === modalAltaCamion) cerrarModal();
        });
    }

    // --- 2b. LÓGICA DEL MODAL DE SUBIR TELEMETRÍA ---
    const modalTelemetria = document.getElementById('modal-subir-telemetria');
    const btnAbrirModalTelemetria = document.getElementById('btn-abrir-modal-telemetria');
    
    if (modalTelemetria && btnAbrirModalTelemetria) {
        btnAbrirModalTelemetria.addEventListener('click', (e) => {
            e.preventDefault();
            modalTelemetria.classList.remove('oculto');
        });
        
        modalTelemetria.addEventListener('click', e => {
            if (e.target === modalTelemetria) modalTelemetria.classList.add('oculto');
        });
    }

    // --- 2c. LÓGICA PARA ENVIAR EL ARCHIVO DE TELEMETRÍA ---
    const btnGuardarCsvTelemetria = document.getElementById('btn-guardar-csv-telemetria');
    const inputCsvRecorridos = document.getElementById('input-csv-recorridos');

    if (btnGuardarCsvTelemetria && inputCsvRecorridos) {
        inputCsvRecorridos.addEventListener('change', () => {
            btnGuardarCsvTelemetria.disabled = (inputCsvRecorridos.files.length === 0);
        });

        btnGuardarCsvTelemetria.addEventListener('click', async (e) => {
            e.preventDefault();
            if (inputCsvRecorridos.files.length === 0) {
                alert("Por favor, selecciona un archivo CSV de telemetría.");
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
                    let mensajeFinal = "¡Éxito! " + data.message;
                    if (data.lista_duplicados && data.lista_duplicados.length > 0) {
                        mensajeFinal += "\n\n⚠️ ADVERTENCIA: Datos actualizados (Duplicados detectados).\n";
                        mensajeFinal += data.lista_duplicados.slice(0, 5).join("\n");
                        if (data.lista_duplicados.length > 5) mensajeFinal += "\n... y " + (data.lista_duplicados.length - 5) + " más.";
                    }
                    alert(mensajeFinal);
                    modalTelemetria.classList.add('oculto');
                } else {
                    alert("Error al procesar el archivo:\n" + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión.');
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

    // --- 4. OCULTAR CAMPOS (NUEVO/USADO) ---
    const condicionVehiculo = document.getElementById('condicion');
    const camposSoloParaUsado = document.querySelectorAll('.ocultar-si-es-nuevo');
    if (condicionVehiculo && camposSoloParaUsado.length > 0) {
        const toggleCamposUsado = () => {
            if (condicionVehiculo.value === 'nuevo') {
                camposSoloParaUsado.forEach(campo => { campo.classList.add('oculto-simple'); });
            } else {
                camposSoloParaUsado.forEach(campo => { campo.classList.remove('oculto-simple'); });
            }
        };
        toggleCamposUsado(); 
        condicionVehiculo.addEventListener('change', toggleCamposUsado); 
    }

    // --- 5. CARGA DINÁMICA DE CATÁLOGOS ---
    let conductoresData = []; 
    const selectTecnologia = document.getElementById("tipo_unidad");
    const selectAnio = document.getElementById("anio");
    const selectMarcaAceite = document.getElementById("marca_filtro");
    const selectMarcaCentrifugo = document.getElementById("marca_filtro_centrifugo");
    const selectLubricante = document.getElementById("tipo_aceite"); 
    
    async function cargarCatalogos() {
        // Tecnologías
        if (selectTecnologia) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=tecnologias');
                const tecnologias = await response.json();
                selectTecnologia.innerHTML = '<option value="">Selecciona tecnología</option>';
                tecnologias.forEach(tec => {
                    const opt = document.createElement("option");
                    opt.value = tec.id; 
                    opt.textContent = tec.nombre.toUpperCase();
                    selectTecnologia.appendChild(opt);
                });
            } catch (e) { console.error(e); }
        }
        // Conductores
        try {
            const response = await fetch('fetch_catalogos.php?tipo=conductores');
            const conductores = await response.json();
            conductoresData = conductores.map(c => ({ id: c.id_usuario, nombre: c.nombre_completo.toUpperCase() }));
        } catch (e) { console.error(e); }
        
        // Años
        if (selectAnio) {
            const anioActual = new Date().getFullYear();
            selectAnio.innerHTML = '<option value="">Selecciona año</option>'; 
            for (let i = anioActual; i >= 1990; i--) {
                const opt = document.createElement("option");
                opt.value = i; opt.textContent = i;
                selectAnio.appendChild(opt);
            }
        }
        // Filtros y Lubricantes
        const llenarSelect = async (select, tipoFetch, defaultText) => {
            if(select) {
                try {
                    const res = await fetch(`fetch_catalogos.php?tipo=${tipoFetch}`);
                    const items = await res.json();
                    select.innerHTML = `<option value="">${defaultText}</option>`;
                    items.forEach(item => {
                        const opt = document.createElement("option");
                        // Asumiendo que el endpoint devuelve objetos con propiedad 'marca' o 'nombre'
                        const val = item.marca || item.nombre;
                        opt.value = val;
                        opt.textContent = val.toUpperCase();
                        select.appendChild(opt);
                    });
                } catch(e) { console.error(e); }
            }
        };

        llenarSelect(selectMarcaAceite, 'filtros_aceite', 'Selecciona marca');
        llenarSelect(selectMarcaCentrifugo, 'filtros_centrifugo', 'Selecciona marca');
        llenarSelect(selectLubricante, 'lubricantes', 'Selecciona lubricante');
    }
    cargarCatalogos();
    cargarTablaCamiones();

    // --- 5b. CARGAR TABLA PRINCIPAL DE CAMIONES ---
    async function cargarTablaCamiones() {
        const tbody = document.querySelector(".tabla-contenido tbody");
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="6">Cargando camiones...</td></tr>';

        try {
            const response = await fetch('api_camiones.php');
            const datos = await response.json();

            if (!datos.success) throw new Error(datos.message);
            if (datos.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No se encontraron camiones registrados.</td></tr>';
                return;
            }

            tbody.innerHTML = '';

            datos.data.forEach(camion => {
                let estatusClase = '';
                switch (camion.estatus) {
                    case 'Activo': estatusClase = 'estatus-activo'; break;
                    case 'En Taller': estatusClase = 'estatus-taller'; break;
                    case 'Inactivo': estatusClase = 'estatus-inactivo'; break;
                }

                let proximoMto = '<span style="color: #999;">Pendiente cálculo</span>'; 
                if (camion.mantenimiento_requerido === 'Si') {
                    const fechaVenc = camion.fecha_estimada_mantenimiento || 'Fecha desc.';
                    proximoMto = `<span class="mto-requerido">¡URGENTE! (${fechaVenc})</span>`;
                } else if (camion.fecha_estimada_mantenimiento) {
                    proximoMto = `<strong>${camion.fecha_estimada_mantenimiento}</strong>`; 
                }

                const filaHTML = `
                    <tr>
                        <td><strong>${camion.numero_economico}</strong></td>
                        <td>${camion.placas}</td>
                        <td><span class="estatus-tag ${estatusClase}">${camion.estatus}</span></td>
                        <td>${camion.fecha_ult_mantenimiento || 'N/A'}</td>
                        <td>${proximoMto}</td>
                        <td class="acciones">
                            <button class="btn-editar" data-id="${camion.id}">Ver/Editar</button>
                            <button class="btn-historial" data-id="${camion.id}" data-eco="${camion.numero_economico}">📜 Historial</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += filaHTML;
            });

        } catch (error) {
            console.error(error);
            tbody.innerHTML = `<tr><td colspan="6">Error al cargar datos.</td></tr>`;
        }
    }

    // --- 6. SUGERENCIAS DE CONDUCTOR (ALTA) ---
    const inputConductor = document.getElementById("id_conductor");
    const listaSugerencias = document.getElementById("sugerencias-conductor");
    
    const configurarSugerencias = (input, lista) => {
        if (input && lista) {
            input.addEventListener("input", () => {
                const valor = input.value.trim().toUpperCase();
                lista.innerHTML = ""; 
                if (valor === "") { lista.style.display = "none"; return; }
                const filtrados = conductoresData.filter(c => c.id.toUpperCase().includes(valor) || c.nombre.includes(valor));
                
                if (filtrados.length > 0) {
                    lista.style.display = "block";
                    filtrados.forEach(c => {
                        const item = document.createElement("div");
                        item.textContent = `${c.nombre} (${c.id})`; 
                        item.addEventListener("click", () => {
                            input.value = c.id; 
                            lista.style.display = "none";
                        });
                        lista.appendChild(item);
                    });
                } else {
                    lista.style.display = "block";
                    const noRes = document.createElement("div");
                    noRes.textContent = "Sin coincidencias";
                    noRes.style.color = "#888";
                    lista.appendChild(noRes);
                }
            });
            document.addEventListener("click", (e) => {
                if (!lista.contains(e.target) && e.target !== input) lista.style.display = "none";
            });
        }
    };
    
    configurarSugerencias(inputConductor, listaSugerencias);

    // --- 7. DESCARGA DE PLANTILLA ALTA CAMIONES ---
    const btnDescargarAlta = document.getElementById('btn-descargar-plantilla-alta');
    const selectCondicionArchivo = document.getElementById('condicion-archivo');
    if (btnDescargarAlta && selectCondicionArchivo) {
        btnDescargarAlta.addEventListener('click', (e) => {
            e.preventDefault();
            const tipoPlantilla = selectCondicionArchivo.value;
            let csvContent = "";
            let fileName = "";
            // (Headers reducidos para brevedad, usando los que definiste antes)
            const headersBase = "numero_economico,condicion,placas,vin,Marca,Anio,id_tecnologia,ID_Conductor,estatus,kilometraje_total";
            
            if (tipoPlantilla === 'nuevo') {
                fileName = "plantilla_camiones_nuevos.csv";
                csvContent = headersBase + ",marca_filtro_aceite_actual,serie_filtro_aceite_actual,marca_filtro_centrifugo_actual,serie_filtro_centrifugo_actual,lubricante_actual\n";
                csvContent += "ECO-1180,nuevo,PLACA123,VIN12345,Scania,2025,1,CON-011,Activo,1500,SCANIA,SERIE1,SCANIA,SERIE2,SAE 10W30 MULTIGRADO\n";
            } else {
                fileName = "plantilla_camiones_usados.csv";
                csvContent = headersBase + ",fecha_ult_mantenimiento,fecha_ult_cambio_aceite,marca_filtro_aceite_actual,serie_filtro_aceite_actual,fecha_ult_cambio_centrifugo,marca_filtro_centrifugo_actual,serie_filtro_centrifugo_actual,lubricante_actual\n";
                csvContent += "ECO-1130,usado,PLACA456,VIN98765,Scania,2021,1,CON-012,Activo,450000,2025-01-01,2025-05-01,SCANIA,SERIE789,2025-05-01,SCANIA,SERIE101,SAE 15W30\n";
            }
            descargarCSV(csvContent, fileName);
        });
    }

    // --- 8. DESCARGA PLANTILLA TELEMETRÍA ---
    const btnDescargarRecorridos = document.getElementById('btn-descargar-recorridos-archivo');
    if (btnDescargarRecorridos) {
        btnDescargarRecorridos.addEventListener('click', (e) => {
            e.preventDefault();
            const headers = "UNIDAD,ANIO,MES,KILOMETRAJE_MES,TIEMPO_CONDUCIENDO_HORAS,TIEMPO_DETENIDO_HORAS,TIEMPO_RALENTI_HORAS\n";
            const row = "ECO-1133,2025,10,9500,180.5,40.2,30.0\n";
            descargarCSV(headers + row, "plantilla_telemetria.csv");
        });
    }
    
    function descargarCSV(content, fileName) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // --- 9. BÚSQUEDA EN TABLA (FILTRO LOCAL) ---
    const inputBuscar = document.getElementById("buscar-eco");
    if (inputBuscar) {
        inputBuscar.addEventListener("keyup", function() {
            const textoBusqueda = inputBuscar.value.toLowerCase();
            const filas = document.querySelectorAll(".tabla-contenido tbody tr");
            filas.forEach(fila => {
                if(fila.cells.length < 2) return;
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(textoBusqueda) ? "" : "none";
            });
        });
    }

    // --- 10. ENVÍO FORMULARIO ALTA MANUAL ---
    const formAltaCamion = document.getElementById('form-alta-camion');
    if (formAltaCamion) {
        formAltaCamion.addEventListener('submit', async function(e) {
            e.preventDefault(); 
            const formData = new FormData(formAltaCamion);
            try {
                const response = await fetch('registrar_camion.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    formAltaCamion.reset();
                    if(modalAltaCamion) modalAltaCamion.classList.add('oculto');
                    cargarTablaCamiones();
                } else {
                    alert(`Error: ${data.message}`);
                }
            } catch (error) {
                alert('Error de conexión.');
            }
        });
    }

    // --- 11. ENVÍO ALTA MASIVA ---
    const btnGuardarArchivoMasivo = document.getElementById('btn-guardar-archivo-masivo');
    const inputCsvAlta = document.getElementById('input-csv-alta');
    const formAltaArchivo = document.getElementById('form-alta-archivo');

    if (btnGuardarArchivoMasivo && inputCsvAlta && formAltaArchivo) {
        inputCsvAlta.addEventListener('change', () => btnGuardarArchivoMasivo.disabled = (inputCsvAlta.files.length === 0));

        btnGuardarArchivoMasivo.addEventListener('click', async (e) => {
            e.preventDefault();
            if (inputCsvAlta.files.length === 0) return alert("Selecciona un archivo.");
            
            const formData = new FormData(formAltaArchivo); 
            btnGuardarArchivoMasivo.disabled = true;
            btnGuardarArchivoMasivo.textContent = 'Procesando...';

            try {
                const response = await fetch('registrar_camion_masivo.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    alert("¡Éxito! " + data.message);
                    formAltaArchivo.reset();
                    modalAltaCamion.classList.add('oculto');
                    cargarTablaCamiones();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) {
                alert('Error de conexión.');
            } finally {
                btnGuardarArchivoMasivo.disabled = false;
                btnGuardarArchivoMasivo.textContent = 'Confirmar y Guardar';
                inputCsvAlta.value = ''; 
            }
        });
    }

    // --- 12. LÓGICA DE EDICIÓN Y EVENTOS DE TABLA ---
    const modalEditar = document.getElementById('modal-editar');
    
    // Cerrar modal editar
    if (modalEditar) {
        modalEditar.querySelectorAll('.modal-cerrar, .btn-cerrar-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                modalEditar.classList.add('oculto');
            });
        });
        modalEditar.addEventListener('click', e => {
            if (e.target === modalEditar) modalEditar.classList.add('oculto');
        });
    }

    // EVENT DELEGATION EN TABLA (PARA EDITAR E HISTORIAL)
    const tablaCuerpo = document.querySelector(".tabla-contenido tbody");
    if (tablaCuerpo) {
        tablaCuerpo.addEventListener('click', async (e) => {
            
            // A. BOTÓN EDITAR
            if (e.target.classList.contains('btn-editar')) {
                const idCamion = e.target.getAttribute('data-id');
                if(modalEditar) modalEditar.classList.remove('oculto');
                
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
                    } else {
                        alert("Error al cargar datos.");
                        modalEditar.classList.add('oculto');
                    }
                } catch (error) {
                    alert("Error de conexión.");
                    modalEditar.classList.add('oculto');
                }
            }

            // B. BOTÓN HISTORIAL
            if (e.target.classList.contains('btn-historial')) {
                const id = e.target.getAttribute('data-id');
                const eco = e.target.getAttribute('data-eco');
                verHistorial(id, eco); // Llamamos a la función dedicada
            }
        });
    }

    // Guardar Edición
    const formEditar = document.getElementById('form-editar-camion');
    if (formEditar) {
        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formEditar);
            try {
                const response = await fetch('editar_camion.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    modalEditar.classList.add('oculto');
                    cargarTablaCamiones();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) {
                alert("Error de conexión.");
            }
        });
    }

    // Configurar sugerencias en modal de edición
    configurarSugerencias(document.getElementById("edit_conductor"), document.getElementById("sugerencias-conductor-edit"));


    // =========================================================
    // 13. FUNCIÓN VER HISTORIAL (MODAL)
    // =========================================================
    async function verHistorial(idCamion, numeroEconomico) {
        const modal = document.getElementById('modal-historial');
        const container = document.getElementById('timeline-container');
        const titulo = document.getElementById('titulo-historial');
        
        modal.style.display = 'flex'; // Usamos flex para centrar si está definido en CSS
        titulo.textContent = `Expediente de Unidad: ${numeroEconomico}`;
        container.innerHTML = '<p style="text-align:center; padding:20px;">🔄 Buscando expedientes...</p>';

        try {
            const res = await fetch(`php/get_historial_camion.php?id=${idCamion}`);
            const resp = await res.json();

            if (!resp.success) {
                container.innerHTML = `<p style="color:red;">Error: ${resp.message}</p>`;
                return;
            }

            const datos = resp.data;

            if (datos.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:30px; color:#666;">📭 Este camión no tiene registros de mantenimiento en el sistema.</div>';
                return;
            }

            let html = '';

            datos.forEach(item => {
                let colorBorde = item.estatus_entrada === 'Entregado' ? '#2ecc71' : '#f39c12';
                
// Botones Miniatura
                let botonesFotos = '';
                if (item.fotos && item.fotos.length > 0) {
                    botonesFotos = '<div class="mini-galeria">';
                    item.fotos.forEach(f => {
                        
                        // --- CORRECCIÓN DE RUTA DE IMAGEN ---
                        // La BD tiene "../uploads/...", pero nosotros estamos en "Portal/portal-camiones/"
                        // Necesitamos subir 2 niveles ("../../") para llegar a "uploads/"
                        let url = f.ruta_archivo;
                        
                        // Si la ruta empieza con un solo "../", le agregamos otro para salir de 'portal-camiones'
                        if (url.startsWith('../uploads')) {
                            url = '../' + url; // Resultado: ../../uploads/...
                        } 
                        // Si por alguna razón tiene tres "../../../" (a veces pasa en saves profundos), lo ajustamos a dos
                        else if (url.startsWith('../../../')) {
                            url = url.replace('../../../', '../../');
                        }
                        // ------------------------------------

                        let icono = '📷';
                        let tooltip = f.tipo_foto;
                        
                        if (f.tipo_foto.includes('Viejos')) { icono = '🗑️'; tooltip = 'Piezas Retiradas'; }
                        else if (f.tipo_foto.includes('Nuevos')) { icono = '✨'; tooltip = 'Piezas Nuevas'; }
                        else if (f.tipo_foto.includes('Cubetas')) { icono = '🛢️'; tooltip = 'Aceite Usado'; }
                        
                        botonesFotos += `<a href="${url}" target="_blank" class="btn-mini-foto" title="${tooltip}">${icono}</a>`;
                    });
                    botonesFotos += '</div>';
                } else {
                    botonesFotos = '<span style="color:#aaa; font-size:0.8em">Sin evidencia</span>';
                }

                html += `
                    <div class="card-historial" style="border-left: 5px solid ${colorBorde};">
                        <div class="hist-header">
                            <span class="folio">Folio: ${item.folio}</span>
                            <span class="fecha">${new Date(item.fecha_ingreso).toLocaleDateString()}</span>
                            <span class="duracion">⏱️ ${item.duracion}</span>
                        </div>
                        <div class="hist-body">
                            <div class="col">
                                <strong>Servicio:</strong><br> ${item.tipo_mantenimiento_solicitado}<br>
                                <small>Responsable: ${item.mecanico || 'N/A'}</small>
                            </div>
                            <div class="col">
                                <strong>Refacciones:</strong>
                                <ul class="lista-mini">
                                    <li>🛢️ Aceite: ${item.cubeta_1_entregada ? 'Sí' : '-'}</li>
                                    <li>⚙️ Filtro: ${item.filtro_aceite_entregado || '-'}</li>
                                </ul>
                            </div>
                            <div class="col-fotos" style="text-align:right;">
                                ${botonesFotos}
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;

        } catch(e) {
            console.error(e);
            container.innerHTML = '<p style="color:red">Error al cargar el historial.</p>';
        }
    }

    // Función global para cerrar historial (por si se llama desde onclick en HTML)
    window.cerrarHistorial = function() {
        document.getElementById('modal-historial').style.display = 'none';
    };

    // Cerrar historial con clic fuera
    const modalHistorial = document.getElementById('modal-historial');
    if(modalHistorial) {
        modalHistorial.addEventListener('click', (e) => {
            if (e.target === modalHistorial) modalHistorial.style.display = 'none';
        });
    }

});