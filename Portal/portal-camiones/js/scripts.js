/**
 * scripts.js - VERSIÓN UNIFICADA Y CORREGIDA
 * * Maneja toda la interactividad del portal de camiones.
 * 1. Unifica todos los 'DOMContentLoaded' en uno solo.
 * 2. Añade "guardias" (if (elemento)) para prevenir errores 'null'.
 * 3. Conecta a 'api/fetch_catalogos.php' para datos reales.
 * 4. Implementa la descarga de plantillas dinámicas.
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

    // **GUARDIA:** Solo ejecuta si los botones del modal existen
    if (modal && btnAbrirModal) {
        const abrirModal = () => modal.classList.remove('oculto');
        const cerrarModal = () => modal.classList.add('oculto');
        btnAbrirModal.addEventListener('click', abrirModal);
        btnsCerrarModal.forEach(btn => btn.addEventListener('click', cerrarModal));
        modal.addEventListener('click', e => {
            if (e.target === modal) cerrarModal();
        });
    }

    // --- 3. LÓGICA DE PESTAÑAS (MANUAL / ARCHIVO) ---
    const tabLinks = document.querySelectorAll('.carga-link');
    const tabContents = document.querySelectorAll('.tab-content');

    // **GUARDIA:** Solo ejecuta si hay pestañas
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

    // --- 4. LÓGICA FORMULARIO MANUAL: MOSTRAR/OCULTAR CAMPOS USADO ---
    const condicionVehiculo = document.getElementById('condicion');
    const camposCamionUsado = document.getElementById('campos-camion-usado');

    // **GUARDIA:** Solo ejecuta si el formulario de alta manual existe
    if (condicionVehiculo && camposCamionUsado) {
        const toggleCamposUsado = () => {
            camposCamionUsado.style.display = (condicionVehiculo.value === 'nuevo') ? 'none' : 'block';
        };
        toggleCamposUsado();
        condicionVehiculo.addEventListener('change', toggleCamposUsado);
    }

    // --- 5. LÓGICA DE CARGA DINÁMICA DE CATÁLOGOS (CONECTADO A PHP) ---
    let conductoresData = []; // Variable global para la búsqueda
    const selectTecnologia = document.getElementById("tipo_unidad");
    const selectAnio = document.getElementById("anio");

    /**
     * Carga catálogos desde la BD (Tecnologías, Conductores) y genera Años
     */
    async function cargarCatalogos() {
        
        // --- Cargar Tecnologías ---
        if (selectTecnologia) {
            try {
                const response = await fetch('api/fetch_catalogos.php?tipo=tecnologias');
                if (!response.ok) throw new Error('Error al cargar tecnologías');
                const tecnologias = await response.json();
                
                selectTecnologia.innerHTML = '<option value="">Selecciona tipo de tecnologia</option>';
                tecnologias.forEach(tec => {
                    const opcion = document.createElement("option");
                    opcion.value = tec.id; // Asumimos que guardas el ID
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
            const response = await fetch('api/fetch_catalogos.php?tipo=conductores');
            if (!response.ok) throw new Error('Error al cargar conductores');
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
            selectAnio.innerHTML = '<option value="">Selecciona año</option>'; // Limpiar
            for (let i = anioActual; i >= 1990; i--) {
                const opcion = document.createElement("option");
                opcion.value = i;
                opcion.textContent = i;
                selectAnio.appendChild(opcion);
            }
        }
    }
    
    // Llama a la función principal
    cargarCatalogos();

    // --- 6. BÚSQUEDA INTELIGENTE DE CONDUCTOR (CONECTADO A PHP) ---
    const inputConductor = document.getElementById("id_conductor");
    const listaSugerencias = document.getElementById("sugerencias-conductor");

    // **GUARDIA:**
    if (inputConductor && listaSugerencias) {
        inputConductor.addEventListener("input", () => {
            const valor = inputConductor.value.trim().toUpperCase();
            listaSugerencias.innerHTML = ""; 
            if (valor === "") {
                listaSugerencias.style.display = "none";
                return;
            }

            // Filtra sobre la variable global `conductoresData` (cargada en cargarCatalogos)
            const filtrados = conductoresData.filter(c =>
                c.id.toUpperCase().includes(valor) ||
                c.nombre.includes(valor)
            );

            if (filtrados.length > 0) {
                listaSugerencias.style.display = "block";
                filtrados.forEach(c => {
                    const item = document.createElement("div");
                    item.textContent = c.nombre; // ej: "JUAN PEREZ (C-001)"
                    item.addEventListener("click", () => {
                        inputConductor.value = c.id; // Guarda "C-001"
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

    // --- 7. GENERACIÓN DE PLANTILLAS CSV ---
    
    // Función auxiliar para descargar
    function downloadCSV(csvContent, fileName) {
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", "data:text/csv;charset=utf-8," + encodedUri);
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // --- Plantilla de ALTA (Dinámica) ---
    // (Tu script usa 'btn-descargar-plantilla', pero tu HTML usa 'btn-descargar-plantilla-alta'. Usaré el ID correcto del HTML.)
    const btnDescargarAlta = document.getElementById('btn-descargar-plantilla-alta');
    const selectCondicionArchivo = document.getElementById('condicion-archivo');

    // **GUARDIA:**
    if (btnDescargarAlta && selectCondicionArchivo) {
        btnDescargarAlta.addEventListener('click', () => {
            const condicion = selectCondicionArchivo.value;
            let headers, exampleRow, fileName;

            if (condicion === 'nuevo') {
                headers = "ID_del_Vehiculo,Placas,Numero_de_Serie_VIN,ID_del_Conductor_Asignado,Marca,Modelo_Anio,Tecnologia_de_carroceria,Estatus_Inicial,Kilometros_Recorridos,Marca_de_Filtro,Numero_de_Serie_Filtro";
                exampleRow = "ECO-100,NUE-123,1G9BS82C0XE178459,C-001,SCANIA,2025,IRIZAR i6,TRABAJANDO,150000,GONHER,G-123";
                fileName = "plantilla_alta_NUEVOS.csv";
            } else {
                headers = "ID_del_Vehiculo,Placas,Numero_de_Serie_VIN,ID_del_Conductor_Asignado,Marca,Modelo_Anio,Tecnologia_de_carroceria,Estatus_Inicial,Kilometros_Recorridos,Ultimo_Mantenimiento_General(YYYY-MM-DD),Ultimo_Cambio_de_Filtro(YYYY-MM-DD),Marca_de_Filtro,Numero_de_Serie_Filtro";
                exampleRow = "ECO-101,ABC-456,1G9AS82C0XE178123,C-002,MAN,2022,IRIZAR i6s 15 MT,MANTENIMIENTO,150000,2025-01-15,2025-03-30,GONHER,G-123";
                fileName = "plantilla_alta_USADOS.csv";
            }
            downloadCSV(headers + "\n" + exampleRow, fileName);
        });
    }

    // --- Plantilla de RECORRIDOS ---
    const btnDescargarRecorridos = document.getElementById('btn-descargar-recorridos-archivo');
    const descargarPlantillaRecorridos = () => {
        const headers = "unidad,anio,mes,kilometraje,Cond_D,Cond_H,Cond_M,Det_D,Det_H,Det_M,Rel_D,Rel_H,Rel_M";
        const exampleRow = "ECO-101,2025,10,12500,0,0,0,0,0,0,0,0,0";
        downloadCSV(headers + "\n" + exampleRow, "plantilla_historial_recorridos.csv");
    };

    if (btnDescargarRecorridos) {
        btnDescargarRecorridos.addEventListener('click', descargarPlantillaRecorridos);
    }
    
    // --- 8. PREVISUALIZACIÓN DE CSV ---
    const inputCsvAlta = document.getElementById('input-csv-alta');
    const previewContainer = document.getElementById('preview-container');
    const btnGuardarCsv = document.getElementById('btn-guardar-csv');

    // **GUARDIA:**
    if (inputCsvAlta) {
        inputCsvAlta.addEventListener('change', event => {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const data = parseCSV(text);
                if (data.length > 0) {
                    displayPreview(data);
                    if (btnGuardarCsv) btnGuardarCsv.disabled = false;
                } else {
                    if (previewContainer) previewContainer.innerHTML = "<p>No se encontraron datos...</p>";
                    if (btnGuardarCsv) btnGuardarCsv.disabled = true;
                }
            };
            reader.readAsText(file);
        });
    }

    function parseCSV(text) {
        const lines = text.trim().replace(/\r/g, "").split('\n');
        if (lines.length < 2) return []; 
        const headers = lines[0].split(',').map(h => h.trim());
        const rows = [];
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(',').map(v => v.trim());
            if (values.length === headers.length) {
                let row = {};
                headers.forEach((header, index) => { row[header] = values[index]; });
                rows.push(row);
            }
        }
        return rows;
    }

    function displayPreview(data) {
        if (!previewContainer) return;
        const headers = Object.keys(data[0]);
        let table = '<table class="preview-table"><thead><tr>';
        headers.forEach(h => table += `<th>${h.replace(/_/g, ' ')}</th>`);
        table += '</tr></thead><tbody>';
        data.forEach(row => {
            table += '<tr>';
            headers.forEach(h => table += `<td>${row[h]}</td>`);
            table += '</tr>';
        });
        table += '</tbody></table>';
        previewContainer.innerHTML = table;
    }

    // --- 9. LÓGICA DE UI ADICIONAL (KPI, BÚSQUEDA TABLA) ---
    
    // --- Mostrar / ocultar lista de aprobaciones ---
    const kpi = document.getElementById("kpi-aprobaciones");
    const lista = document.getElementById("lista-aprobaciones");

    // **GUARDIA:**
    if (kpi && lista) {
        kpi.addEventListener("click", () => {
            lista.classList.toggle("mostrar");
        });
    }

    // --- Búsqueda en tabla principal ---
    const inputBuscar = document.getElementById("buscar-eco");
    const tabla = document.querySelector(".tabla-contenido tbody");

    // **GUARDIA:** (Esta es la que causaba el error)
    if (inputBuscar && tabla) {
        
        // (Aquí deberías cargar tus camiones iniciales con un fetch a la BD)
        const camiones_simulados = [
            { id: "ECO-101", placas: "ABC123", estatus: "En taller", ultima: "2025-10-10", proximo: "2025-11-15" },
            { id: "ECO-203", placas: "XYZ456", estatus: "Listo", ultima: "2025-09-22", proximo: "2026-01-01" },
            { id: "ECO-319", placas: "JKL789", estatus: "En espera", ultima: "2025-10-01", proximo: "2025-12-10" },
        ];
        
        function mostrarCamiones(lista) {
            tabla.innerHTML = "";
            if (lista.length === 0) {
                tabla.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#999;">No se encontraron resultados</td></tr>`;
                return;
            }
            lista.forEach(c => {
                // Genera el estatus con la clase correcta
                let estatusClass = c.estatus.toLowerCase().replace(/ /g, '-');
                let estatusTexto = c.estatus;

                if(estatusClass === 'en-taller') estatusClass = 'mantenimiento';
                if(estatusClass === 'listo') estatusClass = 'trabajando';
                if(estatusClass === 'en-espera') estatusClass = 'inactivo';

                const fila = `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.placas}</td>
                        <td><span class="estatus ${estatusClass}">${estatusTexto}</span></td>
                        <td>${c.ultima}</td>
                        <td>${c.proximo}</td>
                        <td><button class="btn-detalles">Ver</button></td>
                    </tr>`;
                tabla.innerHTML += fila;
            });
        }
        
        // Carga inicial
        mostrarCamiones(camiones_simulados);

        // Búsqueda inteligente en tiempo real
        inputBuscar.addEventListener("input", () => {
            const valor = inputBuscar.value.trim().toLowerCase();
            if (valor === "") {
                mostrarCamiones(camiones_simulados);
                return;
            }
            const filtrados = camiones_simulados.filter(c => 
                c.id.toLowerCase().includes(valor) ||
                c.placas.toLowerCase().includes(valor) ||
                c.estatus.toLowerCase().includes(valor)
            );
            mostrarCamiones(filtrados);
        });
    }
    
    // --- Forzar mayúsculas en inputs ---
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.addEventListener("input", () => {
            // No forzar en el buscador para una búsqueda más natural
            if(input.id !== 'buscar-eco') {
                 input.value = input.value.toUpperCase();
            }
        });
    });

});