/*
 * Portal/portal-taller/js/taller.js
 * Lógica completa con VALIDACIÓN REAL de Conductor.
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
    const alertaConductor = document.getElementById('alerta-conductor'); // TU ALERTA
    const alertaTiempo = document.getElementById('alerta-tiempo');
    const inputObs = document.getElementById('obs-recepcion');
    
    // Fotos y Mensajes
    const inputCamion = document.getElementById("foto-camion");
    const mensajeCamion = document.getElementById("mensaje-foto-camion");

    // Datos ocultos para lógica
    let fechaEstimadaMantenimiento = null;
    let imagenDuplicadaCamion = false;
    const imagenesCamionSubidas = [];

    // Modal Aviso WhatsApp
    const modalAviso = document.getElementById("modal-aviso");
    const cerrarAviso = document.getElementById("cerrar-aviso");
    const continuarBtn = document.getElementById("continuar-subida");
    const cancelarBtn = document.getElementById("cancelar-subida");
// ==========================================================================
    // 2. LÓGICA DE VALIDACIÓN DE IMÁGENES (NUEVA)
    // ==========================================================================

    function mostrarMensaje(texto, tipo) {
        if (!mensajeCamion) return;
        mensajeCamion.innerHTML = '';
        const div = document.createElement("div");
        div.innerHTML = texto; // Usamos innerHTML para permitir negritas
        div.className = `alerta ${tipo}`;
        mensajeCamion.appendChild(div);
    }

function analizarMetadatos(blob, archivoOriginal) {
        return new Promise((resolve, reject) => {
            
            if (typeof EXIF === 'undefined') {
                resolve("Librería EXIF no disponible. Imagen aceptada."); 
                return;
            }

            EXIF.getData(blob, function () {
                const allMetaData = EXIF.getAllTags(this);
                
                // 1. VALIDACIÓN DE INTEGRIDAD
                if (Object.keys(allMetaData).length === 0 || (!allMetaData.DateTimeOriginal && !allMetaData.Model)) {
                    // Limpiamos los inputs ocultos si falla
                    document.getElementById('meta_fecha_captura').value = "";
                    document.getElementById('meta_datos_json').value = "";
                    reject("⚠️ <strong>Alerta de Origen:</strong> La imagen parece venir de WhatsApp (sin metadatos).<br>Se requiere una foto original.");
                    return;
                }
                
                const fechaFotoRaw = allMetaData.DateTimeOriginal || allMetaData.DateTime;
                const modelo = allMetaData.Model || "modelo-desconocido";
                const hash = `${fechaFotoRaw}-${modelo}-${archivoOriginal.size}`;

                // --- INYECCIÓN DE DATOS PARA EL BACKEND ---
                // Guardamos todo el JSON de metadatos
                document.getElementById('meta_datos_json').value = JSON.stringify(allMetaData);

                // Formateamos la fecha para MySQL (YYYY-MM-DD HH:MM:SS)
                // El formato EXIF suele ser "YYYY:MM:DD HH:MM:SS", solo cambiamos los primeros ':' por '-'
                if (fechaFotoRaw) {
                    // Truco simple de string: Reemplaza los primeros 2 ':' por '-'
                    let fechaMySQL = fechaFotoRaw.substring(0, 10).replace(/:/g, '-') + fechaFotoRaw.substring(10);
                    document.getElementById('meta_fecha_captura').value = fechaMySQL;
                }
                // ------------------------------------------

                // 2. VALIDACIÓN DE DUPLICADOS
                if (imagenesCamionSubidas.includes(hash)) {
                    reject("⛔ <strong>Error de Duplicidad:</strong> Ya has intentado subir esta misma foto en esta sesión.");
                    return;
                }

                // 3. VALIDACIÓN DE FECHA (Tu lógica existente...)
                if (fechaFotoRaw) {
                    // A. Parsear fecha de la foto (EXIF viene como "2025:11:25 14:30:00")
                    const partes = fechaFotoRaw.split(" "); // Separar fecha y hora
                    const fechaPartes = partes[0].split(":");
                    const horaPartes = partes[1].split(":");
                    
                    // Mes en JS es base 0 (Enero = 0)
                    const fechaFoto = new Date(
                        fechaPartes[0], fechaPartes[1] - 1, fechaPartes[2], 
                        horaPartes[0], horaPartes[1], horaPartes[2]
                    );
                    
                    // B. Obtener fecha "Ahora" (Momento de la subida)
                    // Usamos "new Date()" del sistema para comparar contra el momento real de carga
                    const ahora = new Date();

                    // C. Calcular diferencia en HORAS
                    // Restamos milisegundos y convertimos: 1000ms * 60s * 60m = 1 hora
                    const diferenciaMilisegundos = Math.abs(ahora - fechaFoto);
                    const horasDiferencia = diferenciaMilisegundos / (1000 * 60 * 60);

                    // D. CONFIGURACIÓN DEL LÍMITE
                    const LIMITE_HORAS = 4; // <--- AQUÍ CAMBIAS TU TOLERANCIA (1, 4, 12, etc.)

                    if (horasDiferencia > LIMITE_HORAS) {
                        // Formato bonito para el mensaje
                        const fechaLegible = fechaFoto.toLocaleString();
                        
                        reject(`⚠️ <strong>Evidencia Antigua:</strong> La foto fue tomada hace ${horasDiferencia.toFixed(1)} horas (${fechaLegible}).<br>
                                El límite permitido es de ${LIMITE_HORAS} horas respecto al momento de carga.`);
                        return;
                    }
                    
                    // Opcional: Validar que la foto no sea del "futuro" (por error de configuración de cámara)
                    if (fechaFoto > ahora) {
                         reject(`⚠️ <strong>Error de Fecha:</strong> La fecha de la foto está en el futuro. Revisa la configuración de tu cámara.`);
                         return;
                    }
                }

                imagenesCamionSubidas.push(hash);
                resolve("✅ Imagen válida, original y reciente.");
            });
        });
    }

    async function procesarArchivo(archivo) {
        if(mensajeCamion) mensajeCamion.innerHTML = "Analizando imagen...";
        imagenDuplicadaCamion = false;

        if (!archivo || !archivo.type.startsWith("image/")) {
            mostrarMensaje("El archivo no es una imagen válida.", "error");
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
                    mostrarMensaje(err, "error");
                    
                    // Mostrar Modal de Aviso si existe error
                    if (modalAviso) {
                        modalAviso.style.display = "block";
                        imagenDuplicadaCamion = true; 
                        
                        // Actualizar texto del modal si quieres ser más específico (Opcional)
                        // document.querySelector('#modal-aviso h2').innerText = "Problema con la Evidencia";
                    } else {
                        if(!confirm(err.replace(/<[^>]*>?/gm, '') + "\n¿Deseas usarla de todas formas?")) {
                            if(inputCamion) inputCamion.value = "";
                            imagenDuplicadaCamion = true;
                        } else {
                            imagenDuplicadaCamion = false;
                        }
                    }
                });
        } catch (error) {
            mostrarMensaje("Error procesando: " + error.message, "error");
        }
    }

    if (inputCamion) {
        inputCamion.addEventListener("change", function (event) {
            const archivo = event.target.files[0];
            if (archivo) procesarArchivo(archivo);
        });
    }

    // ==========================================================================
    // 2. LÓGICA DEL MODAL PRINCIPAL
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
        
        // Limpiar UI
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
    // 3. AUTOCOMPLETADO: BUSCAR CAMIÓN
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
                        item.style.padding = "8px";
                        item.style.cursor = "pointer";
                        item.style.borderBottom = "1px solid #eee";
                        
                        item.addEventListener('click', () => {
                            // Llenar datos visuales
                            inputBuscarCamion.value = c.numero_economico;
                            if(document.getElementById('id_camion_seleccionado')) 
                                document.getElementById('id_camion_seleccionado').value = c.id;
                            if(infoPlacas) infoPlacas.value = c.placas;
                            
                            // --- LÓGICA CRÍTICA PARA VALIDACIÓN DE CONDUCTOR ---
                            if(c.nombre_chofer) {
                                if(infoConductor) infoConductor.value = c.nombre_chofer;
                                if(hiddenIdConductor) {
                                    hiddenIdConductor.value = c.id_chofer;
                                    // GUARDAMOS EL ID INTERNO (ej: CON-011) EN UN DATASET PARA COMPARAR
                                    hiddenIdConductor.dataset.interno = c.id_interno_chofer; 
                                }
                            } else {
                                if(infoConductor) infoConductor.value = "Sin Asignar";
                                if(hiddenIdConductor) {
                                    hiddenIdConductor.value = "";
                                    hiddenIdConductor.dataset.interno = ""; // Limpiamos
                                }
                            }
                            // ---------------------------------------------------

                            // Validaciones
                            fechaEstimadaMantenimiento = c.fecha_estimada_mantenimiento; 
                            validarTiempo(); 
                            validarConductor(); // Validar por si acaso

                            const selectServicio = document.getElementById('tipo-servicio');
                            if(selectServicio && (c.estado_salud === 'Próximo' || c.estado_salud === 'Vencido')) {
                                selectServicio.value = "Mantenimiento Preventivo (Aceite/Filtros)";
                            }

                            listaCamion.style.display = 'none';
                        });
                        listaCamion.appendChild(item);
                    });
                } else if (listaCamion) {
                    listaCamion.style.display = 'none';
                }
            } catch (error) {
                console.error("Error buscando camión:", error);
            }
        });
        
        document.addEventListener('click', (e) => {
             if (listaCamion && !listaCamion.contains(e.target) && e.target !== inputBuscarCamion) {
                listaCamion.style.display = 'none';
            }
        });
    }


    // ==========================================================================
    // 4. AUTOCOMPLETADO: BUSCAR CONDUCTOR (ENTREGA) & VALIDACIÓN
    // ==========================================================================

    // Función que compara los conductores
    function validarConductor() {
        if (!alertaConductor || !inputIdConductorEntrega || !hiddenIdConductor) return;

        const idAsignado = hiddenIdConductor.dataset.interno; // El del camión (ej: CON-011)
        const idEntrega = inputIdConductorEntrega.value; // El seleccionado (ej: CON-012)

        // Solo mostramos alerta si ambos existen y son diferentes
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
            
            if (query.length < 2) {
                if(listaChofer) listaChofer.style.display = 'none';
                return;
            }

            try {
                const res = await fetch('../portal-camiones/fetch_catalogos.php?tipo=conductores');
                const todosConductores = await res.json();
                
                const filtrados = todosConductores.filter(c => 
                    c.nombre_completo.toLowerCase().includes(query.toLowerCase()) || 
                    c.id_usuario.toLowerCase().includes(query.toLowerCase())
                );

                if (filtrados.length > 0 && listaChofer) {
                    listaChofer.style.display = 'block';
                    filtrados.forEach(c => {
                        const item = document.createElement('div');
                        item.textContent = `${c.nombre_completo} (${c.id_usuario})`; 
                        item.style.padding = "8px";
                        item.style.cursor = "pointer";
                        item.style.borderBottom = "1px solid #eee";

                        item.addEventListener('click', () => {
                            inputConductorEntrega.value = c.nombre_completo;
                            
                            // Guardamos el ID interno (ej: CON-012)
                            if(inputIdConductorEntrega) inputIdConductorEntrega.value = c.id_usuario; 
                            
                            if(listaChofer) listaChofer.style.display = 'none';
                            
                            // Ejecutamos la validación
                            validarConductor();
                        });
                        
                        item.addEventListener('mouseenter', () => { item.style.backgroundColor = "#f1f1f1"; });
                        item.addEventListener('mouseleave', () => { item.style.backgroundColor = "white"; });

                        listaChofer.appendChild(item);
                    });
                } else if(listaChofer) {
                    listaChofer.style.display = 'none';
                }

            } catch (error) {
                console.error("Error buscando conductor:", error);
            }
        });
        
        document.addEventListener('click', (e) => {
             if (listaChofer && !listaChofer.contains(e.target) && e.target !== inputConductorEntrega) {
                listaChofer.style.display = 'none';
            }
        });
    }


    // ==========================================================================
    // 5. VALIDACIÓN DE TIEMPO
    // ==========================================================================
    
    function validarTiempo() {
        if (!fechaEstimadaMantenimiento || !alertaTiempo) return;

        const elFecha = document.getElementById('fecha-entrada');
        if(!elFecha) return;

        const fechaEntrada = new Date(elFecha.value);
        const fechaEstimada = new Date(fechaEstimadaMantenimiento);
        
        const diffTime = fechaEntrada - fechaEstimada;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 

        alertaTiempo.style.display = 'none';
        if(inputObs) {
            inputObs.required = false;
            inputObs.placeholder = "Observaciones...";
        }

        if (diffDays < -7) {
            alertaTiempo.style.display = 'block';
            alertaTiempo.className = "alerta-visual alerta-roja";
            alertaTiempo.innerHTML = `⚠️ <strong>Entrada Anticipada:</strong> Programada: ${fechaEstimadaMantenimiento}. (${Math.abs(diffDays)} días antes). <br> *Justificación obligatoria.`;
            if(inputObs) {
                inputObs.required = true;
                inputObs.placeholder = "¿Por qué ingresa antes de su fecha programada?";
            }
        } else if (diffDays > 7) {
            alertaTiempo.style.display = 'block';
            alertaTiempo.className = "alerta-visual alerta-roja";
            alertaTiempo.innerHTML = `⚠️ <strong>Entrada Tardía:</strong> Retraso de ${diffDays} días.`;
        }
    }
    
    const fechaInput = document.getElementById('fecha-entrada');
    if(fechaInput) fechaInput.addEventListener('change', validarTiempo);


    // ==========================================================================
    // 6. PROCESAMIENTO DE IMÁGENES
    // ==========================================================================

    function mostrarMensaje(texto, tipo) {
        if (!mensajeCamion) return;
        mensajeCamion.innerHTML = '';
        const div = document.createElement("div");
        div.textContent = texto;
        div.className = `alerta ${tipo}`;
        mensajeCamion.appendChild(div);
    }

    function analizarMetadatos(blob, archivoOriginal) {
        return new Promise((resolve, reject) => {
            if (typeof EXIF === 'undefined') {
                resolve("Librería EXIF no disponible. Imagen aceptada."); 
                return;
            }
            EXIF.getData(blob, function () {
                const allMetaData = EXIF.getAllTags(this);
                if (Object.keys(allMetaData).length === 0 || (!allMetaData.DateTimeOriginal && !allMetaData.Model)) {
                    reject("⚠️ La imagen parece venir de WhatsApp (sin metadatos).");
                    return;
                }
                const fecha = allMetaData.DateTimeOriginal || allMetaData.DateTime || "sin-fecha";
                const modelo = allMetaData.Model || "modelo-desconocido";
                const hash = `${fecha}-${modelo}-${archivoOriginal.size}`;

                if (imagenesCamionSubidas.includes(hash)) {
                    reject("⚠️ Imagen duplicada.");
                } else {
                    imagenesCamionSubidas.push(hash);
                    resolve("✅ Imagen aceptada.");
                }
            });
        });
    }

    async function procesarArchivo(archivo) {
        if(mensajeCamion) mensajeCamion.innerHTML = "Analizando imagen...";
        imagenDuplicadaCamion = false;

        if (!archivo || !archivo.type.startsWith("image/")) {
            mostrarMensaje("El archivo no es una imagen válida.", "error");
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
                    mostrarMensaje(err, "error");
                    if (modalAviso) {
                        modalAviso.style.display = "block";
                        imagenDuplicadaCamion = true; 
                    } else {
                        if(!confirm(err + "\n¿Deseas usarla de todas formas?")) {
                            if(inputCamion) inputCamion.value = "";
                            imagenDuplicadaCamion = true;
                        } else {
                            imagenDuplicadaCamion = false;
                        }
                    }
                });
        } catch (error) {
            mostrarMensaje("Error procesando: " + error.message, "error");
        }
    }

    if (inputCamion) {
        inputCamion.addEventListener("change", function (event) {
            const archivo = event.target.files[0];
            if (archivo) procesarArchivo(archivo);
        });
    }

    // Eventos del Modal de Aviso
    if (modalAviso) {
        function cancelarSubida() {
            modalAviso.style.display = "none";
            if(inputCamion) inputCamion.value = ""; 
            mostrarMensaje("Subida cancelada.", "error");
            imagenDuplicadaCamion = true;
        }

        if(cerrarAviso) cerrarAviso.onclick = cancelarSubida;
        if(cancelarBtn) cancelarBtn.onclick = cancelarSubida;
        
        if(continuarBtn) continuarBtn.onclick = (e) => {
            e.preventDefault(); 
            modalAviso.style.display = "none";
            imagenDuplicadaCamion = false; 
            mostrarMensaje("⚠️ Imagen aceptada por decisión del usuario.", "ok");
        };
        
        window.addEventListener('click', (e) => {
            if (e.target == modalAviso) cancelarSubida();
        });
    }


    // ==========================================================================
    // 7. ENVÍO DEL FORMULARIO
    // ==========================================================================

    if (form) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            if (imagenDuplicadaCamion) {
                alert("🚫 La imagen no es válida o fue rechazada.");
                return;
            }

            const formData = new FormData(this);

            try {
                const res = await fetch('php/registrar_entrada.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) {
                console.error(error);
                alert("Error de conexión al registrar.");
            }
        });
    }

});