<?php
session_start(); // Inicia la sesión

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 5) {
header("Location: ../index.html?error=acceso_denegado");
exit;
}

$nombre_usuario = $_SESSION['nombre_completo']; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taller - Recepción</title>
	<link rel="shortcut icon" href="../img/bus_8502475.png">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .alerta-visual { padding: 10px; border-radius: 5px; margin-bottom: 10px; display: none; font-size: 0.9em; }
        .alerta-amarilla { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alerta-roja { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .campo-bloqueado { background-color: #e9ecef; cursor: not-allowed; }
        .sugerencias-lista { position: absolute; background: white; border: 1px solid #ddd; width: 90%; z-index: 100; max-height: 150px; overflow-y: auto; }
        .sugerencias-lista div { padding: 8px; cursor: pointer; }
        .sugerencias-lista div:hover { background-color: #f1f1f1; }
    </style>


        <!-- exif-js para metadatos de imágenes JPEG y PNG -->
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>

</head>

<body>
    <!-- Contenedor principal -->
    <div class="contenedor-principal">
		
		<!-- Menú superior -->
		<div class="menu-superior">
			<!-- Opciones del menú -->
			<div class="opciones">
				<div class="fr">FLECHA ROJA</div>
            </div>
                    
    		<div class="perfil">

				<a href="../index.html">
					<img src="../img/cinta_principal2.png" class="img-perfil">
				</a>
					
                <?php echo htmlspecialchars($nombre_usuario); ?>


				<a href="../../php/logout.php"><button type="button">Cerrar sesión</button></a>
			
			</div>
                
        </div>
	</div>


           <!--	---------------------------------------------- BODY ----------------------------------------------	 -->
    <main class="modulo-contenido">
        
        <div class="tabla-titulo">
            <h2>Bitácora de Entradas</h2>
            <button class="btn-primario" id="btn-registrar-entrada">+ Registrar Entrada de Camión</button>
        </div>
        
        <div class="tabla-contenido">
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Unidad</th>
                        <th>Tipo Servicio</th> <th>Fecha Ingreso</th>
                        <th>Alertas</th>       <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>                    
                <tbody id="tabla-entradas-body">
                    <tr><td colspan="7" style="text-align:center">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

    </main>



    <div id="modal-recepcion" class="modal oculto">
        <div class="modal-contenido">
            <span class="cerrar-modal" id="cerrar-modal">&times;</span>
            <h2>Recepción de Unidad</h2>

            <form id="form-recepcion">

                <div class="campo-form" style="position:relative;">
                    <label>Buscar Unidad:</label>
                    <input type="text" id="input-buscar-camion" placeholder="Escribe ECO o Placas..." autocomplete="off">
                    <div id="sugerencias-camion" class="sugerencias-lista"></div>
                    <input type="hidden" id="id_camion_seleccionado" name="id_camion_seleccionado">
                </div>

                <div class="form-row">
                    <div class="campo-form">
                        <label>Placas:</label>
                        <input type="text" id="info-placas" readonly class="campo-bloqueado">
                    </div>
                    <div class="campo-form">
                        <label>Conductor Asignado:</label>
                        <input type="text" id="info-conductor-asignado" readonly class="campo-bloqueado">
                        <input type="hidden" id="id_conductor_asignado_hidden" name="id_conductor_asignado_hidden">
                    </div>
                </div>
                
                <div id="alerta-conductor" class="alerta-visual alerta-amarilla">
                    ⚠️ <strong>Advertencia:</strong> El conductor que entrega es diferente al asignado.
                </div>

                <div class="form-row">
                    <div class="campo-form">
                        <label>Fecha/Hora Entrada:</label>
                        <input type="datetime-local" id="fecha-entrada" name="fecha_ingreso" required>
                    </div>
                    <div class="campo-form">
                        <label>Kilometraje:</label>
                        <input type="number" name="kilometraje_entrada" step="0.1" required>
                    </div>
                </div>


                <div class="form-row">
                    <div class="campo-form">
                        <label>Taller de Recepción:</label>
                        <select name="id_taller" required>
                            <option value="1">Magdalena</option>
                            <option value="2">Poniente</option>
                        </select>
                    </div>
                    <div class="campo-form">
                        <label>Nivel de Combustible:</label>
                        <select name="nivel_combustible" required>
                            <option value="">Selecciona nivel...</option>
                            <option value="Reserva">Reserva</option>
                            <option value="1/4">1/4</option>
                            <option value="1/2">1/2</option>
                            <option value="3/4">3/4</option>
                            <option value="Lleno">Lleno</option>
                        </select>
                    </div>
                </div>

                <div class="campo-form">
                    <label>¿Quién Entrega? (Buscar Conductor):</label>
                    <input type="text" id="input-conductor-entrega" placeholder="Buscar conductor..." autocomplete="off" required>
                    <div id="sugerencias-chofer-entrega" class="sugerencias-lista"></div>
                    <input type="hidden" id="id_conductor_entrega" name="id_conductor_entrega" required>
                </div>

                <div class="campo-form">
                    <label>Tipo de Servicio:</label>
                    <select id="tipo-servicio" name="tipo_mantenimiento" required>
                        <option value="">Selecciona...</option>
                        <option value="Mantenimiento Preventivo (Aceite/Filtros)">Mantenimiento Preventivo</option>
                        <option value="Correctivo">Correctivo (Falla)</option>
                        <option value="Llantas">Llantas</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>    

                <div id="alerta-tiempo" class="alerta-visual alerta-roja"></div>


                <div class="campo-form">
                    <label>Observaciones de Recepción:</label>
                    <textarea id="obs-recepcion" name="observaciones_recepcion" rows="2" placeholder="Daños visibles, nivel combustible, etc." required></textarea>
                </div>

                <div class="campo-form">
                    <label>Evidencia Fotográfica (Entrada):</label>
                    <input type="file" id="foto-entrada" name="foto_entrada" accept="image/*" required>
                </div>
                
                <input type="hidden" id="meta_fecha_captura" name="meta_fecha_captura">
                <input type="hidden" id="meta_datos_json" name="meta_datos_json">
                
                <div class="acciones-form">
                    <button type="submit" class="btn-primario">Registrar Entrada</button>
                </div>

            </form>
        </div>
    </div>



    
       <!-- Modal de advertencia por imagen de WhatsApp -->
        <div id="modal-aviso" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrar-aviso">&times;</span>
                <h2>⚠️Aviso Importante sobre Imágenes de WhatsApp⚠️</h2>
                <p>
                    Si la imagen fue descargada desde WhatsApp, es posible que no sea valida(Solo se admiten imágenes en formato <strong>JPG</strong> o <strong>PNG</strong>).
                </p>
                <p>
                    <strong>Para evitar esto, pide que te la envien de la siguiente manera desde WhatsApp:</strong>
                </p>
                <ol>
                    <li>Presiona el <strong>Clip</strong> para insertar un archivo.</li>
                    <li>Selecciona <strong>Documento</strong> en lugar de la opción de Galería o Fotos.</li>
                    <li>Busca la carpeta donde tienes almacenada la imagen.</li>
                    <li>Selecciona la imagen que quieres enviar (esto la enviará como archivo).</li>
                </ol>
                <p>
                    Si la imagen fue tomada con el mismo dispositivo usado, puedes subir la imagen directamente desde la galería sin pasar por WhatsApp.
                </p>
                <div class="acciones-form">
                    <button id="continuar-subida" class="btn-primario">Continuar</button>
                    <button id="cancelar-subida" class="btn-secundario">Cancelar</button>
                </div>
            </div>
        </div> 
    
<div id="modal-detalle" class="modal oculto">
        <div class="modal-contenido" style="max-width: 800px; min-height: 800px;">
            <span class="cerrar-modal" id="cerrar-modal-detalle">&times;</span>
            
            <div class="modal-header">
                <h2>Detalle de Entrada <span id="ver-folio" style="color:#316960;"></span></h2>
            </div>

            <div class="tabs-modal">
                <button class="tab-btn active" onclick="cambiarTab(event, 'tab-info')">📋 Información</button>
                <button class="tab-btn" onclick="cambiarTab(event, 'tab-foto')">📸 Evidencia</button>
            </div>

            <div id="tab-info" class="tab-panel active">
                <div class="form-row">
                    <div class="columna-detalle">
                        <h3 style="border-bottom: 2px solid #ddd; padding-bottom: 5px; color:#555;">🚌 Unidad</h3>
                        <p><strong>Económico:</strong> <span id="ver-eco" style="font-size:1.2em;"></span></p>
                        <p><strong>Placas:</strong> <span id="ver-placas"></span></p>
                        <p><strong>Marca/Año:</strong> <span id="ver-marca"></span></p>
                        <p><strong>Combustible:</strong> <span id="ver-gas"></span></p>
                        <p><strong>Kilometraje:</strong> <span id="ver-km" style="font-weight:bold;"></span></p>
                    </div>

                    <div class="columna-detalle">
                        <h3 style="border-bottom: 2px solid #ddd; padding-bottom: 5px; color:#555;">📋 Servicio</h3>
                        <p><strong>Fecha:</strong> <span id="ver-fecha"></span></p>
                        <p><strong>Tipo:</strong> <span id="ver-tipo"></span></p>
                        <p><strong>Conductor:</strong> <span id="ver-chofer"></span></p>
                        <p><strong>Estatus:</strong> <span id="ver-estatus" class="estatus-tag"></span></p>
                    </div>
                </div>
                
                <br>
                <p><strong>Observaciones Registradas:</strong></p>
                <div style="background:#f9f9f9; padding:15px; border-left: 4px solid #316960; border-radius:4px;">
                    <span id="ver-obs" style="font-style:italic;">Sin observaciones.</span>
                </div>
            </div>

            <div id="tab-foto" class="tab-panel">
                <div style="
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    background-color: #000; /* Fondo negro para mejor contraste */
                    border-radius: 8px;
                    padding: 10px;
                    height: 400px; /* Altura fija grande */
                ">
                    <img id="ver-foto" src="" alt="Evidencia" style="
                        max-width: 100%; 
                        max-height: 100%; 
                        object-fit: contain; /* La imagen nunca se recorta */
                        box-shadow: 0 0 15px rgba(255,255,255,0.1);
                    ">
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    <button type="button" class="btn-primario" onclick="window.open(document.getElementById('ver-foto').src, '_blank')">
                        🔍 Abrir imagen en pestaña nueva
                    </button>
                </div>
            </div>
            
            <div class="acciones-form" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                <button type="button" class="btn-secundario" id="btn-cerrar-detalle">Cerrar</button>
            </div>
        </div>
    </div>        
<script src="js/taller.js"></script>
</body>
</html>

