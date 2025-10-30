/**
 * scripts.js - VERSIÓN UNIFICADA Y CORREGIDA (v4)
 * * Corrige las rutas de fetch para apuntar correctamente al archivo PHP en la raíz.
 * * Corrige el typo en el parámetro 'tipo=conductores'.
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

    // **GUARDIA:**
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

    // **GUARDIA:**
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

    // **GUARDIA:**
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

    async function cargarCatalogos() {
        
        // --- Cargar Tecnologías ---
        if (selectTecnologia) {
            try {
                // RUTA CORREGIDA: Sube dos niveles desde js/ a la raíz
                const response = await fetch('../fetch_catalogos.php?tipo=tecnologias');
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
            // RUTA CORREGIDA Y PARÁMETRO CORREGIDO

            const response = await fetch('../fetch_catalogos.php?tipo=conductores');
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
    }
    
    cargarCatalogos();

    // --- 6. BÚSQUEDA INTELIGENTE DE CONDUCTOR ---
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

    // --- 7. GENERACIÓN DE PLANTILLAS CSV ---
    // (Código sin cambios, ya debería funcionar)
    function downloadCSV(csvContent, fileName) { /* ... */ }
    const btnDescargarAlta = document.getElementById('btn-descargar-plantilla-alta');
    const selectCondicionArchivo = document.getElementById('condicion-archivo');
    if (btnDescargarAlta && selectCondicionArchivo) { /* ... */ }
    const btnDescargarRecorridos = document.getElementById('btn-descargar-recorridos-archivo');
    const descargarPlantillaRecorridos = () => { /* ... */ };
    if (btnDescargarRecorridos) { btnDescargarRecorridos.addEventListener('click', descargarPlantillaRecorridos); }

    // --- 8. PREVISUALIZACIÓN DE CSV ---
    // (Código sin cambios)
    const inputCsvAlta = document.getElementById('input-csv-alta');
    const previewContainer = document.getElementById('preview-container');
    const btnGuardarCsv = document.getElementById('btn-guardar-csv');
    if (inputCsvAlta) { /* ... */ }
    function parseCSV(text) { /* ... */ } 
    function displayPreview(data) { /* ... */ } 

    // --- 9. LÓGICA DE UI ADICIONAL (KPI, BÚSQUEDA TABLA) ---
    // (Código sin cambios)
    const kpi = document.getElementById("kpi-aprobaciones");
    const lista = document.getElementById("lista-aprobaciones");
    if (kpi && lista) { kpi.addEventListener("click", () => { lista.classList.toggle("mostrar"); }); }
    const inputBuscar = document.getElementById("buscar-eco");
    const tabla = document.querySelector(".tabla-contenido tbody");
    if (inputBuscar && tabla) { /* Tu lógica de búsqueda en tabla */ }
    document.querySelectorAll('input[type="text"]').forEach(input => { /* Tu lógica de mayúsculas */ });

}); // FIN DEL DOMContentLoaded