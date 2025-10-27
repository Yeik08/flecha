// Espera a que todo el HTML esté cargado
document.addEventListener('DOMContentLoaded', function() {

    // --- SELECCION DE ELEMENTOS ---
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const modalOverlay = document.getElementById('modal-personal');
    const formPersonal = document.getElementById('form-alta-personal');
    // Elemento <tbody> de la tabla donde irán los datos
    const tablaBody = document.getElementById('tabla-personal-body');

    // --- TAREA 2 (Leer): FUNCIÓN PARA CARGAR Y MOSTRAR PERSONAL ---
    async function cargarPersonal() {
        if (!tablaBody) return; // Si no hay tabla, no hagas nada
        
        try {
            // Usamos fetch para "llamar" a nuestro API (método GET por defecto)
            // La ruta es '../php/' porque 'script.js' está en 'Portal/js/'
            const response = await fetch('../php/api_personal.php');
            const resultado = await response.json();

            if (!resultado.success) {
                throw new Error(resultado.message);
            }

            // Limpiamos la tabla antes de llenarla
            tablaBody.innerHTML = ''; 
            
            // Recorremos los datos (empleados) y creamos el HTML
            resultado.data.forEach(emp => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${emp.id_interno}</td> 
                    
                    <td>${emp.nombre} ${emp.apellido_p}</td>
                    <td>${emp.nombre_rol}</td>
                    <td>Activo</td> <td>${emp.fecha_ingreso}</td>
                    <td class"acciones">
                        <button class="btn-editar" data-id="${emp.id_empleado}">Editar</button>
                        <button class="btn-eliminar" data-id="${emp.id_empleado}">Eliminar</button>
                    </td>
                `;
                tablaBody.appendChild(tr);
            });

        } catch (error) {
            console.error('Error al cargar personal:', error);
            tablaBody.innerHTML = `<tr><td colspan="6">Error al cargar datos: ${error.message}</td></tr>`;
        }
    }

    // --- TAREA 1 (Escribir): FUNCIÓN PARA ENVIAR EL FORMULARIO ---
    async function registrarPersonal(evento) {
        evento.preventDefault(); // Prevenimos que la página se recargue
        
        // Usamos FormData para capturar TODOS los campos del formulario (con sus 'name')
        const formData = new FormData(formPersonal);

        try {
            // Llamamos al API usando 'POST' y le pasamos los datos
            const response = await fetch('../php/api_personal.php', {
                method: 'POST',
                body: formData
            });

            const resultado = await response.json();

            if (resultado.success) {
                alert(resultado.message); // "Empleado registrado con éxito"
                cerrarModal();
                formPersonal.reset(); // Limpiamos el formulario
                cargarPersonal(); // ¡Recargamos la tabla con el nuevo empleado!
            } else {
                throw new Error(resultado.message);
            }

        } catch (error) {
            console.error('Error al registrar:', error);
            alert(`Error: ${error.message}`);
        }
    }

    // --- FUNCIONES Y EVENTOS DEL MODAL (Tu código original) ---
    function abrirModal() {
        if (modalOverlay) modalOverlay.classList.remove('oculto');
    }
    function cerrarModal() {
        if (modalOverlay) modalOverlay.classList.add('oculto');
    }

    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModal);
    }
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
    }
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) cerrarModal();
        });
    }

    // --- MANEJADOR DEL FORMULARIO ---
    if (formPersonal) {
        formPersonal.addEventListener('submit', registrarPersonal);
    }

    // --- INICIAR TODO ---
    cargarPersonal(); // <-- ¡Llamamos a la función para llenar la tabla al cargar la página!
    
    // (Tu código de mayúsculas)
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.toUpperCase();
        });
    });
});