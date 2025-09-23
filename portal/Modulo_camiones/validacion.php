<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="../css/style.css">
	<link rel="icon" href="img/favicon.ico" type="image/x-icon">
	<script src="../../js/script.js"></script> <!-- Script general -->

</head>
<body>
	
	<?php
		session_start();
			include "../../php/control.php";
		error_reporting(0);
			if ($_SESSION['id'] == '888')
				{
			
				}
			else{
				header("Location: ../index.html");
				}		
	?>

    <!-- Contenedor principal -->
    <div class="contenedor-principal">		
		 <!-- Menú superior -->
		<div class="menu-superior">
			<div class="opciones">
				<ul class="menu">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle">Nóminas</a>
						<ul class="submenu">
							<li><a id="submenu" href="../nominas/nomina.php">Cotizador</a></li>
							<li><a id="submenu" href="../nominas/validacion.php">Validación de cuentas</a></li>
						</ul>
					</li>
				</ul>
				
				<ul class="menu">
					<li><a href="../legal/legal.php">Legal</a></li>
				</ul>			
			</div>
        
			<div class="perfil">
				<a href="../menu.php">
					<img src="../img/home.png" class="img-perfil">
				</a>
  				  <?php
  				      echo "<span style='color: white;'>".$_SESSION['username']."</span>";
  				  ?>
    
  				  <a href="../../php/logout.php"><button type="button">Cerrar sesión</button></a>
			</div>
		</div>

		<div class="formularios-contenedor">
			<h1>Chambeando <small>--- PROXIMANTE ---</small></h1>
			<img src="../../img/trabajo-en-progreso.png" alt="">
		</div>
	
<!--
        <div class="formularios-contenedor">

			<div class="formulario-izquierda">
				<script src="js/piramidador.js"></script>

				<form class="formulario">

					<h2 class="titulo">Validador<br><small>De Cuentas</small></h2>

					<div class="fila">
						<label name="piramidacion">Piramidación</label>
						
							<select id="piramida" name="piramida" onchange="formulario_piramidador()">
								<option value="nada" name="nada">	--	</option>
								<option value="Bruto a Neto" name="Bruto a Neto">Bruto a Neto</option>
								<option value="Neto a Bruto" name="Neto a Bruto">Neto a Bruto</option>
							</select>
				
						<label name="visr" id="visr">ISR Determinado</label>
						<input disabled type="number" placeholder="0.000" name="v_isr" id="v_isr" value="0.0">	
					</div>

					<div class="fila">
						<label name="tb_impuestos">Tabla de impuesto</label>
						
						<select id="periodicidad" name="periodicidad" onchange="formulario_piramidador()">
							<option value="nada" name="nada">    --    </option>
    						<option value="vsemanal" name="semanal">Semanal</option>
   		 					<option value="vquincenal" name="quincenal">Quincenal</option>
    						<option value="vmensual" name="mensual">Mensual</option>
							<option value="vanual" name="anual" id="idanual">Anual</option>
						</select>
							
							<label name="vsub">Subsidio al empleo</label>
							<input disabled type="number" placeholder="0.000" name="v_sub" id="v_sub" value="0.0">
					</div>


					<div class="fila">
						<label name="vmensual" id="vmensual">Bruto</label>
						<input type="text" placeholder="Ingrese un número" name="salario" id="salario" value="0.0">
				 
						<label name="visrr">ISR Retenido</label>
						<input disabled type="text" placeholder="0.000" name="v_isrr" id="v_isrr" value="0.0">	 
					</div>		
	
					<div class="fila">
						<label name="vpriesgo">Prima de riesgo</label>
						<input type="number" placeholder="Ingrese el valor de prima de riesgo a considerar" name="priesgo" id="priesgo" value="0.0">
					
						<label name="vmss">Cuota Imss</label>
						<input disabled type="text" placeholder="0.000" name="v_imss" id="v_imss" value= "0.0">
					</div>


					<div class="fila">
						<label name="nsubanual" id="idsubanual">Subsidio del año causado</label>
						<input disabled type="number"  placeholder="0.000" name="v_subanual" id="id_vsubanual" value="0.0">
					
						<label name="v_net" id="v_net">Neto a pagar</label>
						<input disabled type="text" placeholder="0.000" name="v_neto" id="v_neto" value= "0.0">
					</div>
			 
					<div class="fila">
						<label name="vacio"></label>
						<label name="vacio"></label>
					</div>
		
					<div class="botones">
						<button class="boton" type="button" onclick="valida_metodo() " id="b1">Enviar</button>
						<button class="boton" id="b2"type="button" onclick="window.location.href='nomina.php'">Borrar</button>
					</div>
			 
				</form>
			</div>
			
			
			<div class="formulario-derecha">	
				<form class="formulario" method="post">
        			<h2 class="titulo">Cuotas Patronales<br><small>Por el Empleado</small></h2>
			
					<div class="fila">
						<label name="vimss">IMSS</label>
						<input disabled type="text" placeholder="0.000" name="v_imssp" id="v_imssp" value= "0.0">
					</div>

					<div class="fila">
						<label name="v_infon">Infonavit</label>
						<input disabled type="text" placeholder="0.000" name="v_infonavit" id="v_infonavit" value= "0.0">
					</div>

					<div class="fila">
						<label name="v_infona">ISN (CDMX)</label>
						<input disabled type="text" placeholder="0.000" name="v_isn" id="v_isn"value= "0.0">
					</div>	

					<div class="fila">
						<label name="v_net">Total de aportaciones</label>
						<input disabled type="text" placeholder="0.000" name="v_netop" id="v_netop" value= "0.0">
					</div>

				</form>
		
			</div>			

        </div>
			-->
    </div>
</body>
</html>

