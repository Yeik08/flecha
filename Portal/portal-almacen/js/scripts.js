/*
 * Portal/portal-almacen/js/scripts.js
 * VERSIÓN FINAL: Validaciones en vivo + Validación Ciega + Fotos Seguras
 */

document.addEventListener('DOMContentLoaded', function() {

    const btnBuscar = document.getElementById('btn-buscar-ticket');
    const formIntercambio = document.getElementById('form-intercambio');
    const inputTicket = document.getElementById('ticket-id');
    const selAlmacen = document.getElementById('select-almacen-actual');

    // Referencias a inputs para validación real-time
    const inputsValidables = {
        'filtro_nuevo_serie': 'filtro',
        'filtro_nuevo_centrifugo': 'filtro',
        'cubeta_1': 'cubeta',
        'cubeta_2': 'cubeta'
    };

    // Variables globales para reglas de negocio
    let aceiteRequerido = ""; 
    let almacenActualNombre = ""; 

    // =========================================================
    // 1. CONFIGURACIÓN INICIAL DE INPUTS (Agregar contenedores de error)
    // =========================================================
    for (const [name, tipo] of Object.entries(inputsValidables)) {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input) {
            // Crear elemento <small> para el error si no existe
            let errorSmall = document.createElement('small');
            errorSmall.id = `error-${name}`;
            errorSmall.style.display = 'block';
            errorSmall.style.marginTop = '4px';
            errorSmall.style.fontWeight = 'bold';
            
            // Insertar después del input
            input.parentNode.appendChild(errorSmall);

            // Listeners
            input.addEventListener('change', () => validarCampoEnVivo(input, tipo, name));
            input.addEventListener('input', () => {
                document.getElementById(`error-${name}`).innerText = ""; // Limpiar al escribir
            });
        }
    }

    // Listener para el select de almacén (para saber nombre ubicación y actualizar KPIs)
    if (selAlmacen) {
        selAlmacen.addEventListener('change', async () => {
            const id = selAlmacen.value;
            if (!id) return;

            // 1. Guardar nombre para validación
            almacenActualNombre = selAlmacen.options[selAlmacen.selectedIndex].text;
            
            // 2. Guardar ID en input oculto
            const inputHiddenAlmacen = document.getElementById('id_almacen_origen');
            if (inputHiddenAlmacen) inputHiddenAlmacen.value = id;

            // 3. Re-validar inputs si ya tienen texto
            for (const name of Object.keys(inputsValidables)) {
                const input = document.querySelector(`input[name="${name}"]`);
                if(input && input.value) input.dispatchEvent(new Event('change'));
            }

            // 4. Actualizar KPIs
            try {
                const res = await fetch(`php/get_kpis_almacen.php?id_almacen=${id}`);
                const data = await res.json();
                const kpiFiltros = document.getElementById('kpi-filtros');
                const kpiCubetas = document.getElementById('kpi-cubetas');
                if(kpiFiltros) kpiFiltros.textContent = data.filtros;
                if(kpiCubetas) kpiCubetas.textContent = data.cubetas;
            } catch(e) { console.error(e); }
        });
    }


    // =========================================================
    // 2. FUNCIÓN DE VALIDACIÓN EN VIVO (AJAX)
    // =========================================================
    async function validarCampoEnVivo(input, tipo, name) {
        const serie = input.value.trim();
        const errorBox = document.getElementById(`error-${name}`);
        if (!errorBox) return;

        errorBox.innerText = "🔄 Verificando...";
        errorBox.style.color = "#666";

        if (!serie) {
            errorBox.innerText = "";
            return;
        }

        // Validación Local: Duplicados de Cubetas
        if (tipo === 'cubeta') {
            const c1 = document.querySelector('input[name="cubeta_1"]').value.trim();
            const c2 = document.querySelector('input[name="cubeta_2"]').value.trim();
            if (c1 && c2 && c1 === c2) {
                errorBox.innerText = "⛔ Error: ¡Cubetas duplicadas!";
                errorBox.style.color = "red";
                return;
            }
        }

        try {
            const res = await fetch(`php/validar_item_live.php?serie=${encodeURIComponent(serie)}&tipo=${tipo}`);
            const data = await res.json();

            if (!data.valid) {
                errorBox.innerText = data.msg;
                errorBox.style.color = "red";
                input.style.borderColor = "red";
            } else {
                const item = data.data;
                
                // A. Ubicación (Comparación simple de string)
                // "Almacén Magdalena" debe contener "Magdalena"
                if (almacenActualNombre && !item.ubicacion.includes(almacenActualNombre.replace('Almacén ', ''))) {
                    errorBox.innerText = `⚠️ Cuidado: El sistema dice que esto está en ${item.ubicacion}.`;
                    errorBox.style.color = "orange";
                } 
                // B. Tipo de Filtro
                else if (tipo === 'filtro') {
                    if (name === 'filtro_nuevo_serie' && item.tipo_filtro !== 'Aceite') {
                        errorBox.innerText = `⛔ Error: Este filtro es de ${item.tipo_filtro}, debe ser Aceite.`;
                        errorBox.style.color = "red";
                    } else if (name === 'filtro_nuevo_centrifugo' && item.tipo_filtro !== 'Centrifugo') {
                        errorBox.innerText = `⛔ Error: Este filtro es de ${item.tipo_filtro}, debe ser Centrífugo.`;
                        errorBox.style.color = "red";
                    } else {
                        errorBox.innerText = data.msg;
                        errorBox.style.color = "green";
                        input.style.borderColor = "#ccc";
                    }
                }
                // C. Viscosidad de Aceite
                else if (tipo === 'cubeta' && aceiteRequerido) {
                    if (!item.nombre_producto.toUpperCase().includes(aceiteRequerido)) {
                        errorBox.innerText = `⛔ Error: El camión pide ${aceiteRequerido}, esto es ${item.nombre_producto}.`;
                        errorBox.style.color = "red";
                    } else {
                        errorBox.innerText = `✅ Correcto (${item.nombre_producto})`;
                        errorBox.style.color = "green";
                        input.style.borderColor = "#ccc";
                    }
                } else {
                    errorBox.innerText = data.msg;
                    errorBox.style.color = "green";
                    input.style.borderColor = "#ccc";
                }
            }
        } catch (e) {
            console.error(e);
            errorBox.innerText = "Error de conexión";
        }
    }


    // =========================================================
    // 3. BUSCAR TICKET
    // =========================================================
    if(btnBuscar) {
        btnBuscar.addEventListener('click', async () => {
            const ticket = inputTicket.value.trim();
            if (!ticket) return alert("Ingresa un folio.");

            btnBuscar.textContent = "Buscando...";
            btnBuscar.disabled = true;

            try {
                const res = await fetch(`php/buscar_datos_intercambio.php?ticket=${ticket}`);
                const data = await res.json();

                if (data.success) {
                    const d = data.data;
                    document.getElementById('id_entrada_hidden').value = d.id;
                    document.getElementById('id_camion_hidden').value = d.id_camion;
                    document.getElementById('info-unidad').value = `${d.numero_economico} - ${d.placas}`;
                    document.getElementById('info-mecanico').value = d.nombre_mecanico || "Sin Asignar";
                    
                    document.getElementById('secret-filtro-aceite').value = d.serie_filtro_aceite_actual || '';
                    document.getElementById('secret-filtro-centrifugo').value = d.serie_filtro_centrifugo_actual || '';
                    document.getElementById('tipo-servicio-hidden').value = d.proximo_servicio_tipo || 'Basico';

                    // GUARDAMOS EL ACEITE REQUERIDO PARA VALIDAR LUEGO
                    aceiteRequerido = (d.lubricante_sugerido || "").toUpperCase();

                    // --- MANEJO DE SERVICIO BÁSICO vs COMPLETO ---
                    const tipoServicio = d.proximo_servicio_tipo;
                    const divViejo = document.getElementById('container-centrifugo-viejo');
                    const divNuevo = document.getElementById('container-centrifugo-nuevo');
                    const inputCentNuevo = document.querySelector('input[name="filtro_nuevo_centrifugo"]');
                    const alertaDiv = document.getElementById('alerta-servicio');

                    if (tipoServicio === 'Completo') {
                        if(divViejo) divViejo.style.display = 'block';
                        if(divNuevo) divNuevo.style.display = 'block';
                        if(inputCentNuevo) inputCentNuevo.setAttribute('required', 'true');
                        if(alertaDiv) {
                            alertaDiv.innerHTML = `🔵 SERVICIO COMPLETO<br><small>Requiere: ${aceiteRequerido}</small>`;
                            alertaDiv.className = "alerta-azul";
                        }
                    } else {
                        if(divViejo) divViejo.style.display = 'none';
                        if(divNuevo) divNuevo.style.display = 'none';
                        if(inputCentNuevo) {
                            inputCentNuevo.removeAttribute('required');
                            inputCentNuevo.value = "";
                        }
                        if(alertaDiv) {
                            alertaDiv.innerHTML = `🟢 SERVICIO BÁSICO<br><small>Requiere: ${aceiteRequerido}</small>`;
                            alertaDiv.className = "alerta-verde";
                        }
                    }

                    alert("✅ Ticket validado. Procede a escanear.");
                } else {
                    alert(data.message); 
                    formIntercambio.reset();
                    document.getElementById('info-unidad').value = "";
                    document.getElementById('info-mecanico').value = "";
                }
            } catch (e) {
                console.error(e);
                alert("Error de conexión.");
            } finally {
                btnBuscar.textContent = "🔍 Buscar";
                btnBuscar.disabled = false;
            }
        });
    }


    // =========================================================
    // 4. VALIDACIÓN ESTRICTA DE FOTOS
    // =========================================================
    function validarInputFoto(inputElement) {
        inputElement.addEventListener('change', async function(e) {
            const archivo = e.target.files[0];
            if (!archivo) return;

            const parentDiv = inputElement.parentElement;
            
            let msgBox = parentDiv.querySelector('.mensaje-validacion');
            if (!msgBox) {
                msgBox = document.createElement('div');
                msgBox.className = 'mensaje-validacion';
                msgBox.style.fontSize = '0.85em';
                msgBox.style.marginTop = '5px';
                parentDiv.appendChild(msgBox);
            }
            msgBox.innerHTML = '🔄 Analizando metadatos...';
            msgBox.style.color = '#666';

            // 1. Validar Tipo
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!tiposPermitidos.includes(archivo.type)) {
                inputElement.value = ""; 
                msgBox.innerHTML = '⛔ <strong>Error:</strong> Solo JPG o PNG.';
                msgBox.style.color = 'red';
                return;
            }

            // 2. Validar Metadatos EXIF
            if (typeof EXIF !== 'undefined') {
                EXIF.getData(archivo, function() {
                    const meta = EXIF.getAllTags(this);
                    if (!meta.DateTimeOriginal) {
                        inputElement.value = ""; 
                        msgBox.innerHTML = '⛔ <strong>RECHAZADA:</strong> Sin fecha original (WhatsApp/Captura).';
                        msgBox.style.color = 'red';
                        alert("🚫 FOTO RECHAZADA\n\nEl sistema detectó que esta imagen no tiene fecha original.\nProbablemente viene de WhatsApp o es una captura de pantalla.\n\nPor favor usa una foto original.");
                    } else {
                        msgBox.innerHTML = '✅ Foto válida (Original).';
                        msgBox.style.color = 'green';
                    }
                });
            } else {
                msgBox.innerHTML = '⚠️ Advertencia: Librería EXIF no cargada.';
            }
        });
    }

    const inputsFotos = document.querySelectorAll('#form-intercambio input[type="file"]');
    inputsFotos.forEach(input => validarInputFoto(input));


    // =========================================================
    // 5. ENVÍO DEL FORMULARIO
    // =========================================================
    if(formIntercambio) {
        formIntercambio.addEventListener('submit', async (e) => {
            e.preventDefault();

            if(!selAlmacen.value) {
                alert("⚠️ Selecciona desde qué almacén estás despachando (arriba).");
                return;
            }
            
            const tipoServicio = document.getElementById('tipo-servicio-hidden').value;

            // A. VALIDACIÓN CIEGA DE ACEITE
            const esperadoAceite = document.getElementById('secret-filtro-aceite').value.trim().toUpperCase();
            const escaneadoAceite = formIntercambio.querySelector('input[name="filtro_viejo_serie"]').value.trim().toUpperCase();
            
            // Si el camión tenía filtro (esperadoAceite no vacío), debe coincidir
            if (esperadoAceite && esperadoAceite !== escaneadoAceite) {
                alert("⛔ ERROR DE SEGURIDAD (ACEITE)\n\nEl filtro escaneado NO CORRESPONDE.");
                return;
            }

            // B. VALIDACIÓN CIEGA DE CENTRÍFUGO
            if (tipoServicio === 'Completo') {
                const esperadoCent = document.getElementById('secret-filtro-centrifugo').value.trim().toUpperCase();
                const escaneadoCent = formIntercambio.querySelector('input[name="filtro_viejo_centrifugo_serie"]').value.trim().toUpperCase();

                if (esperadoCent && escaneadoCent !== esperadoCent) {
                    alert("⛔ ERROR DE SEGURIDAD (CENTRÍFUGO)\n\nEl filtro escaneado NO CORRESPONDE.");
                    return;
                }
            }

            // C. Validar fotos vacías
            let fotosValidas = true;
            inputsFotos.forEach(inp => { if(!inp.value) fotosValidas = false; });
            if(!fotosValidas) {
                alert("⚠️ Faltan evidencias válidas. Revisa los mensajes en rojo.");
                return;
            }

            if(!confirm("✅ Validación correcta. ¿Confirmas la entrega?")) return;

            // UI
            const btnSubmit = formIntercambio.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = "Procesando...";

            const formData = new FormData(formIntercambio);
            
            try {
                const res = await fetch('php/procesar_salida_material.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    alert("✅ " + data.message);
                    formIntercambio.reset();
                    document.getElementById('info-unidad').value = "";
                    document.getElementById('info-mecanico').value = "";
                    document.querySelectorAll('.mensaje-validacion').forEach(el => el.innerHTML = '');
                    // Limpiar mensajes de error de inputs
                    document.querySelectorAll('small[id^="error-"]').forEach(el => el.innerHTML = '');
                } else {
                    alert("❌ Error: " + data.message);
                }
            } catch (e) {
                alert("Error de conexión o servidor.");
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = "Confirmar Entrega de Material";
            }
        });
    }
});