document.addEventListener('DOMContentLoaded', function() {

    // --- SELECCION DE ELEMENTOS ---
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const modalOverlay = document.getElementById('modal-personal');
    const formPersonal = document.getElementById('form-alta-personal');
    const tablaBody = document.getElementById('tabla-personal-body');

    // --- Elementos para el ID automático ---
    const rolSelect = document.getElementById('rol');
    const idEmpleadoInput = document.getElementById('id_empleado');

    // --- RUTA CORRECTA A LA API ---
    // Sube 3 niveles (js/ -> portal-personal/ -> Portal/ -> flecha-1/) y entra a php/
    const API_URL = '../../../php/api_personal.php'; 

    // --- TAREA 2 (Leer): FUNCIÓN PARA CARGAR Y MOSTRAR PERSONAL ---
    async function cargarPersonal() {
        if (!tablaBody) return; 
        
        try {
            // CORRECCIÓN 1: Usamos la nueva URL
            const response = await fetch(API_URL); 
            const resultado = await response.json();

            if (!resultado.success) {
                throw new Error(resultado.message);
            }

            tablaBody.innerHTML = ''; 
            
            resultado.data.forEach(emp => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${emp.id_interno}</td> 
                    <td>${emp.nombre} ${emp.apellido_p}</td>
                    <td>${emp.nombre_rol}</td>
                    <td>Activo</td>
                    <td>${emp.fecha_ingreso}</td>
                    <td class="acciones">
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


    // --- TAREA 3 (Eliminar/Baja): FUNCIÓN PARA ELIMINAR PERSONAL ---
    async function eliminarPersonal(id_empleado) {
        if (!confirm(`¿Estás seguro de que quieres eliminar al empleado con ID ${id_empleado}?`)) {
            return; 
        }

        try {
            // CORRECCIÓN 2: Usamos la nueva URL
            const response = await fetch(API_URL, { 
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id_empleado}` 
            });

            const resultado = await response.json();

            if (resultado.success) {
                alert(resultado.message); 
                cargarPersonal(); 
            } else {
                throw new Error(resultado.message);
            }

        } catch (error) {
            console.error('Error al eliminar:', error);
            alert(`Error: ${error.message}`);
        }
    }

    // --- TAREA 1 (Escribir): FUNCIÓN PARA ENVIAR EL FORMULARIO ---
    async function registrarPersonal(evento) {
        evento.preventDefault(); 
        const formData = new FormData(formPersonal);

        try {
            // CORRECCIÓN 3: Usamos la nueva URL
            const response = await fetch(API_URL, { 
                method: 'POST',
                body: formData
            });
            const resultado = await response.json();

            if (resultado.success) {
                alert(resultado.message); 
                cerrarModal();
                formPersonal.reset();
                idEmpleadoInput.value = ''; 
                cargarPersonal(); 
            } else {
                throw new Error(resultado.message);
            }
        } catch (error) {
            console.error('Error al registrar:', error);
            alert(`Error: ${error.message}`);
        }
    }
    
    // --- LÓGICA PARA GENERAR PREFIJO DE ID ---
    function generarPrefijoId() {
        if (!rolSelect || !idEmpleadoInput) return;
        
        const rolId = rolSelect.value; 
        let prefijo = '';

        const prefijos = {
            '1': 'ADM-', '2': 'MES-', '3': 'MEC-', '4': 'JFT-', 
            '5': 'RCT-', '6': 'ALM-', '7': 'CON-'
        };
        
        prefijo = prefijos[rolId] || ''; 
        idEmpleadoInput.value = prefijo; 
    }

    // --- FUNCIONES Y EVENTOS DEL MODAL ---
    function abrirModal() {
        if (modalOverlay) modalOverlay.classList.remove('oculto');
    }
    function cerrarModal() {
        if (modalOverlay) {
            modalOverlay.classList.add('oculto');
            formPersonal.reset(); 
            idEmpleadoInput.value = ''; 
        }
    }

    if (btnAbrirModal) btnAbrirModal.addEventListener('click', abrirModal);
    if (btnCerrarModal) btnCerrarModal.addEventListener('click', cerrarModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) cerrarModal();
        });
    }

    // --- MANEJADORES DE EVENTOS ---
    if (formPersonal) {
        formPersonal.addEventListener('submit', registrarPersonal);
    }
    if (rolSelect) {
        rolSelect.addEventListener('change', generarPrefijoId);
    }

    if (tablaBody) {
        tablaBody.addEventListener('click', function(evento) {
            if (evento.target.classList.contains('btn-eliminar')) {
                const id = evento.target.dataset.id; 
                eliminarPersonal(id);
            }
            if (evento.target.classList.contains('btn-editar')) {
                alert('Función "Editar" aún no implementada.');
            }
        });
    }
    
    // --- INICIAR TODO ---
    cargarPersonal(); 
    
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.toUpperCase();
        });
    });

}); // <-- Esta es la llave de cierre para 'DOMContentLoaded'
// LA LLAVE '}' EXTRA QUE ESTABA AQUÍ FUE ELIMINADA