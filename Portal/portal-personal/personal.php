<?php
session_start(); // Inicia la sesión

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
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
    <title>Gestión de Personal</title>
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
                    
      <!--              <ul class="menu">
						<li><a href="../portal-camiones/camiones.html">Camiones</a></li>
					</ul>-->

<!--					<ul class="menu">
						<li class="dropdown">
							<a href="/Portal/portal-camiones/camiones.html" class="dropdown-toggle">Alta de camiones</a>
								<ul class="submenu">
									<li><a id="submenu" href="/Portal/portal-camiones/camiones.html">Ver Camiones</a></li>
									<li><a id="submenu" href="#">Alta</a></li>
								</ul>
						</li>
					</ul> 
			
					<ul class="menu">
						<li><a href="../portal-inventario/alta-inventario.php">Inventario</a></li>
					</ul>
 -->
    				<ul class="menu">
						<li><a href="#">Personal</a></li>
					</ul>
<!--
					<ul class="menu">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle">Alta de Personal</a>
							<ul class="submenu">
								<li><a id="submenu" href="#">Mecanicos/almacen</a></li>
								<li><a id="submenu" href="#">Choferes</a></li>
							</ul>
						</li>
					</ul>
-->

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
                <h2>Gestión de Personal</h2>
                <button class="btn-primario" id="btn-abrir-modal">+ Agregar Personal </button>
            </div>
            
            <div class="tabla-contenido">
                <table>
                    <thead>
                        <tr>
                            <th class="sortable" data-column="id_interno">ID Interno</th>
                            <th class="sortable" data-column="nombre">Nombre</th>
                            <th class="sortable" data-column="nombre_rol">Rol</th>
                            <th class="sortable" data-column="estatus">Estatus</th>
                            <th class="sortable" data-column="fecha_ingreso">Fecha de Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-personal-body">
                        </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal-overlay oculto" id="modal-personal">
        <div class="modal-contenido">
            <span class="modal-cerrar" id="btn-cerrar-modal">&times;</span>
            <h2>Registrar Nuevo Empleado</h2>
            <form id="form-alta-personal">

                <div class="form-grupo">
                    <label for="nombre">Nombre(s)</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Carlos" required>
                </div>
                <div class="form-grupo">
                    <label for="apellido_paterno">Apellido Paterno</label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno" placeholder="Ej: Pérez" required>
                </div>
                <div class="form-grupo">
                    <label for="apellido_materno">Apellido Materno</label>
                    <input type="text" id="apellido_materno" name="apellido_materno" placeholder="Ej: García" required>
                </div>

                <div class="form-grupo">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="" disabled selected>Seleccione un rol</option>
                        <option value="1">Administrador</option>
                        <option value="2">Mesa de Mantenimiento</option>
                        <option value="3">Técnico Mecánico</option>
                        <option value="4">Jefe de Taller</option>
                        <option value="5">Receptor de Taller</option>
                        <option value="6">Almacenista</option>
                        <option value="7">Conductor</option>

                    </select>
                </div>
                <div class="form-grupo">
                    <label for="fecha_ingreso">Fecha de Ingreso</label>
                    <input type="date" id="fecha_ingreso" name="fecha_ingreso" required>
                </div>

                <hr style="border: 1px solid #ECF0F5; margin: 15px 0;">              

                <div class="form-grupo">
                    <label for="id_empleado">ID de Empleado (se genera automáticamente)</label>
                    <input type="text" id="id_empleado" placeholder="Seleccione un rol para generar el ID" readonly>
                </div>

                <div class="form-grupo">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" placeholder="Ej: jperez@flecha.com" required>
                </div>
                <div class="form-grupo">
                    <label for="password">Contraseña Temporal *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                
                <button type="submit" class="btn-primario btn-modal">Registrar</button>
            </form>
        </div>
    </div>

 <script src="js/script.js"></script>
</body>
</html>