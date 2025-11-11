/**
 * scripts.js - VERSIÓN COMPLETA (v5)
 * - Incluye lógica para modal de telemetría independiente.
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
    const modal = document.getElementById('modal-formulario');
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnsCerrarModal = document.querySelectorAll('.modal-cerrar, .btn-cerrar-modal');

    if (modal && btnAbrirModal) {
        const abrirModal = () => modal.classList.remove('oculto');
        const cerrarModal = () => modal.classList.add('oculto');
        btnAbrirModal.addEventListener('click', abrirModal);
        btnsCerrarModal.forEach(btn => btn.addEventListener('click', cerrarModal));
        modal.addEventListener('click', e => {
            if (e.target === modal) cerrarModal();
        });
    }

    // --- 2b. LÓGICA DEL MODAL DE SUBIR TELEMETRÍA (INDEPENDIENTE) ---
    // (Esta es la nueva lógica que solicitaste)
    const modalTelemetria = document.getElementById('modal-subir-historial');
    const btnAbrirModalTelemetria = document.getElementById('btn-abrir-modal-telemetria');
    
    if (modalTelemetria && btnAbrirModalTelemetria && btnsCerrarModal.length > 0) {
        
        // Abrir el modal de Telemetría
        btnAbrirModalTelemetria.addEventListener('click', (e) => {
            e.preventDefault();
            modalTelemetria.classList.remove('oculto');
        });

        // Reutilizar los botones de cerrar para este modal también
        btnsCerrarModal.forEach(btn => {
            btn.addEventListener('click', () => {
                modalTelemetria.classList.add('oculto');
            });
        });

        // Cerrar si se da clic fuera
        modalTelemetria.addEventListener('click', e => {
            if (e.target === modalTelemetria) {
                modalTelemetria.classList.add('oculto');
            }
        });
    }

    // --- 2c. LÓGICA PARA ENVIAR EL ARCHIVO DE TELEMETRÍA ---
    // (Esta es la nueva lógica que solicitaste)
    const btnGuardarCsv = document.getElementById('btn-guardar-csv');
    const inputCsvRecorridos = document.getElementById('input-csv-recorridos');

    if (btnGuardarCsv && inputCsvRecorridos) {
        
        // Habilitar el botón de guardar solo si se selecciona un archivo
        inputCsvRecorridos.addEventListener('change', () => {
            if (inputCsvRecorridos.files.length > 0) {
                btnGuardarCsv.disabled = false;
            } else {
                btnGuardarCsv.disabled = true;
            }
        });

        // Enviar el archivo al hacer clic en Guardar
        btnGuardarCsv.addEventListener('click', async (e) => {
            e.preventDefault();

            if (inputCsvRecorridos.files.length === 0) {
                alert("Por favor, selecciona un archivo CSV para subir.");
                return;
            }

            const file = inputCsvRecorridos.files[0];
            const formData = new FormData();
            
            // Esta llave 'archivo_recorridos' DEBE coincidir con el 'name'
            // que pusiste en el HTML y el que espera 'procesar_telemetria.php'
            formData.append('archivo_recorridos', file); 

            btnGuardarCsv.disabled = true;
            btnGuardarCsv.textContent = 'Procesando...';

            try {
                const response = await fetch('procesar_telemetria.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert("¡Éxito! " + data.message);
                    modalTelemetria.classList.add('oculto'); // Cierra el modal
                } else {
                    alert("Error al procesar el archivo:\n" + data.message);
                }

            } catch (error) {
                console.error('Error en el fetch de telemetría:', error);
                alert('Error de conexión. No se pudo contactar al servidor.');
            } finally {
                // Reactivar el botón
                btnGuardarCsv.disabled = false;
                btnGuardarCsv.textContent = 'Confirmar y Guardar';
                inputCsvRecorridos.value = ''; // Limpiar el input
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
            if (condicionVehiculo.value === 'nuevo') {
                camposSoloParaUsado.forEach(campo => { campo.style.display = 'none'; });
            } else {
                camposSoloParaUsado.forEach(campo => { campo.style.display = 'flex'; });
            }
        };
        toggleCamposUsado(); 
        condicionVehiculo.addEventListener('change', toggleCamposUsado); 
    }

    // --- 5. LÓGICA DE CARGA DINÁMICA DE CATÁLOGOS ---
    let conductoresData = []; 
    const selectTecnologia = document.getElementById("tipo_unidad");
    const selectAnio = document.getElementById("anio");
    const selectMarcaAceite = document.getElementById("marca_filtro");
    const selectMarcaCentrifugo = document.getElementById("marca_filtro_centrifugo");
    const selectLubricante = document.getElementById("tipo_aceite"); 

    async function cargarCatalogos() {
        
        // --- Cargar Tecnologías ---
        if (selectTecnologia) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=tecnologias');
                if (!response.ok) throw new Error('Error al cargar tecnologías (HTTP ' + response.status + ')');
                const tecnologias = await response.json();
                selectTecnologia.innerHTML = '<option value="">Selecciona tipo de tecnologia</option>';
                tecnologias.forEach(tec => {
                    const opcion = document.createElement("option");
                    opcion.value = tec.id; 
                    opcion.textContent = tec.nombre.toUpperCase();
                    selectTecnologia.appendChild(opcion);
                });
            } catch (error) {
                console.error('Error en fetch Tecnologías:', error); 
                selectTecnologia.innerHTML = '<option value="">Error al cargar datos</option>';
            }
        }

        // --- Cargar Conductores ---
        try {
            const response = await fetch('fetch_catalogos.php?tipo=conductores');
            if (!response.ok) throw new Error('Error al cargar conductores (HTTP ' + response.status + ')');
            const conductores = await response.json();
            conductoresData = conductores.map(c => ({
                id: c.id_usuario,
                nombre: c.nombre_completo.toUpperCase()
            }));
        } catch (error) {
            console.error('Error en fetch Conductores:', error); 
            conductoresData = [{ id: "ERROR", nombre: "NO SE PUDO CARGAR LA LISTA" }];
        }

        // --- Generar Años ---
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

        // --- Cargar Marcas de Filtro de Aceite ---
        if (selectMarcaAceite) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=filtros_aceite');
                if (!response.ok) throw new Error('Error HTTP ' + response.status);
                const marcas = await response.json();
                selectMarcaAceite.innerHTML = '<option value="">Selecciona una marca</option>';
                marcas.forEach(item => {
                    const opcion = document.createElement("option");
                    opcion.value = item.marca;
                    opcion.textContent = item.marca.toUpperCase();
                    selectMarcaAceite.appendChild(opcion);
                });
            } catch (error) {
                console.error('Error en fetch Filtros Aceite:', error); 
                selectMarcaAceite.innerHTML = '<option value="">Error al cargar marcas</option>';
            }
        }
        
        // --- Cargar Marcas Filtro Centrifugo ---
        if (selectMarcaCentrifugo) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=filtros_centrifugo');
                if (!response.ok) throw new Error('Error HTTP ' + response.status);
                const marcas = await response.json();
                selectMarcaCentrifugo.innerHTML = '<option value="">Selecciona una marca</option>';
                marcas.forEach(item => {
                    const opcion = document.createElement("option");
                    opcion.value = item.marca;
                    opcion.textContent = item.marca.toUpperCase();
                    selectMarcaCentrifugo.appendChild(opcion);
                });
            } catch (error) {
                console.error('Error en fetch Filtros Centrifugo:', error); 
                selectMarcaCentrifugo.innerHTML = '<option value="">Error al cargar marcas</option>';
            }
        }

        // --- Cargar Lubricantes ---
        if (selectLubricante) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=lubricantes');
                if (!response.ok) throw new Error('Error HTTP ' + response.status);
                const lubricantes = await response.json();
                selectLubricante.innerHTML = '<option value="">Selecciona un lubricante</option>';
                lubricantes.forEach(item => {
                    const opcion = document.createElement("option");
                    opcion.value = item.nombre; 
                    opcion.textContent = item.nombre.toUpperCase();
                    selectLubricante.appendChild(opcion);
                });
            } catch (error) {
                console.error('Error en fetch Lubricantes:', error); 
                selectLubricante.innerHTML = '<option value="">Error al cargar lubricantes</option>';
            }
        }
    }
    
    // Llamar a la función al cargar la página
    cargarCatalogos();

    // --- 6. BÚSQUEDA INTELIGENTE DE CONDUCTOR ---
    const inputConductor = document.getElementById("id_conductor");
    const listaSugerencias = document.getElementById("sugerencias-conductor");

    if (inputConductor && listaSugerencias) {
        inputConductor.addEventListener("input", () => {
            const valor = inputConductor.value.trim().toUpperCase();
            listaSugerencias.innerHTML = ""; 
            if (valor === "") {
                listaSugerencias.style.display = "none";
                return;
            }
            const filtrados = conductoresData.filter(c =>
                c.id.toUpperCase().includes(valor) || c.nombre.includes(valor)
            );
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

    // --- 7. LÓGICA DE PESTAÑA ARCHIVO (Modal Alta) ---
    const btnDescargarAlta = document.getElementById('btn-descargar-plantilla-archivo');
    const selectCondicionArchivo = document.getElementById('condicion-archivo');

    if (btnDescargarAlta && selectCondicionArchivo) {
        btnDescargarAlta.addEventListener('click', (e) => {
            e.preventDefault();
            const tipoPlantilla = selectCondicionArchivo.value;
            let csvContent = "";
            let fileName = "";
            const headersNuevos = [
                "numero_economico", "condicion", "placas", "vin", "Marca", "Anio", 
                "id_tecnologia", "ID_Conductor", "estatus", "kilometraje_total", 
                "marca_filtro_aceite_actual", "serie_filtro_aceite_actual", 
                "marca_filtro_centrifugo_actual", "serie_filtro_centrifugo_actual", "lubricante_actual"
            ];
            const headersViejos = [
                "numero_economico", "condicion", "placas", "vin", "Marca", "Anio", 
                "id_tecnologia", "ID_Conductor", "estatus", "kilometraje_total", 
                "fecha_ult_mantenimiento", "fecha_ult_cambio_aceite", 
                "marca_filtro_aceite_actual", "serie_filtro_aceite_actual", 
                "fecha_ult_cambio_centrifugo", "marca_filtro_centrifugo_actual", 
                "serie_filtro_centrifugo_actual", "lubricante_actual"
            ];

            if (tipoPlantilla === 'nuevo') {
                fileName = "plantilla_camiones_nuevos.csv";
                csvContent = headersNuevos.join(",") + "\n";
                csvContent += "ECO-1180,nuevo,PLACA123,VIN123456789,Scania,2025,3,CON-011,trabajando,1500,SCANIA,SCASERIE123,SCANIA,SCASERIE456,SAE 10W30 MULTIGRADO\n";
            } else if (tipoPlantilla === 'usado') {
                fileName = "plantilla_camiones_usados.csv";
                csvContent = headersViejos.join(",") + "\n";
                csvContent += "ECO-1130,usado,PLACA456,VIN987654321,Scania,2021,3,CON-012,trabajando,450000,2025-01-01,2025-05-01,SCANIA,SCASERIE789,2025-05-01,SCANIA,SCASERIE101,SAE 15W30\n";
            } else {
                alert("Por favor, selecciona una condición (Nuevo o Usado) para descargar la plantilla.");
                return;
            }
            descargarCSV(csvContent, fileName);
        });
    }

    // --- 8. LÓGICA DE PESTAÑA ARCHIVO (Modal Telemetría) ---
    const btnDescargarRecorridos = document.getElementById('btn-descargar-recorridos-archivo');
    
    if (btnDescargarRecorridos) {
        btnDescargarRecorridos.addEventListener('click', (e) => {
            e.preventDefault();
            const headers = [
                "UNIDAD", "ANIO", "MES", "KILOMETRAJE_MES", 
                "TIEMPO_CONDUCIENDO_HORAS", "TIEMPO_DETENIDO_HORAS", "TIEMPO_RALENTI_HORAS"
            ];
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

    // --- 9. LÓGICA DE UI ADICIONAL (KPI, BÚSQUEDA TABLA) ---
    // (Código sin cambios)
    const kpi = document.getElementById("kpi-aprobaciones");
    const lista = document.getElementById("lista-aprobaciones");
    if (kpi && lista) { kpi.addEventListener("click", () => { lista.classList.toggle("mostrar"); }); }
    const inputBuscar = document.getElementById("buscar-eco");
    const tabla = document.querySelector(".tabla-contenido tbody");
    if (inputBuscar && tabla) { /* Tu lógica de búsqueda en tabla */ }
    
    // --- 10. LÓGICA DE ENVÍO DEL FORMULARIO DE ALTA MANUAL ---
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
                const response = await fetch('registrar_camion.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    formAltaCamion.reset();
                    // Cierra el modal principal
                    const btnCerrar = document.querySelector('#modal-formulario .modal-cerrar');
                    if (btnCerrar) btnCerrar.click();
                    // (Aquí llamarías a recargar la tabla)
                } else {
                    alert(`Error al registrar: ${data.message}`);
                }

            } catch (error) {
                console.error('Error en el fetch:', error);
                alert('Error de conexión. No se pudo contactar al servidor.');
            }
        });
    }

}); // FIN DEL DOMContentLoaded