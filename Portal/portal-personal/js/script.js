// Espera a que todo el contenido del HTML esté cargado antes de ejecutar el script.
document.addEventListener('DOMContentLoaded', function() {

    // --- LÓGICA PARA EL MODAL DE PERSONAL ---

    // Seleccionamos los elementos del DOM que necesitamos manipular.
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const modalOverlay = document.getElementById('modal-personal');
    const formPersonal = document.getElementById('form-alta-personal');
    
    // Nuevos elementos para la lógica del ID automático.
    const rolSelect = document.getElementById('rol');
    const idEmpleadoInput = document.getElementById('id_empleado');

    // --- FUNCIONES DEL MODAL ---
    /**
     * Muestra el modal eliminando la clase 'oculto'.
     */
    function abrirModal() {
        if (modalOverlay) {
            modalOverlay.classList.remove('oculto');
        }
    }

    /**
     * Oculta el modal añadiendo la clase 'oculto'.
     */
    function cerrarModal() {
        if (modalOverlay) {
            modalOverlay.classList.add('oculto');
        }
    }
    
    // --- LÓGICA PARA GENERAR ID AUTOMÁTICAMENTE ---
    
    /**
     * Genera un ID de empleado basado en el rol seleccionado.
     * Simula una llamada al backend para obtener el siguiente número consecutivo.
     */
    async function generarIdEmpleado() {
        const rol = rolSelect.value;
        if (!rol) {
            idEmpleadoInput.value = ""; // Limpia el campo si no hay rol seleccionado
            return;
        }

        // 1. Definir el prefijo basado en el rol seleccionado.
        const prefijos = {
            'Mecanico': 'MEC',
            'Almacenista': 'ALM',
            'Conductor': 'CON'
        };
        const prefijo = prefijos[rol];

        // 2. Simulación de la llamada al Backend.
        // En un futuro, aquí harías una llamada 'fetch' a tu API
        // para obtener el último número registrado para ese rol.
        // Ejemplo: const response = await fetch(`/api/personal/siguiente-id?rol=${rol}`);
        //          const data = await response.json();
        //          const siguienteNumero = data.siguienteId;
        
        // Por ahora, simulamos la respuesta del backend.
        const ultimoNumeroRegistrado = 1; // Imaginemos que la BD nos devuelve '1'.
        const siguienteNumero = ultimoNumeroRegistrado + 1;
        
        // 3. Formatear el número para que tenga 3 dígitos (ej. 002, 015, 123).
        // `padStart` rellena el string al inicio con '0' hasta que tenga 3 caracteres.
        const numeroFormateado = String(siguienteNumero).padStart(3, '0');

        // 4. Combinar y mostrar el ID en el input.
        idEmpleadoInput.value = `${prefijo}-${numeroFormateado}`;
    }


    // --- EVENT LISTENERS (ESCUCHADORES DE EVENTOS) ---

    // 1. Cuando el usuario hace clic en el botón "+ Agregar Personal".
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModal);
    }

    // 2. Cuando el usuario hace clic en la 'X' para cerrar.
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
    }
    
    // 3. Cerrar el modal si el usuario hace clic fuera del formulario (en el fondo oscuro).
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(evento) {
            // Se activa solo si el clic es en el overlay y no en sus elementos hijos.
            if (evento.target === modalOverlay) {
                cerrarModal();
            }
        });
    }

    // 4. Cuando el usuario cambia el rol, se genera el ID.
    if (rolSelect) {
        rolSelect.addEventListener('change', generarIdEmpleado);
    }

    // 5. Manejar el envío del formulario.
    if (formPersonal) {
        formPersonal.addEventListener('submit', function(evento) {
            // Prevenimos que la página se recargue, que es el comportamiento por defecto.
            evento.preventDefault(); 
            
            // Recolectamos los datos del formulario (con los nuevos campos de nombre).
            const datos = {
                idEmpleado: idEmpleadoInput.value,
                nombre: document.getElementById('nombre').value,
                apellidoPaterno: document.getElementById('apellido_paterno').value,
                apellidoMaterno: document.getElementById('apellido_materno').value,
                rol: rolSelect.value,
                fechaIngreso: document.getElementById('fecha_ingreso').value,
            };

            // Verificamos en consola que los datos se están recolectando correctamente.
            console.log('Datos del formulario a enviar:', datos);
            
            alert(`Empleado "${datos.nombre} ${datos.apellidoPaterno}" registrado con el ID: ${datos.idEmpleado} (simulación).`);
            
            // Limpiamos el formulario y cerramos el modal para una nueva entrada.
            formPersonal.reset();
            idEmpleadoInput.value = ''; // Limpiamos el ID también
            cerrarModal();
        });
    }
});