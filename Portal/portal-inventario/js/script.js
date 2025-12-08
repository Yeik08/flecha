document.addEventListener("DOMContentLoaded", function(){

    // --- REFERENCIAS DOM ---
    const botonAgregarInventario = document.getElementById("btn-agregar-inventario");
    const modalFondo = document.getElementById("modal-fondo");
    const divCargaMasiva = document.getElementById("modal-carga-masiva"); 
    
    const botonCancelarMasivo = document.getElementById("btn-cancelar-masivo");
    const btnDescargarPlantilla = document.getElementById("btn-descargar-plantilla");
    const tipoInventarioMasivo = document.getElementById("tipo-inventario-masivo");
    const uploadExcelMasivo = document.getElementById("upload-excel-masivo");
    const formCargaMasiva = document.getElementById("form-carga-masiva");
    
    // Referencias para Edición
    const modalEditar = document.getElementById('modal-editar-inventario');
    const formEditar = document.getElementById('form-editar-item');
    const tablaBody = document.getElementById('tabla-inventario-body');

    // --- 1. CARGAR INVENTARIO AL INICIO ---
    async function cargarInventario() {
        try {
            const res = await fetch('php/get_inventario.php');
            const data = await res.json();

            if (!data.success) {
                console.error("Error backend:", data.message);
                return;
            }

            // A. Llenar KPIs
            if(document.getElementById('kpi-filtros')) document.getElementById('kpi-filtros').textContent = data.kpis.filtros_disponibles;
            if(document.getElementById('kpi-litros')) document.getElementById('kpi-litros').textContent = data.kpis.litros_totales; // Ahora esto mostrará Cubetas
            if(document.getElementById('kpi-alertas')) document.getElementById('kpi-alertas').textContent = data.kpis.stock_bajo;
            if(document.getElementById('kpi-instalados')) document.getElementById('kpi-instalados').textContent = data.kpis.filtros_instalados;

            // B. Llenar Tabla
            if (tablaBody) {
                tablaBody.innerHTML = '';
                if (data.tabla.length === 0) {
                    tablaBody.innerHTML = `<tr><td colspan="6" style="text-align:center">Inventario vacío. Sube un archivo.</td></tr>`;
                    return;
                }

                data.tabla.forEach(item => {
                    const tr = document.createElement('tr');
                    let icono = item.tipo_bien === 'Filtro' ? '⚙️' : '🛢️';
                    // Alerta visual simple si hay poco stock (ajustable)
                    let claseExtra = (item.tipo_bien === 'Lubricante' && parseFloat(item.cantidad_formato) < 5) ? 'stock-bajo' : '';

                    tr.className = claseExtra;
                    tr.innerHTML = `
                        <td>${icono} ${item.categoria}</td>
                        <td><strong>${item.descripcion}</strong></td>
                        <td style="font-family:monospace; color:#555;">${item.identificador}</td>
                        <td style="font-weight:bold; color:#316960;">${item.cantidad_formato}</td>
                        <td>${item.ubicacion}</td>
                        <td class="acciones">
                            <button class="btn-editar" data-id="${item.id}" data-tipo="${item.tipo_bien}" title="Editar Ubicación">✏️</button>
                            <button class="btn-eliminar" data-id="${item.id}" data-tipo="${item.tipo_bien}" title="Dar de Baja">🗑️</button>
                        </td>
                    `;
                    tablaBody.appendChild(tr);
                });
            }
        } catch (error) {
            console.error("Error:", error);
        }
    }
    cargarInventario(); 

    // --- 2. GESTIÓN DE ACCIONES (CLICK EN TABLA) ---
    if (tablaBody) {
        tablaBody.addEventListener('click', async function(e) {
            const btnEliminar = e.target.closest('.btn-eliminar');
            const btnEditar = e.target.closest('.btn-editar');

            // ELIMINAR
            if (btnEliminar) {
                const id = btnEliminar.dataset.id;
                const tipo = btnEliminar.dataset.tipo;
                if(!confirm(`¿Dar de BAJA este ${tipo}?`)) return;

                const formData = new FormData();
                formData.append('accion', 'eliminar');
                formData.append('id', id);
                formData.append('tipo_bien', tipo);

                try {
                    const res = await fetch('php/gestion_inventario.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) { alert("✅ " + data.message); cargarInventario(); }
                    else { alert("❌ " + data.message); }
                } catch (error) { alert("Error de conexión"); }
            }

            // EDITAR
            if (btnEditar) {
                const fila = btnEditar.closest('tr');
                const id = btnEditar.dataset.id;
                const tipo = btnEditar.dataset.tipo;
                
                // Llenar modal
                document.getElementById('edit-id').value = id;
                document.getElementById('edit-tipo').value = tipo;
                document.getElementById('edit-descripcion').value = fila.cells[1].textContent;
                document.getElementById('edit-identificador').value = fila.cells[2].textContent;

                // Ocultamos el campo de litros porque ahora son unitarios (cubetas)
                const divLitros = document.getElementById('div-edit-litros');
                if(divLitros) divLitros.style.display = 'none';

                if(modalFondo) modalFondo.style.display = 'block'; 
                if(divCargaMasiva) divCargaMasiva.style.display = 'none'; 
                if(modalEditar) modalEditar.style.display = 'block'; 
            }
        });
    }

    // --- 3. ENVÍO DE EDICIÓN ---
    if (formEditar) {
        formEditar.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(formEditar);
            formData.append('accion', 'editar');

            try {
                const res = await fetch('php/gestion_inventario.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert("✅ " + data.message);
                    modalEditar.style.display = 'none';
                    modalFondo.style.display = 'none';
                    cargarInventario();
                } else { alert("❌ " + data.message); }
            } catch (error) { alert("Error al editar."); }
        });
    }

    // --- 4. LÓGICA DE MODALES (Carga Masiva) ---
    if(botonAgregarInventario) {
        botonAgregarInventario.addEventListener("click", () => {
            if(modalFondo) modalFondo.style.display = "block";
            if(divCargaMasiva) divCargaMasiva.style.display = "block"; 
            if(modalEditar) modalEditar.style.display = "none";
        });
    }

    if(botonCancelarMasivo) {
        botonCancelarMasivo.addEventListener("click", () => { 
            if(modalFondo) modalFondo.style.display = "none"; 
            formCargaMasiva.reset(); 
        });
    }
    
    window.addEventListener('click', (e) => {
        if (e.target == modalFondo) {
            modalFondo.style.display = "none";
            if(modalEditar) modalEditar.style.display = 'none';
        }
    });

    // --- 5. DESCARGA CSV (ACTUALIZADO PARA CUBETAS) ---
    if(btnDescargarPlantilla) {
        btnDescargarPlantilla.addEventListener("click", function(e) {
            e.preventDefault();
            const tipo = tipoInventarioMasivo.value;
            let fileName = "";
            let csvContent = "";

            if(tipo === "filtro") {
                fileName = "plantilla_alta_filtros.csv";
                const headers = ["MARCA", "NUMERO_PARTE", "NUMERO_SERIE_UNICO", "NOMBRE_ALMACEN"];
                csvContent = headers.join(",") + "\n";
                csvContent += "SCANIA,2002705,SCA-SERIE-001,Poniente\n";
                csvContent += "SCANIA,1928869PE,SCA-SERIE-002,Magdalena\n";
            } else {
                // ✅ CAMBIO: Plantilla para Cubetas (Individuales)
                fileName = "plantilla_alta_cubetas.csv";
                const headers = ["NOMBRE_PRODUCTO", "NUMERO_SERIE_CUBETA", "NOMBRE_ALMACEN"];
                csvContent = headers.join(",") + "\n";
                csvContent += "SAE 10W30 MULTIGRADO,CUB-LUB-1001,Poniente\n";
                csvContent += "SAE 15W30,CUB-LUB-1002,Magdalena\n";
            }

            descargarCSV(csvContent, fileName);
        });
    }

    function descargarCSV(content, fileName) {
        const blob = new Blob(["\uFEFF" + content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // --- 6. SUBIDA CSV ---
    if(formCargaMasiva) {
        formCargaMasiva.addEventListener("submit", async function(e){
            e.preventDefault();
            if (uploadExcelMasivo.files.length === 0) { alert("Selecciona un archivo."); return; }
            
            const btn = formCargaMasiva.querySelector('button[type="submit"]');
            btn.disabled = true; btn.textContent = "Procesando...";

            const formData = new FormData();
            formData.append('archivo_csv', uploadExcelMasivo.files[0]);
            formData.append('tipo_carga', tipoInventarioMasivo.value);

            try {
                const res = await fetch('php/procesar_carga_inventario.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert("✅ " + data.message);
                    modalFondo.style.display = "none";
                    formCargaMasiva.reset();
                    cargarInventario();
                } else { alert("⚠️ " + data.message); }
            } catch (error) { alert("Error de conexión"); }
            finally { btn.disabled = false; btn.textContent = "Guardar y Continuar"; }
        });
    }

    // --- 7. FILTRO DE ALMACÉN EN TIEMPO REAL ---
    const selectFiltroUbicacion = document.getElementById('filtro-ubicacion-ui');
    
    if (selectFiltroUbicacion) {
        selectFiltroUbicacion.addEventListener('change', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tabla-inventario-body tr');

            filas.forEach(fila => {
                const ubicacionTexto = fila.cells[4].textContent.toLowerCase();
                
                if (filtro === 'todos') {
                    fila.style.display = '';
                } else {
                    if (ubicacionTexto.includes(filtro)) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                }
            });
        });
    }
});