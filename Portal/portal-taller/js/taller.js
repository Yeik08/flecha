document.addEventListener('DOMContentLoaded', function() {

    // Referencias
    const modal = document.getElementById('modal-recepcion');
    const btnAbrir = document.getElementById('btn-nueva-entrada');
    const btnCerrar = document.getElementById('cerrar-modal');
    const form = document.getElementById('form-recepcion');
    
    // Inputs
    const inputBuscarCamion = document.getElementById('input-buscar-camion');
    const listaCamion = document.getElementById('sugerencias-camion');
    const inputConductorEntrega = document.getElementById('input-conductor-entrega');
    const listaChofer = document.getElementById('sugerencias-chofer-entrega');
    
    // Alertas y Lógica
    const alertaConductor = document.getElementById('alerta-conductor');
    const alertaTiempo = document.getElementById('alerta-tiempo');
    const inputObs = document.getElementById('obs-recepcion');
    
    // Datos ocultos para lógica
    let fechaEstimadaMantenimiento = null; // Se llenará al buscar camión

    // 1. Abrir Modal (Poner fecha actual)
    btnAbrir.addEventListener('click', () => {
        modal.classList.remove('oculto');
        modal.style.display = 'block';
        
        // Poner fecha/hora actual
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('fecha-entrada').value = now.toISOString().slice(0,16);
    });

    btnCerrar.addEventListener('click', () => { modal.style.display = 'none'; });

    // 2. Buscar Camión (Autocompletado)
    inputBuscarCamion.addEventListener('input', async function() {
        const q = this.value;
        if(q.length < 2) { listaCamion.style.display='none'; return; }
        
        const res = await fetch(`php/buscar_camion_express.php?q=${q}`);
        const data = await res.json();
        
        listaCamion.innerHTML = '';
        listaCamion.style.display = 'block';
        
        data.forEach(c => {
            const item = document.createElement('div');
            item.textContent = `${c.numero_economico} - ${c.placas}`;
            item.onclick = () => {
                // Llenar datos
                inputBuscarCamion.value = c.numero_economico;
                document.getElementById('id_camion_seleccionado').value = c.id;
                document.getElementById('info-placas').value = c.placas;
                
                // Conductor Asignado
                if(c.nombre_chofer) {
                    document.getElementById('info-conductor-asignado').value = c.nombre_chofer;
                    document.getElementById('id_conductor_asignado_hidden').value = c.id_chofer_asignado;
                } else {
                    document.getElementById('info-conductor-asignado').value = "Sin Asignar";
                    document.getElementById('id_conductor_asignado_hidden').value = "";
                }

                // Guardar fecha estimada para validación de tiempo
                fechaEstimadaMantenimiento = c.fecha_estimada_mantenimiento; // "YYYY-MM-DD"
                validarTiempo(); // Ejecutar validación inmediata

                // Preseleccionar mantenimiento si está próximo
                if(c.estado_salud === 'Próximo' || c.estado_salud === 'Vencido') {
                    document.getElementById('tipo-servicio').value = "Mantenimiento Preventivo (Aceite/Filtros)";
                }

                listaCamion.style.display = 'none';
            };
            listaCamion.appendChild(item);
        });
    });

    // 3. Buscar Conductor de Entrega
    inputConductorEntrega.addEventListener('input', async function() {
        const q = this.value;
        // (Aquí deberías tener un endpoint para buscar choferes, o usar uno genérico)
        // Por simplicidad, asumimos que existe php/buscar_chofer.php o similar
        // Si no, puedes cargar todos al inicio.
        // ... lógica de búsqueda similar a camión ...
    });
    // NOTA: Para la demo, validaremos cuando el usuario seleccione/escriba algo.

    // 4. Validación de Conductor (Al cambiar el input de entrega)
    inputConductorEntrega.addEventListener('change', () => {
        const asignadoID = document.getElementById('id_conductor_asignado_hidden').value;
        // Aquí necesitarías comparar IDs reales. 
        // Para simplificar visualmente:
        alertaConductor.style.display = 'block'; // Mostrar advertencia (Lógica real requiere IDs)
    });

    // 5. Lógica de Tiempo (Temprano/Tarde)
    function validarTiempo() {
        if(!fechaEstimadaMantenimiento) return;
        
        const fechaEntrada = new Date(document.getElementById('fecha-entrada').value);
        const fechaEstimada = new Date(fechaEstimadaMantenimiento);
        
        // Diferencia en días
        const diffTime = fechaEntrada - fechaEstimada;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 

        alertaTiempo.style.display = 'none';
        inputObs.required = false;
        inputObs.placeholder = "Observaciones...";

        if (diffDays < -7) {
            // Entró MUY TEMPRANO (Más de 7 días antes)
            alertaTiempo.style.display = 'block';
            alertaTiempo.innerHTML = `⚠️ <strong>Entrada Anticipada:</strong> Este camión está programado para el ${fechaEstimadaMantenimiento}. (Adelantado ${Math.abs(diffDays)} días). <br> *Justificación obligatoria.`;
            inputObs.required = true; // Hacemos obligatorio
            inputObs.placeholder = "¿Por qué ingresa antes de su fecha programada?";
        } else if (diffDays > 7) {
            // Entró TARDE
            alertaTiempo.style.display = 'block';
            alertaTiempo.innerHTML = `⚠️ <strong>Entrada Tardía:</strong> Retraso de ${diffDays} días según programa.`;
        }
    }

    // Recalcular tiempo si cambian la fecha manualmente
    document.getElementById('fecha-entrada').addEventListener('change', validarTiempo);

    // 6. Enviar Formulario
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        
        try {
            const res = await fetch('php/registrar_entrada.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if(data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        } catch(err) {
            alert("Error de conexión");
        }
    });

});
