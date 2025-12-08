<?php
session_start(); // Inicia la sesión

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 4) {
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
    <title>Portal Mecanico</title>
	<link rel="shortcut icon" href="../img/bus_8502475.png">
    <link rel="stylesheet" href="css/style.css">
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
            <div class="modulo-titulo">
                <h1>Portal de Mecánico</h1>
            </div>

            <div class="tabla-contenido" style="margin-bottom: 30px;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="color: #316960; margin: 0;">Camiones en Espera de Servicio</h2>
                    
                    <div class="filtro-container" style="display: flex; gap: 15px;">
                        
                        <div>
                            <label for="filtro-estatus" style="font-weight: bold; color: #555;">Estatus:</label>
                            <select id="filtro-estatus" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                                <option value="todos">Todos</option>
                                <option value="Recibido">Por Iniciar</option>
                                <option value="En Proceso">En Trabajo</option>
                            </select>
                        </div>

                        <div>
                            <label for="filtro-taller" style="font-weight: bold; color: #555;">Origen:</label>
                            <select id="filtro-taller" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                                <option value="todos">Todos</option>
                                <option value="Magdalena">Magdalena</option>
                                <option value="Poniente">Poniente</option>
                            </select>
                        </div>

                    </div>





                </div>

                <table class="tabla-moderna"> <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Origen</th> <th>Unidad</th>
                            <th>Servicio Solicitado</th>
                            <th>Ingreso</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-pendientes-body">
                        <tr><td colspan="6" style="text-align:center">Cargando trabajos pendientes...</td></tr>
                    </tbody>
                </table>
            </div>



            <div class="form-container" id="contenedor-servicio" style="display:none;">
                <h2 id="titulo-servicio">Finalizar Mantenimiento</h2>
                
                <form id="form-salida">
                    <input type="hidden" id="id_entrada_hidden" name="id_entrada">
                    <input type="hidden" id="id_camion_hidden" name="id_camion_real">

                    <fieldset>
                        <legend>Datos del Camion</legend>
                        <div class="columnas-dos">
                            <div class="campo-form">
                                <label>Unidad:</label>
                                <input type="text" id="camion-info" readonly style="background:#eee; font-weight:bold;">
                            </div>
                            <div class="campo-form">
                                <label>Folio:</label>
                                <input type="text" id="folio-info" readonly style="background:#eee;">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>🛠️ Cambio de Filtros (Inventario)</legend>
                        <p style="font-size:0.85em; color:#666; margin-bottom:10px;">
                            Verifica la serie instalada y escanea la nueva serie del almacén.
                        </p>

                        <div class="columnas-dos">
                            <div style="background:#f9f9f9; padding:10px; border-radius:5px;">
                                <label style="font-weight:bold;">Filtro de Aceite</label>
                                <div class="campo-form">
                                    <label>Actual (Retirar):</label>
                                    <input type="text" id="filtro-aceite-actual" readonly style="color:#d32f2f;">
                                </div>
                                <div class="campo-form">
                                    <label>Nuevo (Escanear):</label>
                                    <input type="text" name="nuevo_filtro_aceite" placeholder="Escanear serie nueva..." required>
                                </div>
                            </div>

                            <div style="background:#f9f9f9; padding:10px; border-radius:5px;">
                                <label style="font-weight:bold;">Filtro Centrífugo</label>
                                <div class="campo-form">
                                    <label>Actual (Retirar):</label>
                                    <input type="text" id="filtro-centrifugo-actual" readonly style="color:#d32f2f;">
                                </div>
                                <div class="campo-form">
                                    <label>Nuevo (Escanear):</label>
                                    <input type="text" name="nuevo_filtro_centrifugo" placeholder="Escanear serie nueva...">
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>🛢️ Aceite (2 Cubetas - 38L)</legend>
                        <div class="columnas-dos">
                            <div class="campo-form">
                                <label>Cubeta 1:</label>
                                <input type="text" name="serie_cubeta_1" placeholder="Escanear serie..." required>
                            </div>
                            <div class="campo-form">
                                <label>Cubeta 2:</label>
                                <input type="text" name="serie_cubeta_2" placeholder="Escanear serie..." required>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>📸 Evidencia</legend>
                        <div class="campo-form-evidencia">
                            <label>Filtros Retirados:</label>
                            <input type="file" name="foto_viejos" accept="image/*" required>
                        </div>
                        <div class="campo-form-evidencia">
                            <label>Filtros Nuevos Instalados:</label>
                            <input type="file" name="foto_nuevos" accept="image/*" required>
                        </div>
                        <div class="campo-form-evidencia">
                            <label>Foto General:</label>
                            <input type="file" name="foto_general" accept="image/*" required>
                        </div>
                    </fieldset>

                    <div class="campo-form">
                        <label>Comentarios:</label>
                        <textarea name="comentarios" rows="3"></textarea>
                    </div>

                    <div class="acciones-form">
                        <button type="button" class="btn-secundario" onclick="cancelarServicio()">Cancelar</button>
                        <button type="submit" class="btn-primario">Finalizar Trabajo</button>
                    </div>
                </form>
            </div>
        </main>
    <script src="js/script.js"></script>
</body>
</html>