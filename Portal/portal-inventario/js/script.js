document.addEventListener("DOMContentLoaded", function(){

    // --- REFERENCIAS DOM ---
    const botonAgregarInventario = document.getElementById("btn-agregar-inventario");
    const modalFondo = document.getElementById("modal-fondo");
    const botonCancelarMasivo = document.getElementById("btn-cancelar-masivo");
    const btnDescargarPlantilla = document.getElementById("btn-descargar-plantilla");
    const tipoInventarioMasivo = document.getElementById("tipo-inventario-masivo");
    const uploadExcelMasivo = document.getElementById("upload-excel-masivo");
    const formCargaMasiva = document.getElementById("form-carga-masiva");

    // --- 1. LÓGICA DE MODALES ---
    if(botonAgregarInventario) {
        botonAgregarInventario.addEventListener("click", function() {
            modalFondo.style.display = "block";
        });
    }

    if(botonCancelarMasivo) {
        botonCancelarMasivo.addEventListener("click", function() {
            modalFondo.style.display = "none";
            formCargaMasiva.reset();
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target == modalFondo) {
            modalFondo.style.display = "none";
        }
    });

    // --- 2. LÓGICA DE DESCARGA DE PLANTILLA CSV ---
    if(btnDescargarPlantilla) {
        btnDescargarPlantilla.addEventListener("click", function(e) {
            e.preventDefault();
            const tipo = tipoInventarioMasivo.value;
            let csvContent = "";
            let fileName = "";

            if(tipo === "filtro") {
                // PLANTILLA PARA FILTROS (Piezas únicas)
                // Conecta con tb_cat_filtros (Marca, Parte, Tipo) y tb_inventario_filtros (Serie)
                fileName = "plantilla_alta_filtros.csv";
                const headers = ["MARCA", "NUMERO_PARTE", "NUMERO_SERIE_UNICO", "NOMBRE_ALMACEN"];
                csvContent = headers.join(",") + "\n";
                // Ejemplos para guiar al usuario
                csvContent += "SCANIA,2002705,SCA-SERIE-001,Almacén Poniente\n";
                csvContent += "SCANIA,1928869PE,DON-2025-X99,Almacén Magdalena\n";
            
            } else {
                // PLANTILLA PARA LUBRICANTES (Litros)
                // Conecta con tb_cat_lubricantes (Nombre Producto)
                fileName = "plantilla_alta_lubricantes.csv";
                const headers = ["NOMBRE_PRODUCTO_LUBRICANTE", "NOMBRE_ALMACEN", "LITROS_A_AGREGAR"];
                csvContent = headers.join(",") + "\n";
                csvContent += "SAE 10W30 MULTIGRADO,Almacén Poniente,200\n";
                csvContent += "SAE 15W30,Almacén Magdalena,50.5\n";
            }

            descargarCSV(csvContent, fileName);
        });
    }

    // Función auxiliar para descargar
    function descargarCSV(csvContent, fileName) {
        const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
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

    // --- 3. PREPARAR INPUT PARA CSV ---
    if(uploadExcelMasivo) {
        uploadExcelMasivo.setAttribute("accept", ".csv");
    }

if(formCargaMasiva) {
        formCargaMasiva.addEventListener("submit", async function(e){
            e.preventDefault();
            
            if (uploadExcelMasivo.files.length === 0) {
                alert("Por favor selecciona un archivo CSV.");
                return;
            }

            const btnSubmit = formCargaMasiva.querySelector('button[type="submit"]');
            const textoOriginal = btnSubmit.textContent;
            btnSubmit.disabled = true;
            btnSubmit.textContent = "Procesando...";

            const archivo = uploadExcelMasivo.files[0];
            const tipo = tipoInventarioMasivo.value;

            // Preparamos los datos para enviar
            const formData = new FormData();
            formData.append('archivo_csv', archivo);
            formData.append('tipo_carga', tipo); // 'filtro' o 'lubricante'

            try {
                // Hacemos la petición al PHP que acabamos de crear
                const response = await fetch('php/procesar_carga_inventario.php', {
                    method: 'POST',
                    body: formData
                });

                // Verificamos si la respuesta es JSON válido
                const data = await response.json();

                if (data.success) {
                    alert("✅ " + data.message);
                    modalFondo.style.display = "none";
                    formCargaMasiva.reset();
                    // Opcional: Recargar la tabla si tuviéramos una función para eso
                    location.reload(); 
                } else {
                    alert("⚠️ Hubo problemas:\n" + data.message);
                }

            } catch (error) {
                console.error("Error:", error);
                alert("Error de conexión con el servidor.");
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = textoOriginal;
            }
        });
    }
});