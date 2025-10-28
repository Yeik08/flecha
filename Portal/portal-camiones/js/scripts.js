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
            const headers = "Condicion_del_vehiculo,ID_Camion,Placas,VIN,Condicion,Marca,Carroceria,Modelo,Tipo_Tecnologia,Estatus_Inicial,ID_Conductor,Kilometros_Recorridos,Ultimo_Mantenimiento,Ultimo_Cambio_Filtro,Marca_Filtro,Serie_Filtro_Aceite";
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
// --- Mostrar / ocultar lista de aprobaciones ---
document.addEventListener("DOMContentLoaded", () => {
    const kpi = document.getElementById("kpi-aprobaciones");
    const lista = document.getElementById("lista-aprobaciones");

    if (kpi && lista) {
        kpi.addEventListener("click", () => {
            lista.classList.toggle("mostrar");
        });
    }
});
document.addEventListener("DOMContentLoaded", () => {

    // Elementos del buscador
    const inputBuscar = document.getElementById("buscar-eco");
    const btnBuscar = document.getElementById("btn-buscar");
    const btnLimpiar = document.getElementById("btn-limpiar");
    const tabla = document.querySelector(".tabla-contenido tbody");

    // Ejemplo de datos (puedes reemplazar esto por tus datos reales)
    const camiones = [
        { id: "ECO-101", placas: "ABC123", estatus: "En taller", ultima: "2025-10-10", proximo: "2025-11-15" },
        { id: "ECO-203", placas: "XYZ456", estatus: "Listo", ultima: "2025-09-22", proximo: "2026-01-01" },
        { id: "ECO-319", placas: "JKL789", estatus: "En espera", ultima: "2025-10-01", proximo: "2025-12-10" },
        { id: "ECO-410", placas: "TUV321", estatus: "Mantenimiento", ultima: "2025-10-20", proximo: "2025-11-20" },
    ];

    // Función para renderizar camiones en la tabla
    function mostrarCamiones(lista) {
        tabla.innerHTML = "";
        lista.forEach(c => {
            const fila = `
                <tr>
                    <td>${c.id}</td>
                    <td>${c.placas}</td>
                    <td>${c.estatus}</td>
                    <td>${c.ultima}</td>
                    <td>${c.proximo}</td>
                    <td><button class="btn-tabla">Ver</button></td>
                </tr>
            `;
            tabla.innerHTML += fila;
        });
    }

    // Mostrar todos al cargar
    mostrarCamiones(camiones);

    // Buscar por número ECO
    btnBuscar.addEventListener("click", () => {
        const valor = inputBuscar.value.trim().toUpperCase();
        if (valor === "") {
            alert("Por favor, introduce un número ECO (Ej: ECO-123)");
            return;
        }

        const resultado = camiones.filter(c => c.id.toUpperCase() === valor);
        if (resultado.length === 0) {
            tabla.innerHTML = `<tr><td colspan="6" style="text-align:center;">No se encontró la unidad ${valor}</td></tr>`;
        } else {
            mostrarCamiones(resultado);
        }
    });

    // Mostrar todos de nuevo
    btnLimpiar.addEventListener("click", () => {
        inputBuscar.value = "";
        mostrarCamiones(camiones);
    });
});
document.addEventListener("DOMContentLoaded", () => {
    const inputBuscar = document.getElementById("buscar-eco");
    const tabla = document.querySelector(".tabla-contenido tbody");

    // 🔹 Ejemplo de datos (puedes reemplazar con tus datos reales)
    const camiones = [
        { id: "ECO-101", placas: "ABC123", estatus: "En taller", ultima: "2025-10-10", proximo: "2025-11-15" },
        { id: "ECO-203", placas: "XYZ456", estatus: "Listo", ultima: "2025-09-22", proximo: "2026-01-01" },
        { id: "ECO-319", placas: "JKL789", estatus: "En espera", ultima: "2025-10-01", proximo: "2025-12-10" },
        { id: "ECO-410", placas: "TUV321", estatus: "Mantenimiento", ultima: "2025-10-20", proximo: "2025-11-20" },
        { id: "ECO-412", placas: "LMN222", estatus: "Listo", ultima: "2025-10-21", proximo: "2025-11-22" },
    ];

    // 🔹 Función para renderizar camiones
    function mostrarCamiones(lista) {
        tabla.innerHTML = "";
        if (lista.length === 0) {
            tabla.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#999;">No se encontraron resultados</td></tr>`;
            return;
        }

        lista.forEach(c => {
            const fila = `
                <tr>
                    <td>${c.id}</td>
                    <td>${c.placas}</td>
                    <td>${c.estatus}</td>
                    <td>${c.ultima}</td>
                    <td>${c.proximo}</td>
                    <td><button class="btn-tabla">Ver</button></td>
                </tr>
            `;
            tabla.innerHTML += fila;
        });
    }

    // 🔹 Mostrar todos los camiones al inicio
    mostrarCamiones(camiones);

    // 🔹 Búsqueda inteligente en tiempo real
    inputBuscar.addEventListener("input", () => {
        const valor = inputBuscar.value.trim().toLowerCase();

        if (valor === "") {
            mostrarCamiones(camiones);
            return;
        }

        const filtrados = camiones.filter(c => 
            c.id.toLowerCase().includes(valor) ||
            c.placas.toLowerCase().includes(valor) ||
            c.estatus.toLowerCase().includes(valor)
        );

        mostrarCamiones(filtrados);
    });
});
// --- Forzar mayúsculas en todos los inputs de texto ---
document.querySelectorAll('input[type="text"]').forEach(input => {
    input.addEventListener("input", () => {
        input.value = input.value.toUpperCase();
    });
});
// --- Generar lista de años dinámicamente ---
const selectAnio = document.getElementById("anio");
if (selectAnio) {
    const anioActual = new Date().getFullYear();
    for (let i = anioActual; i >= 1990; i--) {
        const opcion = document.createElement("option");
        opcion.value = i;
        opcion.textContent = i;
        selectAnio.appendChild(opcion);
    }
}
// --- Búsqueda inteligente de conductor ---
const inputConductor = document.getElementById("id_conductor");
const listaSugerencias = document.getElementById("sugerencias-conductor");

// 🔹 Ejemplo de conductores (puedes reemplazar con datos reales de tu BD)
const conductores = [
    { id: "C-001", nombre: "JUAN PÉREZ" },
    { id: "C-002", nombre: "MARÍA LÓPEZ" },
    { id: "C-003", nombre: "PEDRO RAMÍREZ" },
    { id: "C-004", nombre: "ANA TORRES" },
    { id: "C-005", nombre: "LUIS HERNÁNDEZ" },
    { id: "C-006", nombre: "JORGE MENDOZA" },
    { id: "C-007", nombre: "CARLOS DÍAZ" }
];

// 🔸 Mostrar sugerencias dinámicamente mientras se escribe
inputConductor.addEventListener("input", () => {
    const valor = inputConductor.value.trim().toUpperCase();
    listaSugerencias.innerHTML = "";

    if (valor === "") {
        listaSugerencias.style.display = "none";
        return;
    }

    const filtrados = conductores.filter(c =>
        c.id.includes(valor) || c.nombre.includes(valor)
    );

    if (filtrados.length > 0) {
        listaSugerencias.style.display = "block";
        filtrados.forEach(c => {
            const item = document.createElement("div");
            item.textContent = `${c.id} — ${c.nombre}`;
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

// 🔸 Ocultar la lista si se hace clic fuera
document.addEventListener("click", (e) => {
    if (!listaSugerencias.contains(e.target) && e.target !== inputConductor) {
        listaSugerencias.style.display = "none";
    }
});
