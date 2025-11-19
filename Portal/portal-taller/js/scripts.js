/*
 * Portal/portal-taller/js/taller.js
 * Lógica específica para la Recepción de Taller.
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================================
    // 1. REFERENCIAS DOM
    // ==========================================================================
    
    const modal = document.getElementById('modal-registro');
    const btnAbrirModal = document.getElementById('btn-registrar-entrada');
    const btnCerrarModal = document.getElementById('cerrar-modal');
    const form = document.getElementById('form-registro');
    
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

    if (btnAbrirModal && modal) {
        btnAbrirModal.addEventListener('click', (e) => {
            e.preventDefault();
            modal.style.display = 'block'; // Mostramos el modal
            
            // IMPORTANTE: NO abrimos modalAviso aquí. 
            // Ese solo debe abrirse si la foto falla la validación EXIF.
        });

        btnCerrarModal.addEventListener('click', () => {
            cerrarModal();
        });

        window.addEventListener('click', (e) => {
            if (e.target == modal) cerrarModal();
        });
    }

    function cerrarModal() {
        modal.style.display = 'none';
        form.reset();
        mensajeCamion.innerHTML = "";
        imagenDuplicadaCamion = false;
        if(modalAviso) modalAviso.style.display = 'none';
    }


    // ==========================================================================
    // 3. VALIDACIÓN DE IMÁGENES (EXIF)
    // ==========================================================================

    function mostrarMensaje(texto, tipo) {
        mensajeCamion.innerHTML = '';
        const div = document.createElement("div");
        div.textContent = texto;
        div.className = `alerta ${tipo}`; // Asegúrate de tener CSS para .alerta.ok y .alerta.error
        mensajeCamion.appendChild(div);
    }

    function analizarMetadatos(blob, archivoOriginal) {
        return new Promise((resolve, reject) => {
            // Usamos la librería EXIF.js que ya importaste en el HTML
            EXIF.getData(blob, function () {
                const allMetaData = EXIF.getAllTags(this);
                
                // Si no hay metadatos (típico de WhatsApp), rechazamos
                if (Object.keys(allMetaData).length === 0) {
                    reject("⚠️ La imagen parece venir de WhatsApp (sin metadatos).");
                    return;
                }

                // Validación de duplicados (Hash simple: Fecha + Modelo + Tamaño)
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
        mensajeCamion.innerHTML = "Analizando imagen...";
        imagenDuplicadaCamion = false;

        if (!archivo || !archivo.type.startsWith("image/")) {
            mostrarMensaje("El archivo no es una imagen válida.", "error");
            imagenDuplicadaCamion = true;
            return;
        }

        // Validación HEIC (si usas la librería heic2any)
        if (archivo.name.toLowerCase().endsWith(".heic")) {
            mostrarMensaje("Formato HEIC detectado. Convirtiendo...", "ok");
            // Aquí iría la lógica de conversión si la necesitas
        }

        try {
            await analizarMetadatos(archivo, archivo)
                .then(msg => {
                    mostrarMensaje(msg, "ok");
                    imagenDuplicadaCamion = false;
                })
                .catch(err => {
                    // Si falla la validación, mostramos el mensaje y abrimos el modal de aviso
                    mostrarMensaje(err, "error");
                    imagenDuplicadaCamion = true; // Marcamos como inválida inicialmente
                    
                    if (modalAviso) {
                        modalAviso.style.display = "block";
                        
                        // Lógica del Modal de Aviso
                        cancelarBtn.onclick = () => {
                            modalAviso.style.display = "none";
                            inputCamion.value = ""; // Borramos el input
                            mostrarMensaje("Subida cancelada.", "error");
                        };
                        cerrarAviso.onclick = cancelarBtn.onclick;
                        
                        continuarBtn.onclick = (e) => {
                            e.preventDefault(); // Evita submit si el botón está dentro del form
                            modalAviso.style.display = "none";
                            imagenDuplicadaCamion = false; // El usuario aceptó el riesgo
                            mostrarMensaje("⚠️ Imagen aceptada bajo responsabilidad del usuario.", "ok");
                        };
                    }
                });
        } catch (error) {
            mostrarMensaje("Error: " + error.message, "error");
        }
    }

    // Listener del Input File
    if (inputCamion) {
        inputCamion.addEventListener("change", function (event) {
            const archivo = event.target.files[0];
            if (archivo) procesarArchivo(archivo);
        });
    }


    // ==========================================================================
    // 4. ENVÍO DEL FORMULARIO
    // ==========================================================================

    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (imagenDuplicadaCamion) {
                alert("🚫 No se puede generar la solicitud. La imagen no es válida.");
                return;
            }

            // Aquí simulamos el envío (luego conectaremos al PHP real)
            const ticketId = 'TK-' + Date.now();
            alert(`Solicitud generada con éxito.\n\nNúmero de Ticket: ${ticketId}`);
            cerrarModal();
        });
    }

    // ==========================================================================
    // 5. EXTRAS DE UI (KPIs)
    // ==========================================================================
    
    const kpiCards = document.querySelectorAll('.kpi-card');
    kpiCards.forEach(card => {
        card.addEventListener('click', () => {
            const lista = card.querySelector('.lista-kpi');
            if (!lista) return;

            if (card.classList.contains('activo')) {
                card.classList.remove('activo');
                lista.style.display = 'none';
            } else {
                kpiCards.forEach(c => {
                    c.classList.remove('activo');
                    const l = c.querySelector('.lista-kpi');
                    if (l) l.style.display = 'none';
                });
                card.classList.add('activo');
                lista.style.display = 'block';
            }
        });
    });

});