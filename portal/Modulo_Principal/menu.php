<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
	<link rel="shortcut icon" href="../img/Principales/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
	
	<?php
		session_start();
	include "../../php/control.php";
	if ($_SESSION['id'] == '888'){}
		else{
				header("Location:../index.html");
			}	
	?>

<!--<header>
		<nav class="top-nav">
			<div class="perfil">
				<button class="btn-perfil">Perfil</button>
				<button class="btn-cerrar">Cerrar Sesión</button>
			</div>
		</nav>
	</header>-->
	   
	
    <!-- Contenedor principal -->
    <div class="contenedor-principal">
		
		<!-- Menú superior -->
		<div class="menu-superior">
			<!-- Opciones del menú -->
			<div class="opciones">
				<ul class="menu">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle">Checador</a>
						<ul class="submenu">
							<li><a id="submenu" href="checador.php">Ver Checador</a></li>
							<li><a id="submenu" href="vacaciones.php">Vacaciones</a></li>
						</ul>
					</li>
				</ul>
			
				<ul class="menu">
					<li><a href="notificaciones.php">Notificaciones</a></li>
				</ul>

				<ul class="menu">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle">Nóminas</a>
						<ul class="submenu">
							<li><a id="submenu" href="../Modulo_Nominas/nomina.php">Cotizador</a></li>
							<li><a id="submenu" href="../Modulo_Nominas/validacion.php">Validación de cuentas</a></li>
						</ul>
					</li>
				</ul>
			
				<ul class="menu">
					<li><a href="../Modulo_Legal/legal.php">Legal</a></li>
				</ul>
			</div>
        
			<div class="perfil">
  				<!-- Formulario bajo el mini reloj -->
				<div id="formulario-reloj" class="formulario-reloj" style="display: none;">
  					<button class="boton-checar" id="boton-checar1">Checar</button>
				</div>

				<div class="mini-reloj" id="reloj" role="button" tabindex="0">
					<div class="hora-con-icono">
    					<div class="icono" id="icono-dia-noche1"></div>
    					<div class="hora" id="hora-actual"></div>
  					</div>
				</div>

				<a href="menu.php">
					<img src="../img/Principales/home.png" class="img-perfil">
				</a>

				<?php
					//echo "<span style='color: white;'>".$_SESSION['username']."</span>";
				?>

				<a href="../../php/logout.php"><button type="button">Cerrar sesión</button></a>
			</div>

		</div>
<!--	---------------------------------------------- BODY ----------------------------------------------	 -->


<script src="js/script.js"></script>
</body>
</html>