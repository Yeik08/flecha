// 1. Esperar a que todo el contenido del DOM esté cargado.
document.addEventListener('DOMContentLoaded', function() {

    // 2. Obtener referencias a los elementos del DOM.
    const modal = document.getElementById('modal-registro');
    const btnAbrirModal = document.getElementById('btn-registrar-entrada');
    const btnCerrarModal = document.getElementById('cerrar-modal');
    const form = document.getElementById('form-registro');
    const inputCamion = document.getElementById("foto-camion");
    const mensajeCamion = document.getElementById("mensaje-foto-camion");

    const imagenesCamionSubidas = [];
    let imagenDuplicadaCamion = false;

    // --- FUNCIONES DEL MODAL ---
    function abrirModal() {
        modal.style.display = 'block';
    }

    function cerrarModal() {
        modal.style.display = 'none';
        form.reset();
        mensajeCamion.innerHTML = "";
    }

    // --- ASIGNACIÓN DE EVENTOS ---
    btnAbrirModal.addEventListener('click', abrirModal);
    btnCerrarModal.addEventListener('click', cerrarModal);
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            cerrarModal();
        }
    });

    inputCamion.addEventListener("change", function (event) {
        mensajeCamion.innerHTML = "";
        const archivo = event.target.files[0];
        imagenDuplicadaCamion = false;

        if (!archivo || !archivo.type.startsWith("image/")) return;

        // Leemos la imagen para obtener sus metadatos.
        EXIF.getData(archivo, function () {
            // --- ¡CÓDIGO MEJORADO! ---
            // Obtenemos TODOS los metadatos disponibles en un objeto.
            const allMetaData = EXIF.getAllTags(this);
            
            let metadatosFormateados = "Metadatos Encontrados en la Imagen:\n";
            metadatosFormateados += "--------------------------------------\n";

            // Verificamos si el objeto de metadatos está vacío.
            if (Object.keys(allMetaData).length === 0) {
                metadatosFormateados += "⚠️ No se encontraron metadatos EXIF en esta imagen.\n(Probablemente fue procesada por una red social como WhatsApp).";
            } else {
                // Si hay datos, los recorremos y los añadimos al string.
                for (let tag in allMetaData) {
                    // Ignoramos algunas etiquetas que no son útiles para el usuario.
                    if (tag !== "MakerNote" && tag !== "UserComment") {
                         metadatosFormateados += `▶ ${tag}: ${allMetaData[tag]}\n`;
                    }
                }
            }
            
            // Mostramos la alerta con toda la información.
            alert(metadatosFormateados);

            // Lógica de validación de imagen duplicada (sin cambios)
            const fecha = allMetaData.DateTimeOriginal || allMetaData.DateTime || "sin-fecha";
            const modelo = allMetaData.Model || "modelo-desconocido";
            const hash = `${fecha}-${modelo}-${archivo.size}`;

            if (imagenesCamionSubidas.includes(hash)) {
                imagenDuplicadaCamion = true;
                mostrarMensaje("⚠️ Imagen duplicada. Sube una foto diferente.", "error");
                inputCamion.value = "";
            } else {
                imagenesCamionSubidas.push(hash);
                mostrarMensaje("✅ Imagen aceptada y validada.", "ok");
            }
        });
    });

    function mostrarMensaje(texto, tipo) {
        mensajeCamion.innerHTML = '';
        const div = document.createElement("div");
        div.textContent = texto;
        div.className = `alerta ${tipo}`;
        mensajeCamion.appendChild(div);
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (imagenDuplicadaCamion) {
            alert("🚫 No se puede generar la solicitud. La imagen ya fue usada.");
            return;
        }
        const ticketId = 'TK-' + Date.now() + Math.floor(Math.random() * 100);
        alert(`Solicitud generada con éxito.\n\nNúmero de Ticket: ${ticketId}`);
        cerrarModal();
    });
});