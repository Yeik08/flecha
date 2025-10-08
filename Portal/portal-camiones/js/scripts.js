document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const isDropdownToggle = e.target.matches('.dropdown-toggle');
        if (!isDropdownToggle && e.target.closest('.dropdown') === null) {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
        
        if (isDropdownToggle) {
            const parent = e.target.closest('.dropdown');
            parent.classList.toggle('active');
            
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                if (dropdown !== parent) {
                    dropdown.classList.remove('active');
                }
            });
        }
    });

    // --- LÓGICA DEL MODAL DE ALTA DE CAMIÓN ---
    const modal = document.getElementById('modo-formulario');
    const btnAbrirModal = document.getElementById('btn-abrir-modo');
    // Seleccionamos todos los botones que deben cerrar el modal
    const btnsCerrarModal = document.querySelectorAll('.form-cerrar, .btn-cerrar-modo');

    // Comprobamos que los elementos existan antes de añadir eventos
    if (modal && btnAbrirModal && btnsCerrarModal.length > 0) {
        
        const abrirModal = () => modal.classList.remove('oculto');
        const cerrarModal = () => modal.classList.add('oculto');

        btnAbrirModal.addEventListener('click', abrirModal);
        btnsCerrarModal.forEach(btn => btn.addEventListener('click', cerrarModal));
        
        // Cerrar al hacer clic en el fondo oscuro
        modal.addEventListener('click', e => {
            if (e.target === modal) {
                cerrarModal();
            }
        });
    }

    // --- LÓGICA DE PESTAÑAS (TABS) ---
    const tabLinks = document.querySelectorAll('.carga-link');
    const tabContents = document.querySelectorAll('.tab-content');

    if (tabLinks.length > 0 && tabContents.length > 0) {
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const tabId = link.getAttribute('data-tab');

                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => item.classList.remove('active'));

                link.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    }

    // --- LÓGICA PARA CARGA MASIVA (CSV) ---
    const btnDescargarPlantilla = document.getElementById('btn-descargar-plantilla');
    const inputCsvAlta = document.getElementById('input-csv-alta');
    const previewContainer = document.getElementById('preview-container');
    const btnGuardarCsv = document.getElementById('btn-guardar-csv');

    if (btnDescargarPlantilla) {
        btnDescargarPlantilla.addEventListener('click', () => {
            const headers = "ID_Camion,Placas,VIN,Tipo_Tecnologia,Marca,Modelo,Anio,Ruta_Asignada,Chofer_Asignado,Tipo_Motor,Kilometros_Recorridos,Ultimo_Cambio_Filtro,Marca_Filtro,Estatus_Inicial";
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
    }

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

    function parseCSV(text) {
        const lines = text.trim().replace(/\r/g, "").split('\n');
        if (lines.length < 2) return []; // Necesita al menos encabezado y una fila de datos
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