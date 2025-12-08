/*
 * Portal/portal-taller/js/taller.js
 * VERSIÓN FINAL BLINDADA: Cero tolerancia a fotos sin metadatos.
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================================
    // 1. REFERENCIAS DOM
    // ==========================================================================
    
    const modal = document.getElementById('modal-recepcion');
    const btnAbrir = document.getElementById('btn-registrar-entrada');
    const btnCerrar = document.getElementById('cerrar-modal');
    const form = document.getElementById('form-recepcion');
    
    // Inputs
    const inputBuscarCamion = document.getElementById('input-buscar-camion');
    const listaCamion = document.getElementById('sugerencias-camion');
    const inputIdCamion = document.getElementById('id_camion_seleccionado');
    
    const infoPlacas = document.getElementById('info-placas');
    const infoConductor = document.getElementById('info-conductor-asignado');
    const hiddenIdConductor = document.getElementById('id_conductor_asignado_hidden');
    
    const inputConductorEntrega = document.getElementById('input-conductor-entrega');
    const listaChofer = document.getElementById('sugerencias-chofer-entrega');
    const inputIdConductorEntrega = document.getElementById('id_conductor_entrega');
    
    const inputFechaEntrada = document.getElementById('fecha-entrada');

    // Alertas
    const alertaConductor = document.getElementById('alerta-conductor'); 
    const alertaTiempo = document.getElementById('alerta-tiempo');
    const inputObs = document.getElementById('obs-recepcion');
    
    // Fotos y Mensajes
    const inputCamion = document.getElementById("foto-camion");
    const mensajeCamion = document.getElementById("mensaje-foto-camion");

    // Modal Aviso WhatsApp (Ahora funciona como Modal de Rechazo)
    const modalAviso = document.getElementById("modal-aviso");
    const cerrarAviso = document.getElementById("cerrar-aviso");
    const continuarBtn = document.getElementById("continuar-subida"); 
    const cancelarBtn = document.getElementById("cancelar-subida");

    // Datos ocultos para lógica
    let fechaEstimadaMantenimiento = null;
    let imagenDuplicadaCamion = false;
    const imagenesCamionSubidas = [];


    // ==========================================================================
    // 2. LÓGICA DE VALIDACIÓN DE IMÁGENES (ESTRICTA)
    // ==========================================================================

    function mostrarMensaje(texto, tipo) {
        if (!mensajeCamion) return;
        mensajeCamion.innerHTML = '';
        const div = document.createElement("div");
        div.innerHTML = texto;
        div.className = `alerta ${tipo}`;
        mensajeCamion.appendChild(div);
    }

    function analizarMetadatos(blob, archivoOriginal) {
        return new Promise((resolve, reject) => {
            
            if (typeof EXIF === 'undefined') {
                reject("⚠️ <strong>Error Crítico:</strong> Librería EXIF no cargada.");
                return;
            }

            EXIF.getData(blob, function () {
                const allMetaData = EXIF.getAllTags(this);
                
                // 1. VALIDACIÓN DE INTEGRIDAD ESTRICTA
                // Si no tiene DateTimeOriginal, SE RECHAZA.
                if (Object.keys(allMetaData).length === 0 || !allMetaData.DateTimeOriginal) {
                    // Limpiamos los inputs ocultos
                    if(document.getElementById('meta_fecha_captura')) document.getElementById('meta_fecha_captura').value = "";
                    if(document.getElementById('meta_datos_json')) document.getElementById('meta_datos_json').value = "";
                    
                    reject("⛔ <strong>FOTO RECHAZADA:</strong> La imagen no tiene fecha original (es de WhatsApp o captura).<br>Usa una foto original.");
                    return;
                }
                
                const fechaFotoRaw = allMetaData.DateTimeOriginal;
                const modelo = allMetaData.Model || "modelo-desconocido";
                const hash = `${fechaFotoRaw}-${modelo}-${archivoOriginal.size}`;

                // --- INYECCIÓN DE DATOS ---
                if(document.getElementById('meta_datos_json')) {
                    document.getElementById('meta_datos_json').value = JSON.stringify(allMetaData);
                }

                // Formato EXIF "YYYY:MM:DD" -> MySQL "YYYY-MM-DD"
                if (fechaFotoRaw && document.getElementById('meta_fecha_captura')) {
                    let fechaMySQL = fechaFotoRaw.substring(0, 10).replace(/:/g, '-') + fechaFotoRaw.substring(10);
                    document.getElementById('meta_fecha_captura').value = fechaMySQL;
                }

                // 2. VALIDACIÓN DE DUPLICADOS LOCAL
                if (imagenesCamionSubidas.includes(hash)) {
                    reject("⛔ <strong>DUPLICADO:</strong> Ya subiste esta foto en esta sesión.");
                    return;
                }

                // 3. VALIDACIÓN DE TIEMPO REAL (4 HORAS)
                if (fechaFotoRaw) {
                    const partes = fechaFotoRaw.split(" "); 
                    if (partes.length >= 2) {
                        const fechaPartes = partes[0].split(":"); 
                        const horaPartes = partes[1].split(":");  
                        
                        const fechaFoto = new Date(fechaPartes[0], fechaPartes[1]-1, fechaPartes[2], horaPartes[0], horaPartes[1], horaPartes[2]);
                        const ahora = new Date();
                        const diferenciaMilisegundos = ahora - fechaFoto;
                        const horasDiferencia = diferenciaMilisegundos / (1000 * 60 * 60); 

                        if (horasDiferencia > 4) {
                            reject(`⚠️ <strong>FOTO ANTIGUA:</strong> La foto es de hace ${horasDiferencia.toFixed(1)} horas.<br>Límite permitido: 4 horas.`);
                            return;
                        }
                    }
                }

                imagenesCamionSubidas.push(hash);
                resolve("✅ Foto original válida.");
            });
        });
    }

    async function procesarArchivo(archivo) {
        if(mensajeCamion) mensajeCamion.innerHTML = "Analizando integridad...";
        imagenDuplicadaCamion = false;

        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!archivo || !tiposPermitidos.includes(archivo.type)) {
            mostrarMensaje("⛔ Solo se aceptan imágenes JPG o PNG.", "error");
            if(inputCamion) inputCamion.value = ""; 
            imagenDuplicadaCamion = true;
            return;
        }

        try {
            await analizarMetadatos(archivo, archivo)
                .then(msg => {
                    mostrarMensaje(msg, "ok");
                    imagenDuplicadaCamion = false;
                })
                .catch(err => {
                    // SI FALLA LA VALIDACIÓN, SE LIMPIA TODO AUTOMÁTICAMENTE
                    mostrarMensaje(err, "error");
                    
                    if(inputCamion) inputCamion.value = ""; // BORRAR ARCHIVO DEL INPUT
                    imagenDuplicadaCamion = true; // BLOQUEAR FORMULARIO
                    
                    // Mostrar Modal Educativo (Solo informativo, no deja continuar)
                    if (modalAviso) {
                        // Ajustamos el modal para que parezca un mensaje de error final
                        if(continuarBtn) {
                            continuarBtn.textContent = "Entendido";
                            continuarBtn.style.backgroundColor = "#6c757d"; 
                            // Sobrescribimos el evento para que SOLO cierre
                            continuarBtn.onclick = cerrarModalAviso;
                        }
                        if(cancelarBtn) cancelarBtn.style.display = 'none'; // Ocultar botón cancelar
                        
                        modalAviso.style.display = "block";
                    } else {
                        // Si no hay modal, alerta nativa
                        alert(err.replace(/<[^>]*>?/gm, ''));
                    }
                });
        } catch (error) {
            mostrarMensaje("Error procesando: " + error.message, "error");
            if(inputCamion) inputCamion.value = "";
            imagenDuplicadaCamion = true;
        }
    }

    if (inputCamion) {
        inputCamion.addEventListener("change", function (event) {
            const archivo = event.target.files[0];
            if (archivo) procesarArchivo(archivo);
        });
    }

    // Funciones del Modal de Aviso/Rechazo
    function cerrarModalAviso() {
        if (modalAviso) modalAviso.style.display = "none";
        // Aseguramos que el input siga vacío
        if (inputCamion) inputCamion.value = ""; 
        mostrarMensaje("⛔ Carga rechazada. Sube una foto válida.", "error");
        imagenDuplicadaCamion = true;
    }

    if (modalAviso) {
        if(cerrarAviso) cerrarAviso.onclick = cerrarModalAviso;
        if(cancelarBtn) cancelarBtn.onclick = cerrarModalAviso;
        
        window.addEventListener('click', (e) => {
            if (e.target == modalAviso) cerrarModalAviso();
        });
    }


    // ==========================================================================
    // 3. LÓGICA DEL MODAL PRINCIPAL
    // ==========================================================================

    function abrirModal() {
        if (modal) {
            modal.classList.remove('oculto');
            modal.style.display = 'block';
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            if(document.getElementById('fecha-entrada')) {
                document.getElementById('fecha-entrada').value = now.toISOString().slice(0,16);
            }
        }
    }

    function cerrarModal() {
        if (modal) {
            modal.style.display = 'none';
            modal.classList.add('oculto');
        }
        if(form) form.reset();
        if(mensajeCamion) mensajeCamion.innerHTML = "";
        imagenDuplicadaCamion = false;
        
        if(listaCamion) listaCamion.style.display = 'none';
        if(listaChofer) listaChofer.style.display = 'none';
        if(alertaTiempo) alertaTiempo.style.display = 'none';
        if(alertaConductor) alertaConductor.style.display = 'none';
        
        if(modalAviso) modalAviso.style.display = 'none';
        imagenesCamionSubidas.length = 0; 
    }

    if (btnAbrir) btnAbrir.addEventListener('click', (e) => { e.preventDefault(); abrirModal(); });
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    window.addEventListener('click', e => { if (e.target == modal) cerrarModal(); });


    // ==========================================================================
    // 4. AUTOCOMPLETADO Y RESTO DE FUNCIONES (Sin cambios mayores)
    // ==========================================================================
    
    if (inputBuscarCamion) {
        inputBuscarCamion.addEventListener('input', async function() {
            const query = this.value.trim();
            if(listaCamion) listaCamion.innerHTML = '';
            
            if (query.length < 2) { 
                if(listaCamion) listaCamion.style.display = 'none'; 
                return; 
            }

            try {
                const res = await fetch(`php/buscar_camion_express.php?q=${query}`);
                const data = await res.json();
                
                if (data.length > 0 && listaCamion) {
                    listaCamion.style.display = 'block';
                    data.forEach(c => {
                        const item = document.createElement('div');
                        item.textContent = `${c.numero_economico} - ${c.placas}`;
                        item.style.padding = "8px"; item.style.cursor = "pointer"; item.style.borderBottom = "1px solid #eee";
                        
                        item.addEventListener('click', () => {
                            if (c.estatus === 'En Taller') {
                                alert(`⛔ La unidad ${c.numero_economico} ya está en taller. No puedes duplicar entrada.`);
                                inputBuscarCamion.value = '';
                                listaCamion.style.display = 'none';
                                return;
                            }
                            inputBuscarCamion.value = c.numero_economico;
                            if(document.getElementById('id_camion_seleccionado')) 
                                document.getElementById('id_camion_seleccionado').value = c.id;
                            if(infoPlacas) infoPlacas.value = c.placas;
                            
                            if(c.nombre_chofer) {
                                if(infoConductor) infoConductor.value = c.nombre_chofer;
                                if(hiddenIdConductor) {
                                    hiddenIdConductor.value = c.id_chofer;
                                    hiddenIdConductor.dataset.interno = c.id_interno_chofer; 
                                }
                            } else {
                                if(infoConductor) infoConductor.value = "Sin Asignar";
                                if(hiddenIdConductor) { hiddenIdConductor.value = ""; hiddenIdConductor.dataset.interno = ""; }
                            }

                            fechaEstimadaMantenimiento = c.fecha_estimada_mantenimiento; 
                            validarTiempo(); 
                            validarConductor(); 

                            const selectServicio = document.getElementById('tipo-servicio');
                            if(selectServicio && (c.estado_salud === 'Próximo' || c.estado_salud === 'Vencido')) {
                                selectServicio.value = "Mantenimiento Preventivo (Aceite/Filtros)";
                            }
                            listaCamion.style.display = 'none';
                        });
                        listaCamion.appendChild(item);
                    });
                } else if (listaCamion) { listaCamion.style.display = 'none'; }
            } catch (error) { console.error(error); }
        });
        document.addEventListener('click', (e) => {
             if (listaCamion && !listaCamion.contains(e.target) && e.target !== inputBuscarCamion) listaCamion.style.display = 'none';
        });
    }

    // Validación Conductor
    function validarConductor() {
        if (!alertaConductor || !inputIdConductorEntrega || !hiddenIdConductor) return;
        const idAsignado = hiddenIdConductor.dataset.interno;
        const idEntrega = inputIdConductorEntrega.value; 
        if (idAsignado && idEntrega && idAsignado !== idEntrega) {
            alertaConductor.style.display = 'block';
        } else {
            alertaConductor.style.display = 'none';
        }
    }

    if (inputConductorEntrega) {
        inputConductorEntrega.addEventListener('input', async function() {
            const query = this.value.trim();
            if(listaChofer) listaChofer.innerHTML = '';
            if (query.length < 2) { if(listaChofer) listaChofer.style.display = 'none'; return; }

            try {
                const res = await fetch('../portal-camiones/fetch_catalogos.php?tipo=conductores');
                const todosConductores = await res.json();
                const filtrados = todosConductores.filter(c => c.nombre_completo.toLowerCase().includes(query.toLowerCase()) || c.id_usuario.toLowerCase().includes(query.toLowerCase()));

                if (filtrados.length > 0 && listaChofer) {
                    listaChofer.style.display = 'block';
                    filtrados.forEach(c => {
                        const item = document.createElement('div');
                        item.textContent = `${c.nombre_completo} (${c.id_usuario})`; 
                        item.style.padding = "8px"; item.style.cursor = "pointer"; item.style.borderBottom = "1px solid #eee";
                        item.addEventListener('click', () => {
                            inputConductorEntrega.value = c.nombre_completo;
                            if(inputIdConductorEntrega) inputIdConductorEntrega.value = c.id_usuario; 
                            if(listaChofer) listaChofer.style.display = 'none';
                            validarConductor();
                        });
                        item.addEventListener('mouseenter', () => { item.style.backgroundColor = "#f1f1f1"; });
                        item.addEventListener('mouseleave', () => { item.style.backgroundColor = "white"; });
                        listaChofer.appendChild(item);
                    });
                } else if(listaChofer) { listaChofer.style.display = 'none'; }
            } catch (error) { console.error(error); }
        });
        document.addEventListener('click', (e) => {
             if (listaChofer && !listaChofer.contains(e.target) && e.target !== inputConductorEntrega) listaChofer.style.display = 'none';
        });
    }

    // Validación Tiempo
    function validarTiempo() {
        if (!fechaEstimadaMantenimiento || !alertaTiempo) return;
        const elFecha = document.getElementById('fecha-entrada');
        if(!elFecha) return;
        const fechaEntrada = new Date(elFecha.value);
        const fechaEstimada = new Date(fechaEstimadaMantenimiento);
        const diffTime = fechaEntrada - fechaEstimada;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 

        alertaTiempo.style.display = 'none';
        if(inputObs) { inputObs.required = false; inputObs.placeholder = "Observaciones..."; }

        if (diffDays < -7) {
            alertaTiempo.style.display = 'block';
            alertaTiempo.className = "alerta-visual alerta-roja";
            alertaTiempo.innerHTML = `⚠️ <strong>Entrada Anticipada:</strong> Programada: ${fechaEstimadaMantenimiento}. (${Math.abs(diffDays)} días antes). <br> *Justificación obligatoria.`;
            if(inputObs) { inputObs.required = true; inputObs.placeholder = "¿Por qué ingresa antes de su fecha programada?"; }
        } else if (diffDays > 7) {
            alertaTiempo.style.display = 'block';
            alertaTiempo.className = "alerta-visual alerta-roja";
            alertaTiempo.innerHTML = `⚠️ <strong>Entrada Tardía:</strong> Retraso de ${diffDays} días.`;
        }
    }
    const fechaInput = document.getElementById('fecha-entrada');
    if(fechaInput) fechaInput.addEventListener('change', validarTiempo);


    // ==========================================================================
    // 5. ENVÍO DEL FORMULARIO
    // ==========================================================================

    if (form) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const idCamionValido = document.getElementById('id_camion_seleccionado').value;
            if (!idCamionValido) { alert("⚠️ Error: Selecciona un camión de la lista."); return; }        
            
            if (imagenDuplicadaCamion) {
                alert("🚫 BLOQUEADO: La imagen no es válida (sin metadatos o formato incorrecto).");
                return;
            }

            const formData = new FormData(this);
            try {
                const res = await fetch('php/registrar_entrada.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) { console.error(error); alert("Error de conexión."); }
        });
    }

    // ==========================================================================
    // 6. CARGAR BITÁCORA Y MODAL DETALLE (Sin cambios)
    // ==========================================================================
    async function cargarTablaEntradas() {
        const tbody = document.getElementById('tabla-entradas-body');
        if (!tbody) return;
        try {
            const res = await fetch('php/listar_entradas.php');
            const data = await res.json();
            if (!data.success) { tbody.innerHTML = `<tr><td colspan="7" style="color:red;">Error: ${data.message}</td></tr>`; return; }
            if (data.data.length === 0) { tbody.innerHTML = `<tr><td colspan="7">No hay entradas.</td></tr>`; return; }
            tbody.innerHTML = ''; 
            data.data.forEach(entrada => {
                const tr = document.createElement('tr');
                let alertasHTML = '';
                if (entrada.alerta_conductor === 'Si') alertasHTML += `<span title="Conductor no coincide" style="font-size:1.2em;">👨‍💼⚠️</span> `;
                if (entrada.clasificacion_tiempo === 'Anticipado') alertasHTML += `<span style="color:#f39c12; font-weight:bold;">⏱️ Anticipado</span>`;
                else if (entrada.clasificacion_tiempo === 'Tarde') alertasHTML += `<span style="color:#e74c3c; font-weight:bold;">⏱️ Tarde</span>`;
                else alertasHTML += `<span style="color:#2ecc71;">OK</span>`;

                let estatusColor = entrada.estatus_entrada === 'Recibido' ? '#316960' : (entrada.estatus_entrada === 'En Proceso' ? '#f39c12' : '#ff0000');

                tr.innerHTML = `
                    <td><strong>${entrada.folio}</strong></td>
                    <td>${entrada.numero_economico}<br><small style="color:#777;">${entrada.placas}</small></td>
                    <td>${entrada.tipo}</td>
                    <td>${entrada.fecha_formato}</td>
                    <td style="text-align:center;">${alertasHTML}</td>
                    <td><span style="background-color:${estatusColor}; color:white; padding:4px 8px; border-radius:12px; font-size:0.85em;">${entrada.estatus_entrada}</span></td>
                    <td><button class="btn-ver" onclick="verDetalle(${entrada.id})">Ver</button></td>
                `;
                tbody.appendChild(tr);
            });
        } catch (error) { console.error(error); tbody.innerHTML = `<tr><td colspan="7">Error de conexión</td></tr>`; }
    }
    cargarTablaEntradas();

    const modalDetalle = document.getElementById('modal-detalle');
    const btnCerrarDetalle = document.getElementById('btn-cerrar-detalle');
    const equisCerrarDetalle = document.getElementById('cerrar-modal-detalle');

    window.verDetalle = async function(id) {
        if(!modalDetalle) return;
        try {
            document.getElementById('ver-folio').textContent = "Cargando...";
            const res = await fetch(`php/obtener_detalle_entrada.php?id=${id}`);
            const data = await res.json();
            if (data.success) {
                const d = data.data;
                document.getElementById('ver-folio').textContent = d.folio;
                document.getElementById('ver-eco').textContent = d.numero_economico;
                document.getElementById('ver-placas').textContent = d.placas;
                document.getElementById('ver-marca').textContent = `${d.marca} (${d.anio})`;
                document.getElementById('ver-gas').textContent = d.nivel_combustible;
                document.getElementById('ver-km').textContent = d.kilometraje_entrada + " km";
                document.getElementById('ver-fecha').textContent = d.fecha_ingreso_f;
                document.getElementById('ver-tipo').textContent = d.tipo_mantenimiento_solicitado;
                document.getElementById('ver-chofer').textContent = d.nombre_entrega ? d.nombre_entrega : (d.nombre_asignado + " (Asignado)");
                document.getElementById('ver-estatus').textContent = d.estatus_entrada;
                document.getElementById('ver-obs').textContent = d.observaciones_recepcion || "Ninguna.";
                const img = document.getElementById('ver-foto');
                if (img) { img.src = d.foto_evidencia; img.onerror = function() { this.src = '../img/sin_foto.png'; }; }
                
                const primerBoton = document.querySelector('.tab-btn');
                if(primerBoton) primerBoton.click();
                
                modalDetalle.classList.remove('oculto');
                modalDetalle.style.display = 'flex';
            } else { alert("Error: " + data.message); }
        } catch (error) { console.error(error); alert("Error al cargar."); }
    };

    function cerrarModalDetalle() {
        if(modalDetalle) { modalDetalle.classList.add('oculto'); modalDetalle.style.display = 'none'; }
    }
    if(btnCerrarDetalle) btnCerrarDetalle.addEventListener('click', cerrarModalDetalle);
    if(equisCerrarDetalle) equisCerrarDetalle.addEventListener('click', cerrarModalDetalle);
    window.addEventListener('click', e => { if (e.target == modalDetalle) cerrarModalDetalle(); });

});

window.cambiarTab = function(evt, tabId) {
    const paneles = document.getElementsByClassName("tab-panel");
    for (let i = 0; i < paneles.length; i++) { paneles[i].style.display = "none"; paneles[i].classList.remove("active"); }
    const botones = document.getElementsByClassName("tab-btn");
    for (let i = 0; i < botones.length; i++) { botones[i].className = botones[i].className.replace(" active", ""); }
    document.getElementById(tabId).style.display = "block";
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.className += " active";
};