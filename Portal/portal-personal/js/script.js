document.addEventListener('DOMContentLoaded', function() {

    // --- SELECCION DE ELEMENTOS ---
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const modalOverlay = document.getElementById('modal-personal');
    const formPersonal = document.getElementById('form-alta-personal');
    const tablaBody = document.getElementById('tabla-personal-body');
    const tablaHead = tablaBody ? tablaBody.previousElementSibling : null; // Selecciona el thead
    const modalTitulo = modalOverlay ? modalOverlay.querySelector('h2') : null;
    const modalSubmitButton = modalOverlay ? modalOverlay.querySelector('button[type="submit"]') : null;

    // --- Elementos para el ID automático ---
    const rolSelect = document.getElementById('rol');
    const idEmpleadoInput = document.getElementById('id_empleado');

    // --- RUTA ABSOLUTA A LA API ---
    const API_URL = '/flecha/php/api_personal.php';

    // --- Variables Globales ---
    let empleadosData = []; // Para guardar los datos y reordenar/filtrar
    let currentSortColumn = null;
    let currentSortDirection = 'asc';
    let idEmpleadoEditar = null; // Para saber si estamos editando

    // --- TAREA 2 (Leer): FUNCIÓN PARA CARGAR PERSONAL ---
    async function cargarPersonal() {
        if (!tablaBody) return;

        try {
            // Llama a la API para obtener todos los empleados (GET por defecto)
            const response = await fetch(API_URL);
             // Verifica si la respuesta es JSON antes de procesar
             const contentType = response.headers.get("content-type");
             if (!contentType || !contentType.includes("application/json")) {
                 const textResponse = await response.text();
                 throw new Error(`Respuesta inesperada del servidor al cargar: ${textResponse.substring(0, 200)}...`);
             }
            const resultado = await response.json();

            if (!resultado.success) {
                throw new Error(resultado.message);
            }

            empleadosData = resultado.data; // Guarda los datos
            renderTabla(); // Llama a la función para dibujar la tabla ordenada/filtrada

        } catch (error) {
            console.error('Error al cargar personal:', error);
            if(tablaBody) tablaBody.innerHTML = `<tr><td colspan="6">Error al cargar datos: ${error.message}</td></tr>`;
        }
    }

    // --- FUNCIÓN PARA DIBUJAR LA TABLA (renderTabla) ---
    function renderTabla() {
        if (!tablaBody) return;
        tablaBody.innerHTML = ''; // Limpia tabla

        // Ordena los datos si hay una columna seleccionada
        if (currentSortColumn) {
            empleadosData.sort((a, b) => {
                let valA = a[currentSortColumn];
                let valB = b[currentSortColumn];

                // Manejo para fechas
                if (currentSortColumn === 'fecha_ingreso') {
                    valA = new Date(valA || 0); // Maneja nulos
                    valB = new Date(valB || 0);
                } else if (typeof valA === 'string') {
                    // Ignorar mayúsculas/minúsculas y nulos para strings
                    valA = (valA || '').toLowerCase();
                    valB = (valB || '').toLowerCase();
                } else {
                    // Para números o nulos
                    valA = valA || 0; // Convierte nulos a 0 para comparar
                    valB = valB || 0;
                }

                if (valA < valB) return currentSortDirection === 'asc' ? -1 : 1;
                if (valA > valB) return currentSortDirection === 'asc' ? 1 : -1;
                return 0; // Son iguales
            });
        }

        // Actualiza iconos de cabecera
        if (tablaHead) {
            tablaHead.querySelectorAll('th.sortable').forEach(th => {
                 th.classList.remove('asc', 'desc');
                 if (th.dataset.column === currentSortColumn) {
                     th.classList.add(currentSortDirection);
                 }
            });
        }

        // Dibuja las filas
        if (empleadosData.length > 0) {
            empleadosData.forEach(emp => {
                const tr = document.createElement('tr');
                if (emp.estatus === 'inactivo') {
                    tr.classList.add('inactivo'); // Clase para estilo opcional
                }
                tr.innerHTML = `
                    <td>${emp.id_interno || 'N/A'}</td>
                    <td>${emp.nombre} ${emp.apellido_p || ''} ${emp.apellido_m || ''}</td>
                    <td>${emp.nombre_rol}</td>
                    <td>${emp.estatus ? emp.estatus.charAt(0).toUpperCase() + emp.estatus.slice(1) : 'N/A'}</td>
                    <td>${emp.fecha_ingreso}</td>
                    <td class="acciones">
                        <button class="btn-editar" data-id="${emp.id_empleado}">Editar</button>
                        ${emp.estatus === 'activo' ?
                            `<button class="btn-eliminar" data-id="${emp.id_empleado}">Desactivar</button>` :
                            (emp.estatus === 'inactivo' ? `<button class="btn-reactivar" data-id="${emp.id_empleado}">Reactivar</button>` : '')
                        }
                    </td>
                `;
                tablaBody.appendChild(tr);
            });
        } else {
             tablaBody.innerHTML = `<tr><td colspan="6">No se encontraron empleados.</td></tr>`;
        }
    }

    // --- TAREA 3 (Desactivar): FUNCIÓN ---
    async function eliminarPersonal(id_empleado) {
        if (!confirm(`¿Estás seguro de que quieres DESACTIVAR al empleado con ID numérico ${id_empleado}?`)) return;
        try {
            const response = await fetch(API_URL, { method: 'DELETE', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${id_empleado}` });
            // Asegurarse que la respuesta sea JSON antes de procesar
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                 const textResponse = await response.text();
                 throw new Error(`Respuesta inesperada del servidor al desactivar: ${textResponse.substring(0, 200)}...`);
             }
            const resultado = await response.json();
            if (resultado.success) { alert(resultado.message); cargarPersonal(); }
            else { throw new Error(resultado.message); }
        } catch (error) { console.error('Error al desactivar:', error); alert(`Error: ${error.message}`); }
    }

    // --- TAREA 4 (Reactivar): FUNCIÓN ---
    async function reactivarPersonal(id_empleado) {
        if (!confirm(`¿Estás seguro de que quieres REACTIVAR al empleado con ID numérico ${id_empleado}?`)) return;
        try {
            const response = await fetch(API_URL, { method: 'PUT', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${id_empleado}&accion=reactivar` });
             const contentType = response.headers.get("content-type");
             if (!contentType || !contentType.includes("application/json")) {
                 const textResponse = await response.text();
                 throw new Error(`Respuesta inesperada del servidor al reactivar: ${textResponse.substring(0, 200)}...`);
             }
            const resultado = await response.json();
            if (resultado.success) { alert(resultado.message); cargarPersonal(); }
            else { throw new Error(resultado.message); }
        } catch (error) { console.error('Error al reactivar:', error); alert(`Error: ${error.message}`); }
    }

    // --- FUNCIÓN PARA LLENAR EL FORMULARIO AL EDITAR ---
    function llenarFormularioParaEditar(empleado) {
        // console.log("Datos recibidos para llenar:", empleado); // Descomenta para depurar
        if (!formPersonal || !empleado) {
             console.error("Formulario o datos de empleado no encontrados para llenar.");
             return;
        }
        try {
            formPersonal.querySelector('#nombre').value = empleado.nombre || '';
            formPersonal.querySelector('#apellido_paterno').value = empleado.apellido_p || '';
            formPersonal.querySelector('#apellido_materno').value = empleado.apellido_m || '';
            // Necesitas el role_id para el select, asegúrate que la API lo devuelva
            formPersonal.querySelector('#rol').value = empleado.role_id || '';
            formPersonal.querySelector('#fecha_ingreso').value = empleado.fecha_ingreso || '';
            if(idEmpleadoInput) idEmpleadoInput.value = empleado.id_interno || ''; // Muestra ID interno
            formPersonal.querySelector('#email').value = empleado.email || '';
            const passInput = formPersonal.querySelector('#password');
            if(passInput) {
                passInput.value = '';
                passInput.placeholder = 'Dejar vacío para no cambiar';
                passInput.required = false; // No requerido al editar
            }
        } catch(e) {
             console.error("Error al intentar llenar el formulario:", e);
             alert("Error interno al intentar llenar los datos para editar.");
        }
    }

    // --- FUNCIÓN PARA PREPARAR MODAL PARA EDITAR ---
    async function prepararModalParaEditar(id_empleado) {
        idEmpleadoEditar = id_empleado;
        if (modalTitulo) modalTitulo.textContent = 'Editar Empleado';
        if (modalSubmitButton) modalSubmitButton.textContent = 'Actualizar';
        if (idEmpleadoInput) idEmpleadoInput.readOnly = true;

        try {
            const response = await fetch(`${API_URL}?id=${id_empleado}`); // Pide datos de un ID específico
             const contentType = response.headers.get("content-type");
             if (!contentType || !contentType.includes("application/json")) {
                 const textResponse = await response.text();
                 throw new Error(`Respuesta inesperada del servidor al buscar empleado: ${textResponse.substring(0, 200)}...`);
             }
            const resultado = await response.json();

            if (!resultado.success || !resultado.data) {
                 throw new Error(resultado.message || 'No se encontraron datos del empleado para editar.');
             }
            // La API debería devolver un solo objeto 'data' al pedir por ID
            llenarFormularioParaEditar(resultado.data);
            abrirModal();

        } catch (error) {
           console.error('Error al obtener datos para editar:', error);
           alert(`Error al cargar datos para editar: ${error.message}`);
           idEmpleadoEditar = null; // Resetea si falla
       }
    }

    // --- FUNCIÓN PARA PREPARAR MODAL PARA REGISTRAR ---
    function prepararModalParaRegistrar() {
        idEmpleadoEditar = null;
        if (formPersonal) {
             formPersonal.reset();
             const passInput = formPersonal.querySelector('#password');
             if (passInput) {
                 passInput.placeholder = '';
                 passInput.required = true; // Requerido al registrar
             }
        }
        if (idEmpleadoInput) {
             idEmpleadoInput.value = '';
             idEmpleadoInput.readOnly = true;
        }
        if (modalTitulo) modalTitulo.textContent = 'Registrar Nuevo Empleado';
        if (modalSubmitButton) modalSubmitButton.textContent = 'Registrar';
        abrirModal();
    }


    // --- TAREA 1 y 5 (Guardar): FUNCIÓN UNIFICADA ---
    async function guardarPersonal(evento) {
        evento.preventDefault();
        const formData = new FormData(formPersonal);
        let metodo = 'POST';
        let url = API_URL;
        let bodyContent;
        let headers = {};

        if (idEmpleadoEditar) { // Editando
            metodo = 'PUT';
            const params = new URLSearchParams();
            formData.forEach((value, key) => {
                 // No enviar pass vacía al editar
                 if (key === 'password' && value === '') return;
                params.append(key, value);
            });
            params.append('id_empleado_editar', idEmpleadoEditar);
            bodyContent = params.toString();
            headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
        } else { // Registrando
            bodyContent = formData;
        }

        try {
            const response = await fetch(url, { method: metodo, headers: headers, body: bodyContent });
             const contentType = response.headers.get("content-type");
             if (!contentType || !contentType.includes("application/json")) {
                 const textResponse = await response.text();
                 throw new Error(`Respuesta inesperada del servidor: ${textResponse.substring(0, 200)}...`);
             }
            const resultado = await response.json();

            if (resultado.success) {
                alert(resultado.message);
                cerrarModal();
                cargarPersonal();
            } else { throw new Error(resultado.message); }
        } catch (error) {
            console.error(`Error al ${idEmpleadoEditar ? 'actualizar' : 'registrar'}:`, error);
             if (error.message.startsWith('Respuesta inesperada')) {
                 alert(`Error: El servidor no respondió correctamente. ${error.message}`);
             } else { alert(`Error: ${error.message}`); }
        }
    }

    // --- LÓGICA PARA GENERAR PREFIJO DE ID ---
    function generarPrefijoId() {
        if (!rolSelect || !idEmpleadoInput || idEmpleadoEditar) return; // No generar si estamos editando
        const rolId = rolSelect.value;
        let prefijo = '';
        const prefijos = { '1': 'ADM-', '2': 'MES-', '3': 'MEC-', '4': 'JFT-', '5': 'RCT-', '6': 'ALM-', '7': 'CON-' };
        prefijo = prefijos[rolId] || '';
        idEmpleadoInput.value = prefijo;
    }

    // --- FUNCIONES Y EVENTOS DEL MODAL ---
    function abrirModal() { if (modalOverlay) modalOverlay.classList.remove('oculto'); }
    function cerrarModal() {
        if (modalOverlay) {
            modalOverlay.classList.add('oculto');
            if(formPersonal) formPersonal.reset();
            if(idEmpleadoInput) idEmpleadoInput.value = '';
            idEmpleadoEditar = null; // Resetea estado de edición
            if (modalTitulo) modalTitulo.textContent = 'Registrar Nuevo Empleado';
            if (modalSubmitButton) modalSubmitButton.textContent = 'Registrar';
             const passInput = formPersonal ? formPersonal.querySelector('#password') : null;
             if(passInput) { passInput.placeholder = ''; passInput.required = true; } // Vuelve a ser requerido
        }
    }

    // --- MANEJADORES DE EVENTOS ---
    if (btnAbrirModal) btnAbrirModal.addEventListener('click', prepararModalParaRegistrar);
    if (btnCerrarModal) btnCerrarModal.addEventListener('click', cerrarModal);
    if (modalOverlay) modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) cerrarModal(); });
    if (formPersonal) formPersonal.addEventListener('submit', guardarPersonal);
    if (rolSelect) rolSelect.addEventListener('change', generarPrefijoId);

    // Listener para ordenar tabla
    if (tablaHead) {
        tablaHead.addEventListener('click', (evento) => {
            const th = evento.target.closest('th.sortable');
            if (!th) return;
            const column = th.dataset.column;
            if (currentSortColumn === column) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortColumn = column;
                currentSortDirection = 'asc';
            }
            renderTabla();
        });
    }
    // Listener para botones de acción en la tabla
    if (tablaBody) {
        tablaBody.addEventListener('click', function(evento) {
            const target = evento.target;
            // Asegurarse que el target sea un botón antes de leer dataset
            if (target.tagName === 'BUTTON' && target.dataset.id) {
                 const id = target.dataset.id;
                 if (target.classList.contains('btn-eliminar')) {
                    eliminarPersonal(id); // Desactivar
                } else if (target.classList.contains('btn-reactivar')) {
                    reactivarPersonal(id); // Reactivar
                } else if (target.classList.contains('btn-editar')) {
                    prepararModalParaEditar(id); // Editar
                }
            }
        });
    }

    // --- INICIAR TODO ---
    cargarPersonal(); // Carga inicial de datos

    // Código para convertir a mayúsculas (excepto email y ID readonly)
    document.querySelectorAll('input[type="text"], input[type="email"]').forEach(input => {
         if (input.type !== 'email' && input.id !== 'id_empleado') {
             input.addEventListener('input', () => input.value = input.value.toUpperCase());
         } else if (input.type === 'email') {
             input.addEventListener('input', () => input.value = input.value.toLowerCase());
         }
    });

}); // Fin del DOMContentLoaded