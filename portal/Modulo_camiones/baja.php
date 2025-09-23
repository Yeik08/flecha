<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="../css/style.css">
	<link rel="icon" href="img/favicon.ico" type="image/x-icon">
	<script src="../js/script.js"></script>

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
				<a href="nomina.php">Nómina</a>
				<a href="../legal/legal.php">Legal</a>
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
	

        <div class="formularios-contenedor" id="baja">

			<div class="formulario-izquierda">
				<script src="js/piramidador.js"></script>

				<form class="formulario">

					<h2 class="titulo">Calculo de Baja<br><small>Del Empleado</small></h2>

					<div class="fila">
						<label name="tipbaja">¿Que deseas calcular?</label>
						
							<select id="idtbaja" name="tbaja" onchange="adecuacion_formulario()">
								<option value="nada" name="nada">	--	</option>
								<option value="vfiniquito" name="finiquito" id="idfiniquito">Finiquito</option>
								<option value="vliquidacion" name="liquidacion" id="idliquidacion">Liquidación</option>
							</select>
						<label name="diaguipag" id="iddiaguipag">Días pactados de aguinaldo a pagar</label>
						<input type="number" placeholder="0.000" name="ndiaguipag" id="id_diaguipag" value="0.0">	
					</div>

					<div class="fila">
						<label name="sdi">Salario Diario</label>
						<input type="number" placeholder="0.0000" name="nsdi" id="idsdi" value="0.0">
							
						<label name="faltas">Ausentismo en el año</label>
						<input type="number" placeholder="0.000" name="nfaltas" id="idfaltas" value="0.0">
					</div>

 
					<div class="fila">
						<label name="fchingreso" id="idfchingreso">Fecha de Ingreso</label>
						<input type="date" placeholder="Fecha de Ingreso" name="fching" id="idfching" value="0.0">
				 
						<label name="escenario" id="idescenario">Escenarios</label>
						<input disabled type="number" placeholder="0.000" name="nescenario" id="idescenario1" value="0.0">	 
					</div>		
	
					<div class="fila">
						<label name="vpriesgo">Fecha de Baja</label>
						<input type="date" placeholder="Fecha de baja" name="fchbj" id="idfchbj" value="0.0">
					
						<label name="vmss">Cuota Imss</label>
						<input disabled type="text" placeholder="0.000" name="v_imss" id="v_imss" value= "0.0">
					</div>


					<div class="fila">
						<label name="vacdi" id="idvacdi">Dias de vacaciones gozadas</label>
						<input type="number"  placeholder="0.000" name="vvacdi" id="id_vacdi" value="0.0">
					
						<label name="v_net" id="v_net">Neto a pagar</label>
						<input disabled type="text" placeholder="0.000" name="v_neto" id="v_neto" value= "0.0">
					</div>
			 
		
					<div class="botones">
						<button class="boton" type="button" onclick="calculo_baja() " id="b1">Enviar</button>
						<button class="boton" id="b2"type="button" onclick="window.location.href='baja.php'">Borrar</button>
					</div>
			 
				</form>
			</div>
			
	




        </div>

    </div>
</body>
</html>

