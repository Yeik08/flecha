<?php
session_start(); // Inicia la sesión

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 6) {
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
    <title>Portal almacen</title>
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
					<?php echo htmlspecialchars($nombre_usuario); ?>
         
					<a href="../../php/logout.php"><button type="button">Cerrar sesión</button></a>
			
				</div>

			</div>
	</div>

<!--	---------------------------------------------- BODY ----------------------------------------------	 -->

        <main class="modulo-contenido">
            <div class="modulo-titulo">
                <h1>Control de Intercambio de Filtros</h1>
            </div>

            <div class="tarjetas-kpi">
                
                <div class="kpi-card">
                    <h3>Total Filtros</h3>
                    <p>1,245</p>
                </div>
                <div class="kpi-card">
                    <h3>Almacén Poniente</h3>
                    <p>650</p>
                </div>
                <div class="kpi-card">
                    <h3>Almacén Magdalena</h3>
                    <p>480</p>
                </div>
                <div class="kpi-card">
                    <h3>Total Aceite</h3>
                    <p>1,245</p>
                </div>
                <div class="kpi-card">
                    <h3>Almacén Poniente</h3>
                    <p>650</p>
                </div>
                <div class="kpi-card">
                    <h3>Almacén Magdalena</h3>
                    <p>480</p>
                </div>
                
                
            </div>
        </div>
    </div>
            <div class="form-container">
                <h2>Salida de Material (Intercambio)</h2>
                <form id="form-intercambio">
                    <input type="hidden" id="id_entrada_hidden" name="id_entrada">
                    <input type="hidden" id="id_camion_hidden" name="id_camion">

<fieldset>
                    <legend>1. Validación de Ticket</legend>
                        <div class="campo-form">
                            <label>Número de Ticket:</label>
                            <div class="input-con-boton">
                                <input type="text" id="ticket-id" placeholder="Ej: ENT-251208-XXXX" required>
                                <button type="button" id="btn-buscar-ticket">🔍 Buscar</button>
                            </div>
                        </div>
                        <div class="columnas-dos">
                            <div class="campo-form">
                                <label>Unidad:</label>
                                <input type="text" id="info-unidad" readonly style="background:#eee;">
                            </div>
                            <div class="campo-form">
                                <label>Mecánico Responsable:</label>
                                <input type="text" id="info-mecanico" readonly style="background:#eee;">
                            </div>
                        </div>
                        
                        <input type="hidden" id="secret-filtro-aceite">
                        <input type="hidden" id="secret-filtro-centrifugo">
                        <input type="hidden" id="tipo-servicio-hidden">
                    </fieldset>

                    <fieldset>
                        <legend>2. Retorno de Material (Validación Ciega)</legend>
                        <p style="font-size: 0.9em; color: #666; margin-bottom: 15px;">
                            ℹ️ Escanea los filtros sucios que entrega el mecánico para validar que corresponden a esta unidad.
                        </p>
                        
                        <div class="columnas-dos">
                            <div class="campo-form">
                                <label>Escanear Filtro Aceite (USADO):</label>
                                <input type="text" name="filtro_viejo_serie" placeholder="Escanea la serie de la pieza física..." required autocomplete="off">

                            </div>
                            
                            <div class="campo-form">
                                <label>Escanear Filtro Centrífugo (USADO):</label>
                                <input type="text" name="filtro_viejo_centrifugo_serie" placeholder="Escanea la serie de la pieza física..." autocomplete="off">
                           
                           
                           
                            </div>

                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>3. Entrega de Material Nuevo</legend>
                         <div id="alerta-servicio" style="margin-bottom:15px; padding:10px; border-radius:5px; font-weight:bold; text-align:center;"></div>
                        
                        
                        <div class="columnas-dos">
                            <div class="campo-form">
                                <label>Filtro Aceite Nuevo: <span style="color:red">*</span></label>
                                <input type="text" name="filtro_nuevo_serie" placeholder="Escanear..." required>
                            </div>

                            <div class="campo-form" id="container-centrifugo-nuevo">
                                <label>Filtro Centrífugo Nuevo: <span style="color:red">*</span></label>
                                <input type="text" name="filtro_nuevo_centrifugo" placeholder="Escanear...">
                            </div>
                        </div>
                        <div class="columnas-dos">
                            <div class="campo-form">
                                <label>Cubeta Aceite 1: <span style="color:red">*</span></label>
                                <input type="text" name="cubeta_1" placeholder="Escanear..." required>
                            </div>
                            <div class="campo-form">
                                <label>Cubeta Aceite 2: <span style="color:red">*</span></label>
                                <input type="text" name="cubeta_2" placeholder="Escanear..." required>
                            </div>
                        </div>
                    </fieldset>



                    <fieldset>
                        <legend>📸 Evidencia Fotográfica</legend>
                        
                        <div class="campo-form-evidencia">
                            <label>1. Filtros Retirados (Viejos):</label>
                            <input type="file" name="foto_viejos" accept="image/*" required>
                            <small style="color:#666;">Muestra los números de serie sucios.</small>
                        </div>

                        <div class="campo-form-evidencia">
                            <label>2. Filtros Nuevos Instalados:</label>
                            <input type="file" name="foto_nuevos" accept="image/*" required>
                        </div>

                        <div class="campo-form-evidencia">
                            <label>3. Cubetas de Aceite Usadas:</label>
                            <input type="file" name="foto_cubetas" accept="image/*" required>
                            <small style="color:#666;">Foto clara de las etiquetas de las cubetas.</small>
                        </div>

                        <div class="campo-form-evidencia">
                            <label>4. Foto General (Terminado):</label>
                            <input type="file" name="foto_general" accept="image/*" required>
                        </div>
                    </fieldset>




                    <div class="acciones-form">
                        <button type="submit" class="btn-primario">Confirmar Entrega de Material</button>
                    </div>
                </form>
            </div>
        </main>

<script src="js/scripts.js"></script>
</body>
</html>