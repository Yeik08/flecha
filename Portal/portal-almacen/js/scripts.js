document.addEventListener('DOMContentLoaded', function() {

    const btnBuscar = document.getElementById('btn-buscar-ticket');
    const formIntercambio = document.getElementById('form-intercambio');
    const inputTicket = document.getElementById('ticket-id');

    // BUSCAR TICKET
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
                    
                    // Guardamos en oculto para validación ciega
                    document.getElementById('secret-filtro-aceite').value = d.serie_filtro_aceite_actual || '';
                    document.getElementById('secret-filtro-centrifugo').value = d.serie_filtro_centrifugo_actual || '';
                    
                    alert("✅ Ticket validado. Procede a escanear las piezas.");
                } else {
                    alert("❌ " + data.message);
                    formIntercambio.reset();
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
    // VALIDACIÓN ESTRICTA DE FOTOS (Igual que Mecánico)
    // =========================================================
    function validarInputFoto(inputElement) {
        inputElement.addEventListener('change', async function(e) {
            const archivo = e.target.files[0];
            if (!archivo) return;

            const parentDiv = inputElement.parentElement;
            
            // Crear o limpiar caja de mensaje
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
                    
                    // VALIDACIÓN: ¿Tiene fecha original?
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

    // Aplicar a todos los inputs de archivo
    const inputsFotos = document.querySelectorAll('#form-intercambio input[type="file"]');
    inputsFotos.forEach(input => validarInputFoto(input));


    // =========================================================
    // ENVÍO DEL FORMULARIO
    // =========================================================
    if(formIntercambio) {
        formIntercambio.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // VALIDACIÓN CIEGA (FRONTEND)
            const esperadoAceite = document.getElementById('secret-filtro-aceite').value.trim().toUpperCase();
            const escaneadoAceite = formIntercambio.querySelector('input[name="filtro_viejo_serie"]').value.trim().toUpperCase();
            
            if (esperadoAceite && esperadoAceite !== escaneadoAceite) {
                alert("⛔ ERROR DE SEGURIDAD (ACEITE)\n\nEl filtro escaneado NO PERTENECE a este camión.\n\nEscaneado: " + escaneadoAceite + "\n\nVerifica la pieza física.");
                return;
            }

            const esperadoCent = document.getElementById('secret-filtro-centrifugo').value.trim().toUpperCase();
            const inputCent = formIntercambio.querySelector('input[name="filtro_viejo_centrifugo_serie"]');
            const escaneadoCent = inputCent ? inputCent.value.trim().toUpperCase() : '';

            if (esperadoCent && escaneadoCent && esperadoCent !== escaneadoCent) {
                alert("⛔ ERROR DE SEGURIDAD (CENTRÍFUGO)\n\nEl filtro escaneado NO PERTENECE a este camión.\n\nEscaneado: " + escaneadoCent);
                return;
            }

            // Validar fotos vacías (si el validador las borró)
            let fotosValidas = true;
            inputsFotos.forEach(inp => { if(!inp.value) fotosValidas = false; });
            if(!fotosValidas) {
                alert("⚠️ Faltan evidencias válidas. Revisa los mensajes en rojo.");
                return;
            }

            if(!confirm("✅ Validación correcta. ¿Confirmas la entrega y guardado de evidencias?")) return;

            // Preparar UI
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
                    // Limpiar mensajes de fotos
                    document.querySelectorAll('.mensaje-validacion').forEach(el => el.innerHTML = '');
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