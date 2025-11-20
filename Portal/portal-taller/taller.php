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
                <thead><tr><th>Folio</th><th>Unidad</th><th>Fecha</th><th>Estatus</th></tr></thead>                    
                <tbody><tr><td colspan="4" style="text-align:center">Cargando...</td></tr></tbody>
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
                    <input type="hidden" id="id_camion_seleccionado" name="id_camion">
                </div>

                <div class="form-row">
                    <div class="campo-form">
                        <label>Placas:</label>
                        <input type="text" id="info-placas" readonly class="campo-bloqueado">
                    </div>
                    <div class="campo-form">
                        <label>Conductor Asignado:</label>
                        <input type="text" id="info-conductor-asignado" readonly class="campo-bloqueado">
                        <input type="hidden" id="id_conductor_asignado_hidden" name="id_conductor_asignado">
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

                <div class="campo-form">
                    <label>¿Quién Entrega? (Buscar Conductor):</label>
                    <input type="text" id="input-conductor-entrega" placeholder="Buscar conductor..." autocomplete="off">
                    <div id="sugerencias-chofer-entrega" class="sugerencias-lista"></div>
                    <input type="hidden" id="id_conductor_entrega" name="id_conductor_entrega">
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
                    <textarea id="obs-recepcion" name="observaciones_recepcion" rows="2" placeholder="Daños visibles, nivel combustible, etc."></textarea>
                </div>

                <div class="campo-form">
                    <label>Evidencia Fotográfica (Entrada):</label>
                    <input type="file" id="foto-entrada" name="foto_entrada" accept="image/*" required>
                </div>

                <div class="acciones-form">
                    <button type="submit" class="btn-primario">Registrar Entrada</button>
                </div>

            </form>
        </div>
    </div>



    
       <!-- Modal de advertencia por imagen de WhatsApp 
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
        </div> -->
    

<script src="js/scripts.js"></script>
<script src="js/taller.js"></script>
</body>
</html>

