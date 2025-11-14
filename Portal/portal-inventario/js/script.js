document.addEventListener("DOMContentLoaded", function(){

    // --- Modal de carga masiva ---
    const botonAgregarInventario = document.getElementById("btn-agregar-inventario");
    const modalFondo = document.getElementById("modal-fondo");
    const botonCancelarMasivo = document.getElementById("btn-cancelar-masivo");
    const btnDescargarPlantilla = document.getElementById("btn-descargar-plantilla");
    const tipoInventarioMasivo = document.getElementById("tipo-inventario-masivo");
    const uploadExcelMasivo = document.getElementById("upload-excel-masivo");
    const formCargaMasiva = document.getElementById("form-carga-masiva");

    // Abrir modal
    botonAgregarInventario.addEventListener("click", function() {
        modalFondo.style.display = "block";
    });

    // Cerrar modal
    botonCancelarMasivo.addEventListener("click", function() {
        modalFondo.style.display = "none";
        formCargaMasiva.reset();
    });

    // Descargar plantilla según tipo
    btnDescargarPlantilla.addEventListener("click", function() {
        const tipo = tipoInventarioMasivo.value;
        let data = [];

        if(tipo === "filtro") {
            data = [
                ['ID', 'Número de Serie', 'Ubicación'],
                ['FR-ACE-001', 'FR-ACE-100', 'Almacén A']
            ];
        } else {
            data = [
                ['ID', 'Número de Lote', 'Ubicación'],
                ['LUB-001', 'LUB-001-A', 'Almacén A']
            ];
        }

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Inventario');
        XLSX.writeFile(wb, `${tipo}_Inventario.xlsx`);
    });

    // Subir archivo Excel
    uploadExcelMasivo.addEventListener("change", function(event){
        const file = event.target.files[0];
        if(file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
            const reader = new FileReader();
            reader.onload = function(e){
                const data = e.target.result;
                const workbook = XLSX.read(data, { type: 'binary' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(sheet);
                console.log(jsonData);
                alert('Archivo subido y procesado.');
            };
            reader.readAsBinaryString(file);
        } else {
            alert('Por favor, sube un archivo Excel válido (.xlsx).');
        }
    });

    // Guardar y cerrar modal
    formCargaMasiva.addEventListener("submit", function(e){
        e.preventDefault();
        alert('Inventario cargado correctamente.');
        modalFondo.style.display = "none";
        formCargaMasiva.reset();
    });

});
