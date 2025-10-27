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
                    
                    <ul class="menu">
						<li><a href="../portal-camiones/camiones.html">Camiones</a></li>
					</ul>

<!--					<ul class="menu">
						<li class="dropdown">
							<a href="/Portal/portal-camiones/camiones.html" class="dropdown-toggle">Alta de camiones</a>
								<ul class="submenu">
									<li><a id="submenu" href="/Portal/portal-camiones/camiones.html">Ver Camiones</a></li>
									<li><a id="submenu" href="#">Alta</a></li>
								</ul>
						</li>
					</ul> -->
			
					<ul class="menu">
						<li><a href="../portal-inventario/alta-inventario.html">Inventario</a></li>
					</ul>

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
                            <th>ID Empleado</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Estatus</th>
                            <th>Fecha de Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>MEC-001</td>
                            <td>Juan Pérez</td>
                            <td>Mecánico</td>
                            <td>Activo</td>
                            <td>2023-01-15</td>
                            <td class="acciones">
                                <button class="btn-editar">Editar</button>
                                <button class="btn-eliminar">Eliminar</button>
                            </td>
                        </tr>
                        <tr>
                            <td>ALM-001</td>
                            <td>Maria López</td>
                            <td>Almacenista</td>
                            <td>Activo</td>
                            <td>2022-11-20</td>
                            <td class="acciones">
                                <button class="btn-editar">Editar</button>
                                <button class="btn-eliminar">Eliminar</button>
                            </td>
                        </tr>
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
                    <input type="text" id="nombre" placeholder="Ej: Juan Carlos" required>
                </div>
                <div class="form-grupo">
                    <label for="apellido_paterno">Apellido Paterno</label>
                    <input type="text" id="apellido_paterno" placeholder="Ej: Pérez" required>
                </div>
                <div class="form-grupo">
                    <label for="apellido_materno">Apellido Materno</label>
                    <input type="text" id="apellido_materno" placeholder="Ej: García" required>
                </div>

                <div class="form-grupo">
                    <label for="rol">Rol</label>
                    <select id="rol" required>
                        <option value="" disabled selected>Seleccione un rol</option>
                        <option value="Mecanico">Mecánico</option>
                        <option value="Almacenista">Almacenista</option>
                        <option value="Conductor">Conductor</option>
                    </select>
                </div>
                <div class="form-grupo">
                    <label for="fecha_ingreso">Fecha de Ingreso</label>
                    <input type="date" id="fecha_ingreso" required>
                </div>
                
                <div class="form-grupo">
                    <label for="id_empleado">ID de Empleado (se genera automáticamente)</label>
                    <input type="text" id="id_empleado" placeholder="Seleccione un rol para generar el ID" readonly required>
                </div>
                
                <button type="submit" class="btn-primario btn-modal">Registrar</button>
            </form>
        </div>
    </div>

 <script src="js/script.js"></script>
</body>
</html>