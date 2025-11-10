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
<<<<<<< HEAD
=======



// APARECER EL FORMULARIO DE AGREGAR PIEZAS    ----------------AGREGAR PARA QUE TAMBEIN CON EL BOTON DE AGREGAR FILTRO
// Esperar a que cargue toda la pagina 
document.addEventListener("DOMContentLoaded", function(){
    const botonAgregar = document.getElementById("btn-agregar-filtro");
    const formularioContainer = document.getElementById("formulario-registro");
    const botonCancelar = document.getElementById("btn-cancelar");

    function mostrarFormulario(){
        formularioContainer.style.display = "block"; //para hacer visible
        formularioContainer.scrollIntoView({behavior:"smooth"}); //scroll
    }

    function ocultarFormulario(){
        formularioContainer.style.display = "none";
    }


    //condicion para esconder el formulario
    if (botonAgregar) {
        botonAgregar.addEventListener("click", mostrarFormulario);
    }

    if (botonCancelar) {
        botonCancelar.addEventListener("click", ocultarFormulario);
    }
})
// Función que muestra u oculta el detalle al hacer clic
function toggleDetalle(id) {
  const detalle = document.getElementById(id);
  if (detalle.style.display === "block") {
    detalle.style.display = "none";
  } else {
    // Oculta todos los demás antes de mostrar el seleccionado (opcional)
    document.querySelectorAll('.detalle').forEach(d => d.style.display = 'none');
    detalle.style.display = "block";
  }
}

// Asignar eventos de clic a las tarjetas
document.getElementById("kpi-filtros-poniente").addEventListener("click", () => toggleDetalle("detalle-filtros-poniente"));
document.getElementById("kpi-filtros-magdalena").addEventListener("click", () => toggleDetalle("detalle-filtros-magdalena"));
document.getElementById("kpi-lubricante-poniente").addEventListener("click", () => toggleDetalle("detalle-lubricante-poniente"));
document.getElementById("kpi-lubricante-magdalena").addEventListener("click", () => toggleDetalle("detalle-lubricante-magdalena"));
// === REFERENCIAS ===
const btnAgregar = document.getElementById("btn-agregar");
const formulario = document.getElementById("formulario-registro");
const form = document.getElementById("form-producto");
const btnCancelar = document.getElementById("btn-cancelar");
const tablaBody = document.querySelector("#tabla-inventario tbody");
const tituloForm = document.getElementById("titulo-form");

// === MOSTRAR FORMULARIO ===
btnAgregar.addEventListener("click", () => {
  formulario.style.display = "block";
  tituloForm.textContent = "Nuevo Producto";
});

// === OCULTAR FORMULARIO ===
btnCancelar.addEventListener("click", () => {
  formulario.style.display = "none";
  form.reset();
});

// === GUARDAR PRODUCTO ===
form.addEventListener("submit", (e) => {
  e.preventDefault();

  const id = document.getElementById("producto-id").value.trim();
  const tipo = document.getElementById("producto-tipo").value;
  const subtipo = document.getElementById("producto-subtipo").value.trim();
  const marca = document.getElementById("producto-marca").value.trim();
  const cantidad = document.getElementById("producto-cantidad").value;
  const ubicacion = document.getElementById("producto-ubicacion").value.trim();

  // Crear una nueva fila
  const fila = document.createElement("tr");
  fila.innerHTML = `
    <td>${id}</td>
    <td>${tipo}</td>
    <td>${subtipo}</td>
    <td>${marca}</td>
    <td>${cantidad}</td>
    <td>${ubicacion}</td>
    <td class="acciones">
      <button class="btn-editar">Editar</button>
      <button class="btn-eliminar">Eliminar</button>
    </td>
  `;

  tablaBody.appendChild(fila);
  form.reset();
  formulario.style.display = "none";
});

// === ELIMINAR PRODUCTO ===
tablaBody.addEventListener("click", (e) => {
  if (e.target.classList.contains("btn-eliminar")) {
    const fila = e.target.closest("tr");
    fila.remove();
  }
});
// --- REFERENCIAS ---
const tipoSelect = document.getElementById("producto-tipo");
const subtipoSelect = document.getElementById("producto-subtipo");
const marcaSelect = document.getElementById("producto-marca");
const ubicacionSelect = document.getElementById("producto-ubicacion");

// --- OPCIONES DISPONIBLES ---
const opciones = {
  Filtro: {
    subtipos: ["Primario", "Centrífugo", "Aceite", "Combustible"],
    marcas: ["PATITO", "Donaldson", "Fleetguard", "WIX"],
    ubicaciones: ["Almacén A", "Almacén B", "Taller Tenango"]
  },
  Lubricante: {
    subtipos: ["15W-40", "10W-30", "5W-30", "20W-50"],
    marcas: ["Castrol", "Mobil", "Shell", "Total"],
    ubicaciones: ["Almacén C", "Bodega Poniente", "Magdalena"]
  }
};

// --- FUNCIÓN PARA LLENAR SELECTS ---
function llenarSelect(select, opciones) {
  select.innerHTML = ""; // limpia opciones previas
  const opcionDefault = document.createElement("option");
  opcionDefault.textContent = "Selecciona una opción";
  opcionDefault.value = "";
  select.appendChild(opcionDefault);

  opciones.forEach(op => {
    const option = document.createElement("option");
    option.value = op;
    option.textContent = op;
    select.appendChild(option);
  });
}

// --- EVENTO: CUANDO CAMBIA EL TIPO ---
tipoSelect.addEventListener("change", () => {
  const tipo = tipoSelect.value;

  if (opciones[tipo]) {
    llenarSelect(subtipoSelect, opciones[tipo].subtipos);
    llenarSelect(marcaSelect, opciones[tipo].marcas);
    llenarSelect(ubicacionSelect, opciones[tipo].ubicaciones);
  } else {
    // Si no hay tipo seleccionado, resetea los selects
    subtipoSelect.innerHTML = `<option value="">Selecciona un tipo primero</option>`;
    marcaSelect.innerHTML = `<option value="">Selecciona un tipo primero</option>`;
    ubicacionSelect.innerHTML = `<option value="">Selecciona un tipo primero</option>`;
  }
});

>>>>>>> 01b4da6130ced3a24738885cf11e61a109a26b33
