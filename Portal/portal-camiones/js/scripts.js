document.addEventListener('DOMContentLoaded', function() {

    // --- LÓGICA PARA EL MENÚ DROPDOWN ---
    document.addEventListener('click', function(e) {
        // Si el clic no es en un toggle, cierra todos los submenús
        if (!e.target.matches('.dropdown-toggle')) {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            return;
        }

        const parent = e.target.closest('.dropdown');
        
        // Cierra otros submenús que puedan estar abiertos
        document.querySelectorAll('.dropdown.active').forEach(dropdown => {
            if (dropdown !== parent) {
                dropdown.classList.remove('active');
            }
        });

        // Alterna la clase 'active' solo en el dropdown clickeado
        parent.classList.toggle('active');
    });

    // --- LÓGICA PARA EL MOD DE ALTA DE CAMIÓN ---
    const modal = document.getElementById('modo-formulario');
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const btnCancelarModal = document.getElementById('btn-cancelar-modal');

    // Función para abrir el modal
    function abrirModal() {
        if (modal) {
            modal.classList.remove('oculto');
        }
    }

    // Función para cerrar el modal
    function cerrarModal() {
        if (modal) {
            modal.classList.add('oculto');
        }
    }

    // Asignar eventos a los botones
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModal);
    }
    
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
    }

    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', cerrarModal);
    }

    // Opcional: Cerrar el modal si se hace clic en el fondo oscuro
    if (modal) {
        modal.addEventListener('click', function(e) {
            // Si el clic fue directamente en el fondo (overlay)
            if (e.target === modal) {
                cerrarModal();
            }
        });
    }});




    document.addEventListener('DOMContentLoaded', function() {

    // --- LÓGICA GENERAL DEL MODAL (ABRIR/CERRAR) ---
    const modal = document.getElementById('modal-formulario');
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnsCerrarModal = document.querySelectorAll('.modal-cerrar, .btn-cancelar-modal');

    function abrirModal() {
        if (modal) modal.classList.remove('oculto');
    }
    function cerrarModal() {
        if (modal) modal.classList.add('oculto');
    }

    btnAbrirModal.addEventListener('click', abrirModal);
    btnsCerrarModal.forEach(btn => btn.addEventListener('click', cerrarModal));
    modal.addEventListener('click', e => {
        if (e.target === modal) cerrarModal();
    });

    // --- LÓGICA DE PESTAÑAS (TABS) ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.getAttribute('data-tab');

            // Desactivar todas las pestañas y contenidos
            tabLinks.forEach(item => item.classList.remove('active'));
            tabContents.forEach(item => item.classList.remove('active'));

            // Activar la pestaña y contenido seleccionados
            link.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // --- LÓGICA PARA CARGA MASIVA (CSV) ---
    const btnDescargarPlantilla = document.getElementById('btn-descargar-plantilla');
    const inputCsvAlta = document.getElementById('input-csv-alta');
    const previewContainer = document.getElementById('preview-container');
    const btnGuardarCsv = document.getElementById('btn-guardar-csv');

    // 1. Descargar Plantilla
    btnDescargarPlantilla.addEventListener('click', () => {
        // Encabezados de la plantilla. ¡Deben coincidir con tu base de datos!
        const headers = "ID_Camion,Placas,VIN,Tipo_Tecnologia,Marca,Modelo,Anio,Ruta_Asignada,Chofer_Asignado,Tipo_Motor,Kilometros_Recorridos,Ultimo_Cambio_Filtro,Marca_Filtro,Estatus_Inicial";
        // Datos de ejemplo
        const exampleRow = "ECO-999,ABC-123,1G9BS82C0XE178459,SCANIA IRIZAR I5,Scania,Irizar,2023,MEX-QRO,Juan Perez,Diesel,NA,NA,Scania,En Taller/Mantenimiento";
        const csvContent = "data:text/csv;charset=utf-8," + headers + "\n" + exampleRow;
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "plantilla_alta_camiones.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 2. Leer y Previsualizar el CSV de Alta
    inputCsvAlta.addEventListener('change', event => {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const data = parseCSV(text);

            if (data.length > 0) {
                displayPreview(data);
                btnGuardarCsv.disabled = false; 
            } else {
                previewContainer.innerHTML = "<p>No se encontraron datos en el archivo o el formato es incorrecto.</p>";
                btnGuardarCsv.disabled = true;
            }
        };
        reader.readAsText(file);
    });


    function parseCSV(text) {
        const lines = text.trim().split('\n');
        const headers = lines[0].split(',').map(h => h.trim());
        const rows = [];
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(',').map(v => v.trim());
            if (values.length === headers.length) {
                const row = {};
                for (let j = 0; j < headers.length; j++) {
                    row[headers[j]] = values[j];
                }
                rows.push(row);
            }
        }
        return rows;
    }

    // Función para mostrar los datos parseados en una tabla HTML
    function displayPreview(data) {
        const headers = Object.keys(data[0]);
        
        let table = '<table class="preview-table"><thead><tr>';
        headers.forEach(h => table += `<th>${h}</th>`);
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