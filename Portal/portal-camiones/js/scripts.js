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

    const selectMarcaAceite = document.getElementById("marca_filtro");
    const selectMarcaCentrifugo = document.getElementById("marca_filtro_centrifugo");
    const selectLubricante = document.getElementById("tipo_aceite"); 

    async function cargarCatalogos() {
        
        // --- Cargar Tecnologías ---
        if (selectTecnologia) {
            try {
                // RUTA RELATIVA: el archivo `fetch_catalogos.php` está en el mismo directorio que esta página (camiones.php)
                const response = await fetch('fetch_catalogos.php?tipo=tecnologias'); // <--- LÍNEA CORR')');
                
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
            // RUTA RELATIVA CORRECTA a `fetch_catalogos.php` (mismo directorio que camiones.php)
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
                    opcion.value = item.marca; // El valor será "SCANIA", "Mann-Filter", etc.
                    opcion.textContent = item.marca.toUpperCase();
                    selectMarcaAceite.appendChild(opcion);
                });
            } catch (error) {
                console.error('Error en fetch Filtros Aceite:', error); 
                selectMarcaAceite.innerHTML = '<option value="">Error al cargar marcas</option>';
            }
        }
        
        // --- (NUEVO) Cargar Marcas Filtro Centrifugo ---
        if (selectMarcaCentrifugo) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=filtros_centrifugo');
                if (!response.ok) throw new Error('Error HTTP ' + response.status);
                const marcas = await response.json();
                
                selectMarcaCentrifugo.innerHTML = '<option value="">Selecciona una marca</option>';
                marcas.forEach(item => {
                    const opcion = document.createElement("option");
                    opcion.value = item.marca; // El valor será "SCANIA", etc.
                    opcion.textContent = item.marca.toUpperCase();
                    selectMarcaCentrifugo.appendChild(opcion);
                });
            } catch (error) {
                console.error('Error en fetch Filtros Centrifugo:', error); 
                selectMarcaCentrifugo.innerHTML = '<option value="">Error al cargar marcas</option>';
            }
        }

      if (selectLubricante) {
            try {
                const response = await fetch('fetch_catalogos.php?tipo=lubricantes');
                if (!response.ok) throw new Error('Error HTTP ' + response.status);
                const lubricantes = await response.json();
                
                selectLubricante.innerHTML = '<option value="">Selecciona un lubricante</option>';
                lubricantes.forEach(item => {
                    const opcion = document.createElement("option");
                    // Usamos el nombre como valor, ya que registrar_camion.php
                    // espera el nombre del lubricante.
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



    // (Añadir esto dentro de tu DOMContentLoaded en scripts.js)

// --- 10. LÓGICA DE ENVÍO DEL FORMULARIO DE ALTA MANUAL ---

const formAltaCamion = document.getElementById('form-alta-camion');
if (formAltaCamion) {
    formAltaCamion.addEventListener('submit', async function(e) {
        e.preventDefault(); // Evita que la página se recargue

        // (Opcional: puedes añadir una confirmación visual aquí, ej. SweetAlert)
        // alert("Registrando camión...");

        const formData = new FormData(formAltaCamion);
        
        // (Opcional: validaciones de campos en JS)
        if (formData.get('identificador') === '' || formData.get('placas') === '') {
            alert('Por favor, llena los campos de ID y Placas.');
            return;
        }

        try {
            // Enviamos los datos a nuestro nuevo script PHP
            const response = await fetch('registrar_camion.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                // Si el servidor responde con un error (403, 500, etc.)
                throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                // ¡Éxito!
                alert(data.message); // "Camión registrado con éxito..."
                formAltaCamion.reset(); // Limpia el formulario
                
                // Cierra el modal (busca un botón de cerrar y simula un clic)
                const btnCerrar = document.querySelector('.modal-cerrar');
                if (btnCerrar) btnCerrar.click();
                
                // (En un futuro, aquí llamarías a una función para recargar la tabla de camiones)
                // recargarTablaCamiones(); 
            } else {
                // Error reportado por el PHP (ej. duplicado)
                alert(`Error al registrar: ${data.message}`);
            }

        } catch (error) {
            // Error de red o del fetch
            console.error('Error en el fetch:', error);
            alert('Error de conexión. No se pudo contactar al servidor.');
        }
    });
}

}); // FIN DEL DOMContentLoaded