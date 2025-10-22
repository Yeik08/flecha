// 1. DOM cargado
document.addEventListener('DOMContentLoaded', function() {

    // 2. Referencias DOM
    const modal = document.getElementById('modal-registro');
    const btnAbrirModal = document.getElementById('btn-registrar-entrada');
    const btnCerrarModal = document.getElementById('cerrar-modal');
    const form = document.getElementById('form-registro');
    const inputCamion = document.getElementById("foto-camion");
    const mensajeCamion = document.getElementById("mensaje-foto-camion");

    const imagenesCamionSubidas = [];
    let imagenDuplicadaCamion = false;

    // 3. Funciones abrir/cerrar modal
    function abrirModal() {
        modal.style.display = 'block';
    }

    function cerrarModal() {
        modal.style.display = 'none';
        form.reset();
        mensajeCamion.innerHTML = "";
        imagenDuplicadaCamion = false;
    }

    // 4. Eventos abrir/cerrar modal
    btnAbrirModal.addEventListener('click', () => {
        abrirModal();
        modalAviso.style.display = "block";
    });
    btnCerrarModal.addEventListener('click', cerrarModal);
    window.addEventListener('click', e => {
        if (e.target == modal) cerrarModal();
        if (e.target == modalAviso) cancelarSubida();
    });

    // 5. Mostrar mensajes
    function mostrarMensaje(texto, tipo) {
        mensajeCamion.innerHTML = '';
        const div = document.createElement("div");
        div.textContent = texto;
        div.className = `alerta ${tipo}`;
        mensajeCamion.appendChild(div);
    }

    // 6. Analizar metadatos EXIF con EXIF.js (devuelve Promise para esperar)
    function analizarMetadatos(blob, archivoOriginal) {
        return new Promise((resolve, reject) => {
            EXIF.getData(blob, function () {
                const allMetaData = EXIF.getAllTags(this);
                if (Object.keys(allMetaData).length === 0) {
                    reject("❌ No se encontraron metadatos EXIF en esta imagen. La imagen no es aceptada.");
                    return;
                }

                let metadatosFormateados = "Metadatos Encontrados:\n-----------------------\n";
                for (let tag in allMetaData) {
                    if (tag !== "MakerNote" && tag !== "UserComment") {
                        metadatosFormateados += `▶ ${tag}: ${allMetaData[tag]}\n`;
                    }
                }
                alert(metadatosFormateados);

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

    // 7. Procesar archivo (async)
    async function procesarArchivo(archivo) {
        mensajeCamion.innerHTML = "";
        imagenDuplicadaCamion = false;

        if (!archivo || !archivo.type.startsWith("image/")) {
            mostrarMensaje("El archivo no es una imagen válida.", "error");
            imagenDuplicadaCamion = true;
            return;
        }

        if (archivo.name.toLowerCase().endsWith(".heic")) {
            mostrarMensaje("❌ El formato HEIC no es válido. Por favor, sube una imagen JPG o PNG.", "error");
            inputCamion.value = "";
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
                    inputCamion.value = "";
                    imagenDuplicadaCamion = true;
                });
        } catch (error) {
            mostrarMensaje("❌ Error procesando la imagen: " + error.message, "error");
            inputCamion.value = "";
            imagenDuplicadaCamion = true;
        }
    }

    // 8. Input cambio archivo
    inputCamion.addEventListener("change", function (event) {
        const archivo = event.target.files[0];
        if (!archivo) return;
        procesarArchivo(archivo);
    });

    // 9. Enviar formulario
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (imagenDuplicadaCamion) {
            alert("🚫 No se puede generar la solicitud. La imagen no es válida o ya fue usada.");
            return;
        }
        const ticketId = 'TK-' + Date.now() + Math.floor(Math.random() * 100);
        alert(`Solicitud generada con éxito.\n\nNúmero de Ticket: ${ticketId}`);
        cerrarModal();
    });

    // 10. Modal aviso WhatsApp
    const modalAviso = document.getElementById("modal-aviso");
    const cerrarAviso = document.getElementById("cerrar-aviso");
    const continuarBtn = document.getElementById("continuar-subida");
    const cancelarBtn = document.getElementById("cancelar-subida");

    cerrarAviso.onclick = cancelarSubida;
    cancelarBtn.onclick = cancelarSubida;

    function cancelarSubida() {
        modalAviso.style.display = "none";
        inputCamion.value = "";
        cerrarModal();
    }

    continuarBtn.onclick = () => {
        modalAviso.style.display = "none";
    };

});

