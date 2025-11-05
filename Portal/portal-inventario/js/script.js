document.addEventListener("DOMContentLoaded", function(){
    const botonAgregarInventario = document.getElementById("btn-agregar-inventario");
    const formularioSeleccion = document.getElementById("formulario-seleccion");
    const formularioRegistro = document.getElementById("formulario-registro");
    const botonCancelar = document.getElementById("btn-cancelar");
    const botonCancelarFormulario = document.getElementById("btn-cancelar-formulario");
    const botonConfirmarTipo = document.getElementById("btn-confirmar-tipo");
    const tipoInventarioSelect = document.getElementById("tipo-inventario");
    const campoNumeroSerie = document.getElementById("campo-numero-serie");
    const campoNumeroLote = document.getElementById("campo-numero-lote");
    const cantidadInput = document.getElementById("cantidad");
    const btnDescargarExcel = document.getElementById("btn-descargar-excel");
    const uploadExcelInput = document.getElementById("upload-excel");

    // Mostrar formulario de selección
    botonAgregarInventario.addEventListener("click", function() {
        formularioSeleccion.style.display = "block";
    });

    // Ocultar formulario de selección y mostrar formulario de registro
    botonConfirmarTipo.addEventListener("click", function() {
        formularioSeleccion.style.display = "none";
        formularioRegistro.style.display = "block";

        const tipoInventario = tipoInventarioSelect.value;

        // Ajustar campos según tipo
        if (tipoInventario === "filtro") {
            document.getElementById("titulo-formulario").textContent = "Registrar Filtro";
            campoNumeroSerie.style.display = "block";
            campoNumeroLote.style.display = "none";
        } else {
            document.getElementById("titulo-formulario").textContent = "Registrar Lubricante";
            campoNumeroSerie.style.display = "none";
            campoNumeroLote.style.display = "block";
        }
    });

    // Ocultar formulario de registro
    botonCancelar.addEventListener("click", function() {
        formularioSeleccion.style.display = "none";
    });

    botonCancelarFormulario.addEventListener("click", function() {
        formularioRegistro.style.display = "none";
    });

    // Descargar archivo Excel según tipo de inventario
    btnDescargarExcel.addEventListener("click", function() {
        const tipoInventario = tipoInventarioSelect.value;
        let data = [];
        
        // Obtener los datos para la descarga según tipo
        if (tipoInventario === "filtro") {
            // Datos para Filtros
            data = [
                ['ID', 'Número de Serie', 'Ubicación'],
                ['FR-ACE-001', 'FR-ACE-100', 'Almacén A'],
                ['FR-AIR-003', 'FR-AIR-200', 'Almacén B'],
                ['FR-COMB-002', 'FR-COMB-300', 'Almacén C']
            ];
        } else {
            // Datos para Lubricantes
            data = [
                ['ID', 'Número de Lote', 'Ubicación'],
                ['LUB-001', 'LUB-001-A', 'Almacén A'],
                ['LUB-002', 'LUB-002-B', 'Almacén B'],
                ['LUB-003', 'LUB-003-C', 'Almacén C']
            ];
        }

        // Crear una hoja de trabajo en formato Excel
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Inventario');

        // Generar y descargar el archivo Excel
        XLSX.writeFile(wb, `${tipoInventario}_Inventario.xlsx`);
    });

    // Subir archivo Excel y leer su contenido
    uploadExcelInput.addEventListener("change", function(event) {
        const file = event.target.files[0];
        if (file && file.name.endsWith('.xlsx')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const data = e.target.result;
                const workbook = XLSX.read(data, { type: 'binary' });

                // Procesar el archivo Excel (por ejemplo, leyendo la primera hoja)
                const sheetName = workbook.SheetNames[0];
                const sheet = workbook.Sheets[sheetName];

                // Leer los datos de la hoja
                const jsonData = XLSX.utils.sheet_to_json(sheet);
                console.log(jsonData); // Mostrar datos en consola o procesarlos como desees

                // Aquí podrías agregar una función para agregar los datos a la tabla o procesarlos
                alert('Archivo subido y procesado.');
            };
            reader.readAsBinaryString(file);
        } else {
            alert('Por favor, sube un archivo Excel válido (.xlsx).');
        }
    });

    // Mostrar el botón de descargar Excel siempre (sin importar la cantidad de piezas)
    cantidadInput.addEventListener("input", function() {
        btnDescargarExcel.style.display = "block"; // Siempre mostrar el botón de descarga
    });
});
