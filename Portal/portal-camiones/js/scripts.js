document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA PARA EL MENÚ DROPDOWN ---
    document.addEventListener('click', function(e) {
        const isDropdownToggle = e.target.matches('.dropdown-toggle');
        // Si se hace clic fuera de un menú desplegable, cierra todos los que estén abiertos.
        if (!isDropdownToggle && e.target.closest('.dropdown') === null) {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => dropdown.classList.remove('active'));
        }
        // Si se hace clic en un botón de desplegable.
        if (isDropdownToggle) {
            const parent = e.target.closest('.dropdown');
            // Alterna la clase 'active' para mostrar u ocultar el submenú.
            parent.classList.toggle('active');
            // Cierra cualquier otro menú que estuviera abierto.
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                if (dropdown !== parent) dropdown.classList.remove('active');
            });
        }
    });

    // --- LÓGICA DEL MODAL DE ALTA DE CAMIÓN ---
    const modal = document.getElementById('modal-formulario');
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnsCerrarModal = document.querySelectorAll('.modal-cerrar, .btn-cerrar-modal');

    if (modal && btnAbrirModal) {
        const abrirModal = () => modal.classList.remove('oculto');
        const cerrarModal = () => modal.classList.add('oculto');
        btnAbrirModal.addEventListener('click', abrirModal);
        btnsCerrarModal.forEach(btn => btn.addEventListener('click', cerrarModal));
        // Cierra el modal si se hace clic fuera del contenido.
        modal.addEventListener('click', e => {
            if (e.target === modal) cerrarModal();
        });
    }

    // --- LÓGICA DE PESTAÑAS (MANUAL / ARCHIVO) ---
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

    // --- NUEVA LÓGICA PARA EL FORMULARIO DE CAMIONES ---
    const condicionVehiculo = document.getElementById('condicion');
    const camposCamionUsado = document.getElementById('campos-camion-usado');

    if (condicionVehiculo && camposCamionUsado) {
        // Función para actualizar la visibilidad de los campos.
        const toggleCamposUsado = () => {
            // Si el valor es 'nuevo', oculta los campos; si es 'usado', los muestra.
            if (condicionVehiculo.value === 'nuevo') {
                camposCamionUsado.style.display = 'none';
            } else {
                camposCamionUsado.style.display = 'block';
            }
        };

        // Llama a la función al cargar la página para establecer el estado inicial.
        toggleCamposUsado();

        // Añade un 'listener' para que cambie cada vez que el usuario selecciona una opción.
        condicionVehiculo.addEventListener('change', toggleCamposUsado);
    }

    // --- LÓGICA ACTUALIZADA PARA DESCARGAR LA PLANTILLA CSV ---
    const btnDescargarPlantilla = document.getElementById('btn-descargar-plantilla');

    if (btnDescargarPlantilla) {
        btnDescargarPlantilla.addEventListener('click', () => {
            // Columnas actualizadas para la plantilla.
            const headers = "Condicion_del_vehiculo,ID_Camion,Placas,VIN,Condicion,Marca,Modelo,Anio,Tipo_Tecnologia,Estatus_Inicial,ID_Conductor,Kilometros_Recorridos,Ultimo_Mantenimiento,Ultimo_Cambio_Filtro,Marca_Filtro,Serie_Filtro_Aceite";
            // Fila de ejemplo con los nuevos campos.
            const exampleRow = "Usado / Nuevo, ECO-999,ABC-123,1G9BS82C0XE178459,Usado,Scania,Irizar,2022,scania_i5,trabajando,COND-045,150000,2023-10-15,2023-11-01,Gonher,G-123,GA-456";
            
            const csvContent = "data:text/csv;charset=utf-8," + headers + "\n" + exampleRow;
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "plantilla_alta_camiones.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // --- LÓGICA PARA CARGA MASIVA Y PREVISUALIZACIÓN DE CSV ---
    const inputCsvAlta = document.getElementById('input-csv-alta');
    const previewContainer = document.getElementById('preview-container');
    const btnGuardarCsv = document.getElementById('btn-guardar-csv');

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
                    if (previewContainer) previewContainer.innerHTML = "<p>No se encontraron datos en el archivo o el formato es incorrecto.</p>";
                    if (btnGuardarCsv) btnGuardarCsv.disabled = true;
                }
            };
            reader.readAsText(file);
        });
    }

    // Función para convertir el texto CSV en un objeto JSON.
    function parseCSV(text) {
        const lines = text.trim().replace(/\r/g, "").split('\n');
        if (lines.length < 2) return []; // Necesita al menos encabezado y una fila de datos.
        const headers = lines[0].split(',').map(h => h.trim());
        const rows = [];
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(',').map(v => v.trim());
            if (values.length === headers.length) {
                const row = {};
                headers.forEach((header, index) => {
                    row[header] = values[index];
                });
                rows.push(row);
            }
        }
        return rows;
    }

    // Función para mostrar la previsualización del CSV en una tabla HTML.
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
});