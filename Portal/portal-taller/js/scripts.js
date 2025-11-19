/*
 * Portal/portal-taller/js/taller.js
 * Lógica completa para el Módulo de Recepción de Taller.
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================================
    // 1. REFERENCIAS DOM Y VARIABLES GLOBALES
    // ==========================================================================
    
    // Modal de Recepción
    const modalRecepcion = document.getElementById('modal-recepcion');
    const btnNuevaEntrada = document.getElementById('btn-nueva-entrada');
    const btnsCerrarModal = document.querySelectorAll('.modal-cerrar, .btn-cerrar-modal');
    const formRecepcion = document.getElementById('form-recepcion');
    
    // Modal de Aviso WhatsApp (Imágenes)
    const modalAviso = document.getElementById("modal-aviso"); // Asegúrate de tener este HTML en tu taller.php si usas la validación
    const cerrarAviso = document.getElementById("cerrar-aviso");
    const continuarBtn = document.getElementById("continuar-subida");
    const cancelarBtn = document.getElementById("cancelar-subida");

    // Campos del Formulario
    const inputBuscarCamion = document.getElementById('input-buscar-camion');
    const listaSugerenciasCamion = document.getElementById('sugerencias-camion');
    const inputIdCamion = document.getElementById('id_camion_seleccionado');
    
    const infoConductorAsignado = document.getElementById('info-conductor-asignado');
    const hiddenIdConductorAsignado = document.getElementById('id_conductor_asignado_hidden'); // Asegúrate de agregar este input hidden en tu HTML si no está
    const alertaMto = document.getElementById('alerta-mto');
    const selectTipoServicio = document.querySelector('select[name="tipo_servicio"]');
    
    const inputConductorEntrega = document.getElementById('input-conductor-entrega');
    const listaSugerenciasChofer = document.getElementById('sugerencias-chofer-entrega');
    const inputIdConductorEntrega = document.getElementById('id_conductor_entrega');

    // Manejo de Imágenes
    const inputFotos = document.getElementById("input-fotos");
    // (Opcional) Div para mostrar mensajes de error de imagen
    // const mensajeFotos = document.getElementById("mensaje-fotos"); 
    let imagenValida = true;


    // ==========================================================================
    // 2. LÓGICA DE MODALES (ABRIR / CERRAR)
    // ==========================================================================

    if (modalRecepcion && btnNuevaEntrada) {
        btnNuevaEntrada.addEventListener('click', () => {
            modalRecepcion.classList.remove('oculto');
            // Resetear formulario al abrir
            formRecepcion.reset();
            listaSugerenciasCamion.style.display = 'none';
            if(alertaMto) alertaMto.classList.add('oculto');
        });

        btnsCerrarModal.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                modalRecepcion.classList.add('oculto');
                if (modalAviso) modalAviso.style.display = 'none';
            });
        });

        window.addEventListener('click', (e) => {
            if (e.target === modalRecepcion) modalRecepcion.classList.add('oculto');
        });
    }


    // ==========================================================================
    // 3. AUTOCOMPLETADO: BUSCAR CAMIÓN
    // ==========================================================================

    if (inputBuscarCamion) {
        inputBuscarCamion.addEventListener('input', async function() {
            const query = this.value.trim();
            listaSugerenciasCamion.innerHTML = '';
            
            if (query.length < 2) {
                listaSugerenciasCamion.style.display = 'none';
                return;
            }

            try {
                const res = await fetch(`php/buscar_camion_express.php?q=${query}`);
                const data = await res.json();
                
                if (data.length > 0) {
                    listaSugerenciasCamion.style.display = 'block';
                    
                    data.forEach(camion => {
                        const item = document.createElement('div');
                        item.textContent = `${camion.numero_economico} - ${camion.placas}`;
                        item.style.padding = "8px";
                        item.style.cursor = "pointer";
                        
                        item.addEventListener('click', () => {
                            // 1. Llenar input visual
                            inputBuscarCamion.value = camion.numero_economico;
                            
                            // 2. Llenar datos ocultos
                            inputIdCamion.value = camion.id;
                            
                            // 3. Llenar Conductor Asignado
                            if (camion.nombre_chofer) {
                                infoConductorAsignado.value = `${camion.nombre_chofer} (${camion.id_interno_chofer})`;
                                // Si tienes el campo hidden para validación:
                                if(hiddenIdConductorAsignado) hiddenIdConductorAsignado.value = camion.id_chofer;
                            } else {
                                infoConductorAsignado.value = "Sin asignar";
                                if(hiddenIdConductorAsignado) hiddenIdConductorAsignado.value = "";
                            }

                            // 4. Lógica de Alerta de Mantenimiento
                            if (alertaMto) {
                                alertaMto.classList.add('oculto');
                                if (camion.estado_salud === 'Próximo' || camion.estado_salud === 'Vencido') {
                                    alertaMto.classList.remove('oculto');
                                    // Preseleccionar tipo de servicio si es preventivo
                                    selectTipoServicio.value = "Mantenimiento Preventivo (Aceite/Filtros)";
                                }
                            }

                            listaSugerenciasCamion.style.display = 'none';
                        });
                        
                        // Efecto Hover
                        item.addEventListener('mouseenter', () => { item.style.backgroundColor = "#f1f1f1"; });
                        item.addEventListener('mouseleave', () => { item.style.backgroundColor = "white"; });

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


    // ==========================================================================
    // 4. AUTOCOMPLETADO: BUSCAR CONDUCTOR (QUE ENTREGA)
    // ==========================================================================

    if (inputConductorEntrega) {
        inputConductorEntrega.addEventListener('input', async function() {
            const query = this.value.trim();
            listaSugerenciasChofer.innerHTML = '';
            
            if (query.length < 2) {
                listaSugerenciasChofer.style.display = 'none';
                return;
            }

            try {
                // Reusamos el endpoint de conductores que ya tenías o creamos uno similar
                // Asumiendo que fetch_catalogos.php puede filtrar o trae todos
                // Para optimizar, lo ideal es un endpoint con búsqueda como buscar_camion_express.php
                // Pero usaremos fetch_catalogos.php si trae todo y filtramos aquí (menos eficiente pero funcional para MVP)
                
                const res = await fetch('../portal-camiones/fetch_catalogos.php?tipo=conductores');
                const todosConductores = await res.json();
                
                const filtrados = todosConductores.filter(c => 
                    c.nombre_completo.toLowerCase().includes(query.toLowerCase()) || 
                    c.id_usuario.toLowerCase().includes(query.toLowerCase())
                );

                if (filtrados.length > 0) {
                    listaSugerenciasChofer.style.display = 'block';
                    filtrados.forEach(c => {
                        const item = document.createElement('div');
                        item.textContent = `${c.nombre_completo} (${c.id_usuario})`; // Nombre (ID)
                        item.style.padding = "8px";
                        item.style.cursor = "pointer";

                        item.addEventListener('click', () => {
                            inputConductorEntrega.value = c.nombre_completo;
                            // Necesitamos el ID numérico del conductor que entrega
                            // Tu fetch_catalogos devuelve 'id_usuario' (CON-001), 
                            // pero para comparar necesitamos el ID numérico si es posible, 
                            // o manejamos la lógica en el backend buscando por ID interno.
                            // Para simplificar aquí, enviamos el ID Interno al backend y que él busque.
                            inputIdConductorEntrega.value = c.id_usuario; 
                            listaSugerenciasChofer.style.display = 'none';
                        });
                        
                        item.addEventListener('mouseenter', () => { item.style.backgroundColor = "#f1f1f1"; });
                        item.addEventListener('mouseleave', () => { item.style.backgroundColor = "white"; });

                        listaSugerenciasChofer.appendChild(item);
                    });
                } else {
                    listaSugerenciasChofer.style.display = 'none';
                }

            } catch (error) {
                console.error("Error buscando conductor:", error);
            }
        });
    }

    // Cierra listas al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (listaSugerenciasCamion && !listaSugerenciasCamion.contains(e.target) && e.target !== inputBuscarCamion) {
            listaSugerenciasCamion.style.display = 'none';
        }
        if (listaSugerenciasChofer && !listaSugerenciasChofer.contains(e.target) && e.target !== inputConductorEntrega) {
            listaSugerenciasChofer.style.display = 'none';
        }
    });


    // ==========================================================================
    // 5. VALIDACIÓN DE IMÁGENES (EXIF & WHATSAPP)
    // ==========================================================================

    function analizarMetadatos(blob) {
        return new Promise((resolve, reject) => {
            EXIF.getData(blob, function () {
                const allMetaData = EXIF.getAllTags(this);
                // Validamos si tiene fecha original o modelo de cámara
                // Las fotos de WhatsApp suelen borrar TODO esto.
                if (!allMetaData.DateTimeOriginal && !allMetaData.Model && !allMetaData.Make) {
                    reject("⚠️ La imagen parece venir de WhatsApp (sin metadatos).");
                } else {
                    resolve("✅ Imagen válida.");
                }
            });
        });
    }

    if (inputFotos) {
        inputFotos.addEventListener("change", async function (event) {
            const archivos = event.target.files;
            if (!archivos || archivos.length === 0) return;

            imagenValida = true; // Resetear bandera

            for (let i = 0; i < archivos.length; i++) {
                const archivo = archivos[i];
                
                // Validación de tipo
                if (!archivo.type.startsWith("image/")) {
                    alert("Solo se permiten archivos de imagen.");
                    inputFotos.value = ""; // Limpiar
                    return;
                }

                // Validación EXIF
                try {
                    await analizarMetadatos(archivo);
                } catch (warning) {
                    // Si falla la validación EXIF, mostramos el modal de advertencia
                    // pero NO bloqueamos necesariamente, depende de tu regla de negocio.
                    // Aquí mostramos el modal que diseñaste.
                    if (modalAviso) {
                        modalAviso.style.display = "block";
                        // Si el usuario cancela en el modal, limpiamos el input
                        cancelarBtn.onclick = () => {
                            inputFotos.value = "";
                            modalAviso.style.display = "none";
                            imagenValida = false;
                        };
                        cerrarAviso.onclick = cancelarBtn.onclick;
                        
                        // Si continúa, asumimos el riesgo
                        continuarBtn.onclick = () => {
                            modalAviso.style.display = "none";
                            imagenValida = true; 
                        };
                    } else {
                        // Fallback si no hay modal
                        if(!confirm(warning + "\n¿Deseas usarla de todas formas?")) {
                            inputFotos.value = "";
                            imagenValida = false;
                        }
                    }
                }
            }
        });
    }


    // ==========================================================================
    // 6. ENVÍO DEL FORMULARIO DE RECEPCIÓN
    // ==========================================================================

    if (formRecepcion) {
        formRecepcion.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!imagenValida) {
                alert("Por favor, verifica las imágenes antes de continuar.");
                return;
            }

            // Validación visual de conductor (solo advertencia)
            const asignado = hiddenIdConductorAsignado ? hiddenIdConductorAsignado.value : '';
            // Nota: Para comparar correctamente, necesitarías el ID de empleado de quien entrega.
            // Si solo tienes el nombre o ID interno en el input, la comparación ideal se hace en el backend
            // o buscando el ID numérico en el paso de autocompletado (ya lo hicimos arriba en inputIdConductorEntrega).
            
            // Enviamos
            const formData = new FormData(this);
            
            try {
                const response = await fetch('php/registrar_entrada.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message); // "Entrada registrada correctamente. Folio: ENT-..."
                    modalRecepcion.classList.add('oculto');
                    location.reload(); // Recargar para ver la nueva entrada en la tabla
                } else {
                    alert("Error: " + data.message);
                }
                
            } catch (error) {
                console.error(error);
                alert("Error de conexión al registrar la entrada.");
            }
        });
    }


    // ==========================================================================
    // 7. UI EXTRA: BUSCADOR EN TABLA & KPI
    // ==========================================================================
    
    // Buscador en Tabla de Entradas
    const inputBuscarEntrada = document.getElementById('buscar-entrada');
    const tablaEntradas = document.getElementById('tabla-entradas-body');

    if (inputBuscarEntrada && tablaEntradas) {
        inputBuscarEntrada.addEventListener('keyup', function() {
            const texto = this.value.toLowerCase();
            const filas = tablaEntradas.getElementsByTagName('tr');
            
            for (let i = 0; i < filas.length; i++) {
                const fila = filas[i];
                const contenido = fila.textContent.toLowerCase();
                if (contenido.includes(texto)) {
                    fila.style.display = "";
                } else {
                    fila.style.display = "none";
                }
            }
        });
    }

    // KPI Cards (Desplegables)
    const kpiCards = document.querySelectorAll('.kpi-card');
    kpiCards.forEach(card => {
        card.addEventListener('click', () => {
            const lista = card.querySelector('.lista-kpi');
            if (!lista) return;

            if (card.classList.contains('activo')) {
                card.classList.remove('activo');
                lista.style.display = 'none';
            } else {
                // Cerrar otros
                kpiCards.forEach(c => {
                    c.classList.remove('activo');
                    const l = c.querySelector('.lista-kpi');
                    if (l) l.style.display = 'none';
                });
                // Abrir este
                card.classList.add('activo');
                lista.style.display = 'block';
            }
        });
    });

}); // Fin DOMContentLoaded