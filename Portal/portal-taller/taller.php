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


        <!-- exif-js para metadatos de imágenes JPEG y PNG -->
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>

    <!-- heic2any para convertir imágenes HEIC a JPEG -->
    <script src="https://cdn.jsdelivr.net/npm/heic2any"></script>
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
            <h2>Estado de la Flota en Taller</h2>
            <button class="btn-primario" id="btn-registrar-entrada">+ Registrar Entrada de Camión</button>
        </div>
   
            <div class="tabla-contenido">
                <table>
                    <thead>
                        <tr>
                            <th>Unidad (ID)</th>
                            <th>Placas</th>
                            <th>Último Mantenimiento</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ECO-112</td>
                            <td>A123BCD</td>
                            <td>15/09/2025</td>
                            <td class="estatus-operativo">Operativo</td>
                            <td class="acciones"><button class="btn-ver">Ver Historial</button></td>
                        </tr>
                        <tr class="stock-bajo"> <td>ECO-080</td>
                            <td>B456EFG</td>
                            <td>20/08/2025</td>
                            <td class="estatus-taller">En Taller</td>
                            <td class="acciones"><button class="btn-ver">Ver Historial</button></td>
                        </tr>
                        <tr>
                            <td>ECO-201</td>
                            <td>C789HIJ</td>
                            <td>01/09/2025</td>
                            <td class="estatus-operativo">Operativo</td>
                            <td class="acciones"><button class="btn-ver">Ver Historial</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
    </main>

    <div id="modal-registro" class="modal">
        <div class="modal-contenido">
            <span class="cerrar-modal" id="cerrar-modal">&times;</span>
            <h2>Registrar Entrada de Camión a Taller</h2>
            <form id="form-registro">
                <div class="campo-form">
                        <label for="id_unidad">Buscar Unidad:</label>
                        <input type="text" id="input-buscar-camion" placeholder="Escribe ECO o Placas..." autocomplete="off">
                        <input type="hidden" id="id_camion_seleccionado" name="id_camion_seleccionado">
                        <div id="sugerencias-camion" class="sugerencias-lista"></div>
                </div>

                <div class="form-row">
                    <div class="campo-form">
                        <label>Placas:</label>
                        <input type="text" id="info-placas" readonly style="background:#eee;">
                    </div>
                    <div class="campo-form">
                        <label>Próximo Mantenimiento:</label>
                        <input type="text" id="info-proximo-mto" readonly style="background:#eee;">
                    </div>
                </div>

                <div id="alerta-mto" class="alerta error oculto" style="margin-bottom: 15px;">
                        ⚠️ Esta unidad requiere mantenimiento preventivo urgente.
                </div>
                                
                <div class="campo-form">
                        <label>Conductor Asignado:</label>
                        <input type="text" id="info-conductor-asignado" readonly style="background:#eee;">
                        <input type="hidden" id="id_conductor_asignado_hidden" name="id_conductor_asignado_hidden">
                </div>



                <div class="campo-form">
                    <label for="fecha-hora">Fecha y Hora de Entrada:</label>
                    <input type="datetime-local" id="fecha-hora" name="fecha-hora" required>
                </div>
                
                <div class="campo-form">
                    <label for="tipo-mantenimiento">Tipo de Mantenimiento:</label>
                    <select id="tipo-mantenimiento" name="tipo-mantenimiento" required>
                        <option value="">Selecciona Mantenimiento</option>
                        <option value="cambio_filtro">Cambio de aceite y filtros</option>
              <!--          <option value="revision_frenos">Revisión de Frenos</option>
                        <option value="electrico">Sistema Eléctrico</option>-->
                    </select>
                </div>
                <div class="campo-form">
                   
                <label for="id_unidad">ID mecánico a cargo:</label>
                    <input list="mecánico" id="id_mecánico" name="id_mecánico" placeholder="Buscar mecánico..">
                    <datalist id="mecánico">
                    <option value="MEC 01 LUIS ROBLE">
                    <option value="MEC 02 JUAN PEREZ">
                    <option value="MEC 03 CARLOS SANCHEZ">
                    <option value="MEC 04 ANTONIO LOPEZ">
                    </datalist>
                </div>

                <div class="campo-form">
    <label for="id_conductor">ID CONDUCTOR DE LA UNIDAD:</label>
                    <input list="conductor" id="id_conductor" name="id_conductor" placeholder="Buscar conductor..">
                    <datalist id="conductor">
                    <option value="DRV 01 LUIS ROBLE">
                    <option value="DRV 02 JUAN PEREZ">
                    <option value="DRV 03 CARLOS SANCHEZ">
                    <option value="DRV 04 ANTONIO LOPEZ">
                    </datalist>
                </div>

                <div class="campo-form">
                    <label for="foto-camion">Foto de Evidencia (Entrada):</label>
                    <input type="file" id="foto-camion" name="foto-camion" accept="image/*" required>
                    <div id="mensaje-foto-camion"></div> <!-- Aquí se mostrarán los mensajes -->
                </div>
                <div class="acciones-form">
                    <button type="submit" class="btn-primario">Generar Solicitud</button>
                </div>
            </form>
        </div>
    </div>
       <!-- Modal de advertencia por imagen de WhatsApp -->
        <div id="modal-aviso" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrar-aviso">&times;</span>
                <h2>⚠️ Aviso Importante sobre Imágenes de WhatsApp ⚠️</h2>
                <p>
                    Si la imagen fue descargada desde WhatsApp, es posible que no sea valida (Solo se admiten imágenes en formato <strong>JPG</strong> o <strong>PNG</strong>).
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
    </div>

<script src="js/scripts.js"></script>
</body>
</html>

