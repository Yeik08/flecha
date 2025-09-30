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

