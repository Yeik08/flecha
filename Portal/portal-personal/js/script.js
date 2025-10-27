document.addEventListener('DOMContentLoaded', function() {

    // --- SELECCION DE ELEMENTOS ---
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const modalOverlay = document.getElementById('modal-personal');
    const formPersonal = document.getElementById('form-alta-personal');
    const tablaBody = document.getElementById('tabla-personal-body');

    // --- NUEVO: Elementos para el ID automático ---
    const rolSelect = document.getElementById('rol');
    const idEmpleadoInput = document.getElementById('id_empleado');

    // --- TAREA 2 (Leer): FUNCIÓN PARA CARGAR Y MOSTRAR PERSONAL ---
    async function cargarPersonal() {
        if (!tablaBody) return; 
        
        try {
            const response = await fetch('../php/api_personal.php');
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

    // --- TAREA 1 (Escribir): FUNCIÓN PARA ENVIAR EL FORMULARIO ---
    async function registrarPersonal(evento) {
        evento.preventDefault(); 
        const formData = new FormData(formPersonal);

        try {
            const response = await fetch('../php/api_personal.php', {
                method: 'POST',
                body: formData
            });
            const resultado = await response.json();

            if (resultado.success) {
                alert(resultado.message); 
                cerrarModal();
                formPersonal.reset();
                idEmpleadoInput.value = ''; // Limpiamos el prefijo
                cargarPersonal(); 
            } else {
                throw new Error(resultado.message);
            }
        } catch (error) {
            console.error('Error al registrar:', error);
            alert(`Error: ${error.message}`);
        }
    }
    
    // --- NUEVO: LÓGICA PARA GENERAR PREFIJO DE ID ---
    function generarPrefijoId() {
        if (!rolSelect || !idEmpleadoInput) return;
        
        const rolId = rolSelect.value; // Esto es '1', '2', '3', etc.
        let prefijo = '';

        const prefijos = {
            '1': 'ADM-', // Administrador
            '2': 'MES-', // Mesa de Mantenimiento
            '3': 'MEC-', // Técnico Mecánico
            '4': 'JFT-', // Jefe de Taller
            '5': 'RCT-', // Receptor de Taller
            '6': 'ALM-', // Almacenista
            '7': 'CON-'  // Conductor
        };
        
        prefijo = prefijos[rolId] || ''; // Busca el prefijo, si no, deja vacío

        idEmpleadoInput.value = prefijo; // Pone "MEC-" en el input deshabilitado
    }

    // --- FUNCIONES Y EVENTOS DEL MODAL ---
    function abrirModal() {
        if (modalOverlay) modalOverlay.classList.remove('o-culto');
    }
    function cerrarModal() {
        if (modalOverlay) {
            modalOverlay.classList.add('oculto');
            formPersonal.reset(); // Limpia el form al cerrar
            idEmpleadoInput.value = ''; // Limpia el prefijo
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
    // --- NUEVO: Escucha cambios en el dropdown de Rol ---
    if (rolSelect) {
        rolSelect.addEventListener('change', generarPrefijoId);
    }

    // --- INICIAR TODO ---
    cargarPersonal(); 
    
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.toUpperCase();
        });
    });
});