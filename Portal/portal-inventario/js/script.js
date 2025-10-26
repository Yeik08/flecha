// Este script maneja el clic en los menús para mostrar/ocultar el submenú.
document.addEventListener('click', function(e) {
// Cierra todos los submenús si se hace clic fuera de un dropdown
if (!e.target.matches('.dropdown-toggle')) {
    document.querySelectorAll('.dropdown.active').forEach(dropdown => {
    dropdown.classList.remove('active');
    });
return;
}
const parent = e.target.closest('.dropdown');
// Alterna la clase 'active' solo en el dropdown clickeado
parent.classList.toggle('active');

// Cierra otros submenús abiertos
document.querySelectorAll('.dropdown.active').forEach(dropdown => {
    if (dropdown !== parent) {
        dropdown.classList.remove('active');
        }
    });
});



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

