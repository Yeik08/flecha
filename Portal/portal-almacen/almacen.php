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
                
                <div class="notificaciones-kpi">
                    <h2>Notificaciones</h2>
                        <div class="notificaciones-grid">
                        <div class="notificacion-card alerta">
                    <h3>Stock Bajo</h3>
                    <p>El almacén Poniente tiene menos de 20 filtros disponibles.</p>
                    <span class="fecha">Actualizado: 22/10/2025</span>
                </div>

                <div class="notificacion-card info">
                    <h3>Nueva Solicitud</h3>
                    <p>Se registró una nueva solicitud de intercambio del ticket #3245.</p>
                    <span class="fecha">Hace 15 min</span>
                </div>

                <div class="notificacion-card exito">
                    <h3>Entrega Confirmada</h3>
                    <p>El filtro del camión 45 fue entregado exitosamente.</p>
                    <span class="fecha">Hoy 11:32 AM</span>
                </div>
            </div>
        </div>
    </div>
            <div class="form-container">
                <h2>Registrar Intercambio por Ticket</h2>
                <form id="form-intercambio">

                    <fieldset>
                        <legend>Información de la Solicitud</legend>

                        <div class="campo-form">
                            <label for="ticket-id">Número de Ticket:</label>
                            <div class="input-con-boton">
                            <input type="text" id="ticket-id" name="ticket-id" placeholder="Ingrese el ticket del mecánico" required>
                            <button type="button" id="btn-buscar-ticket">Buscar</button>
                        </div>
                    </div>

                        <div class="campo-form">
                            <label for="camion-id">ID de Camión:</label>
                            <input type="text" id="camion-id" name="camion-id" readonly>
                        </div>

                        <div class="campo-form">
                            <label for="mecanico-id">ID del Mecánico:</label>
                            <input type="text" id="mecanico-id" name="mecanico-id" readonly required>
                        </div>

                        <div class="campo-form">
                            <label for="almacen">Almacén de Salida:</label>
                            <input type="text" id="almacen-id" name="almacen-id" readonly required>   
                        </div>
                    </fieldset>


                    <fieldset>
                        <legend>Detalles del Intercambio</legend>
                        <div class="columnas-dos">
                            <div class="columna">
                                <strong>Filtro Retirado (Viejo)</strong>
                                <div class="campo-form">
                                    <label for="filtro-viejo-serie">Número de Serie:</label>
                                    <input type="text" id="filtro-viejo-serie" name="filtro-viejo-serie" required>
                                </div>
                                <div class="campo-form">
                                    <label for="filtro-viejo-marca">Marca:</label>
                                    <input type="text" id="filtro-viejo-marca" name="filtro-viejo-marca" readonly>
                                </div>
                            </div>
                            <div class="columna">
                                <strong>Filtro Entregado (Nuevo)</strong>
                                <div class="campo-form">
                                    <label for="filtro-nuevo-serie">Número de Serie:</label>
                                    <input type="text" id="filtro-nuevo-serie" name="filtro-nuevo-serie" required>
                                </div>
                                <div class="campo-form">
                                    <label for="filtro-nuevo-marca">Marca:</label>
                                    <input type="text" id="filtro-nuevo-marca" name="filtro-nuevo-marca" readonly>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Evidencia Fotográfica (Obligatoria)</legend>
                        <div class="campo-form-evidencia">
                            <label for="foto-viejo">1. Foto del Filtro Viejo:</label>
                            <input type="file" id="foto-viejo" name="foto-viejo" accept="image/*" required>
                        </div>
                        <div class="campo-form-evidencia">
                            <label for="foto-nuevo">2. Foto del Filtro Nuevo:</label>
                            <input type="file" id="foto-nuevo" name="foto-nuevo" accept="image/*" required>
                        </div>
                        <div class="campo-form-evidencia">
                            <label for="foto-comparacion">3. Foto de Ambos Filtros Juntos:</label>
                            <input type="file" id="foto-comparacion" name="foto-comparacion" accept="image/*" required>
                        </div>
                    </fieldset>

                    <div class="acciones-form">
                        <button type="submit" class="btn-primario">Confirmar y Registrar Salida</button>
                    </div>
                </form>
            </div>
        </main>

<script src="js/inventario.js"></script>
</body>
</html>