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

//-------------------------------------------------------------------------------------------

