/*
 * Portal/portal-taller/js/taller.js
 * Lógica completa para el Módulo de Recepción de Taller.
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================================
    // 1. REFERENCIAS DOM
    // ==========================================================================
    
    const modal = document.getElementById('modal-registro');
    const btnAbrir = document.getElementById('btn-registrar-entrada'); // Asegúrate que este ID exista en tu HTML
    const btnCerrar = document.getElementById('cerrar-modal');
    const form = document.getElementById('form-registro');
    
    // Inputs del formulario
    const inputBuscarCamion = document.getElementById('input-buscar-camion');
    const listaSugerenciasCamion = document.getElementById('sugerencias-camion');
    const inputIdCamion = document.getElementById('id_camion_seleccionado');
    
    const infoPlacas = document.getElementById('info-placas');
    const infoConductor = document.getElementById('info-conductor-asignado');
    const hiddenIdConductor = document.getElementById('id_conductor_asignado_hidden');
    
    const inputConductorEntrega = document.getElementById('input-conductor-entrega');
    const listaSugerenciasChofer = document.getElementById('sugerencias-chofer-entrega');
    const inputIdConductorEntrega = document.getElementById('id_conductor_entrega');

    // Input de Fotos y Mensajes
    const inputCamion = document.getElementById("foto-camion");
    const mensajeCamion = document.getElementById("mensaje-foto-camion");

    // Modal de Aviso (WhatsApp)
    const modalAviso = document.getElementById("modal-aviso");
    const cerrarAviso = document.getElementById("cerrar-aviso");
    const continuarBtn = document.getElementById("continuar-subida");
    const cancelarBtn = document.getElementById("cancelar-subida");

    // Variables de estado
    const imagenesCamionSubidas = [];
    let imagenDuplicadaCamion = false;


    // ==========================================================================
    // 2. LÓGICA DEL MODAL (ABRIR / CERRAR)
    // ==========================================================================

    if (btnAbrir) {
        btnAbrir.addEventListener('click', () => {
            modal.style.display = 'block';
            // Poner fecha/hora actual al abrir
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            if(document.getElementById('fecha-entrada')) {
                document.getElementById('fecha-entrada').value = now.toISOString().slice(0,16);
            }
        });
    }

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    window.addEventListener('click', e => {
        if (e.target == modal) cerrarModal();
        if (e.target == modalAviso) cancelarSubida();
    });

    function cerrarModal() {
        modal.style.display = 'none';
        form.reset();
        if(mensajeCamion) mensajeCamion.innerHTML = "";
        imagenDuplicadaCamion = false;
        if(modalAviso) modalAviso.style.display = 'none';
    }


    // ==========================================================================
    // 3. AUTOCOMPLETADO (CAMIÓN Y CONDUCTOR)
    // ==========================================================================

    // A. Buscar Camión
    if (inputBuscarCamion) {
        inputBuscarCamion.addEventListener('input', async function() {
            const query = this.value.trim();
            listaSugerenciasCamion.innerHTML = '';
            
            if (query.length < 2) {
                listaSugerenciasCamion.style.display = 'none';
                return;
            }

            try {
                // Ajusta la ruta si es necesario ('php/buscar_camion_express.php')
                const res = await fetch(`php/buscar_camion_express.php?q=${query}`);
                const data = await res.json();
                
                if (data.length > 0) {
                    listaSugerenciasCamion.style.display = 'block';
                    data.forEach(camion => {
                        const item = document.createElement('div');
                        item.textContent = `${camion.numero_economico} - ${camion.placas}`;
                        item.style.padding = "8px";
                        item.style.cursor = "pointer";
                        item.style.borderBottom = "1px solid #eee";
                        
                        item.addEventListener('click', () => {
                            inputBuscarCamion.value = camion.numero_economico;
                            if(inputIdCamion) inputIdCamion.value = camion.id;
                            if(infoPlacas) infoPlacas.value = camion.placas;
                            
                            if (camion.nombre_chofer) {
                                if(infoConductor) infoConductor.value = camion.nombre_chofer;
                                if(hiddenIdConductor) hiddenIdConductor.value = camion.id_chofer_asignado;
                            } else {
                                if(infoConductor) infoConductor.value = "Sin asignar";
                                if(hiddenIdConductor) hiddenIdConductor.value = "";
                            }
                            listaSugerenciasCamion.style.display = 'none';
                        });
                        listaSugerenciasCamion.appendChild(item);
                    });
                } else {
                    listaSugerenciasCamion.style.display = 'none';
                }
            } catch (error) {
                console.error("Error buscando camión:", error);
            }
        });
    }

    // B. Buscar Conductor de Entrega
    if (inputConductorEntrega) {
        inputConductorEntrega.addEventListener('input', async function() {
            const query = this.value.trim();
            listaSugerenciasChofer.innerHTML = '';
            if (query.length < 2) { listaSugerenciasChofer.style.display = 'none'; return; }
            
            // Nota: Aquí asumimos que usas el mismo endpoint o uno similar.
            // Si no tienes uno específico, puedes usar fetch_catalogos.php filtrando en JS (menos óptimo pero funcional)
            // O crear 'php/buscar_chofer.php' similar a 'buscar_camion_express.php'
        });
    }

    // Cierra listas al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (listaSugerenciasCamion && !listaSugerenciasCamion.contains(e.target) && e.target !== inputBuscarCamion) {
            listaSugerenciasCamion.style.display = 'none';
        }
    });


    // ==========================================================================
    // 4. VALIDACIÓN DE IMÁGENES (EXIF)
    // ==========================================================================

    function mostrarMensaje(texto, tipo) {
        if(!mensajeCamion) return;
        mensajeCamion.innerHTML = '';
        const div = document.createElement("div");
        div.textContent = texto;
        div.className = `alerta ${tipo}`;
        mensajeCamion.appendChild(div);
    }

    function analizarMetadatos(blob, archivoOriginal) {
        return new Promise((resolve, reject) => {
            if (typeof EXIF === 'undefined') {
                resolve("Librería EXIF no cargada. Saltando validación."); 
                return;
            }
            EXIF.getData(blob, function () {
                const allMetaData = EXIF.getAllTags(this);
                // Si no hay metadatos (típico de WhatsApp), rechazamos
                if (Object.keys(allMetaData).length === 0) {
                    reject("⚠️ La imagen parece venir de WhatsApp (sin metadatos).");
                    return;
                }
                const fecha = allMetaData.DateTimeOriginal || allMetaData.DateTime || "sin-fecha";
                const modelo = allMetaData.Model || "modelo-desconocido";
                const hash = `${fecha}-${modelo}-${archivoOriginal.size}`;

                if (imagenesCamionSubidas.includes(hash)) {
                    reject("⚠️ Imagen duplicada. Sube una foto diferente.");
                } else {
                    imagenesCamionSubidas.push(hash);
                    resolve("✅ Imagen aceptada y validada.");
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
                    if(inputCamion) inputCamion.value = "";
                    imagenDuplicadaCamion = true;
                    
                    // Mostrar Modal de Aviso si existe
                    if (modalAviso) {
                        modalAviso.style.display = "block";
                    } else if(!confirm(err + "\n¿Deseas usarla de todas formas?")) {
                        // Fallback si no hay modal
                        inputCamion.value = "";
                    } else {
                         // Si acepta en el confirm
                         imagenDuplicadaCamion = false;
                    }
                });
        } catch (error) {
            mostrarMensaje("Error procesando imagen: " + error.message, "error");
        }
    }

    if (inputCamion) {
        inputCamion.addEventListener("change", function (event) {
            const archivo = event.target.files[0];
            if (archivo) procesarArchivo(archivo);
        });
    }

    // Lógica del Modal de Aviso
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
            mostrarMensaje("⚠️ Imagen aceptada bajo responsabilidad del usuario.", "ok");
        };
    }


    // ==========================================================================
    // 5. ENVÍO DEL FORMULARIO
    // ==========================================================================

    if (form) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            if (imagenDuplicadaCamion) {
                alert("🚫 No se puede generar la solicitud. La imagen no es válida.");
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

    

}); // <--- FIN DEL ARCHIVO (Asegúrate que esta línea esté presente)