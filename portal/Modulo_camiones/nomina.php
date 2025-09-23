<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nóminas</title>   																		 <!--CAMBIAR TITULO -->
	<link rel="shortcut icon" href="../img/Principales/favicon.ico">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
	
	<?php
	//	session_start();
	  //include "../php/control.php";
	//if (/$_SESSION['id'] == '888')		
	//	{0
	//	}000
	//	else{
	//		header("Location:../index.html");
	//	}	
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
							<li><a id="submenu" href="../Modulo_Principal/checador.php">Ver Checador</a></li>
							<li><a id="submenu" href="../Modulo_Principal/vacaciones.php">Vacaciones</a></li>
						</ul>
					</li>
				</ul>
			
				<ul class="menu">
					<li><a href="../Modulo_Principal/notificaciones.php">Notificaciones</a></li>
				</ul>

				<ul class="menu">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle">Nóminas</a>
						<ul class="submenu">
							<li><a id="submenu" href="nomina.php">Cotizador</a></li>
							<li><a id="submenu" href="validacion.php">Validación de cuentas</a></li>
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

				<a href="../Modulo_Principal/menu.php">
					<img src="../img/Principales/home.png" class="img-perfil">
				</a>

				<?php
					//echo "<span style='color: white;'>".$_SESSION['username']."</span>";
				?>

				<a href="../../php/logout.php"><button type="button">Cerrar sesión</button></a>
			</div>

		</div>
<!--	---------------------------------------------- BODY ----------------------------------------------	 -->


        <div class="formularios-contenedor">

			<div class="formulario-izquierda">
<!--				<script src="js/piramidador.js"></script> -->

				<form class="formulario">
					
					<h2 class="titulo">Calculo de Nomina<br><small>Del Empleado</small></h2>
					
					<div class="fila">

						<label name="mecacalc">Mecanica de Calculo</label>
						
						<div class="switch-button">
							<input type="checkbox" name="switch-button" id="switch-label" class="switch-button__checkbox">
							<label for="switch-label" class="switch-button__label"></label>
						</div>

						<label name="vmeca">Mécanica </label>
						<input disabled  placeholder="FIJA" name="mecanica" id="mecanica" value="FIJA">
						
					</div>

					<div class="fila">

						<label name="piramidacion">Piramidación</label>
						
							<select id="piramida" name="piramida" onchange="formulario_piramidador()">
								<option value="nada" name="nada">	--	</option>
								<option value="Bruto a Neto" name="Bruto a Neto">Bruto a Neto</option>
								<option value="Neto a Bruto" name="Neto a Bruto">Neto a Bruto</option>
							</select> 						

						<label name="visr" id="visr">ISR Determinado</label>

						<div class=input-button-container>
							<input disabled type="number" placeholder="0.000" name="v_isr" id="v_isr" value="0.0">	
						
							<button class="toggle-button" type="button" onclick="toggle('fields1')" id="b1">▼</button>
						</div>

					</div>


					 <!--DIV DE CALCULO -->
					 
					<div id="additionalFields" style="display: none;">
    					<div class="fila" >

							<label name="vsub" id="adicion">Salario</label>
								<div class="tooltip">
									<input class="ad" disabled type="number" placeholder="0.000" name="v_sala" id="v_sala" value="0.0">
									<span class="tooltiptext" id="v_sala_tooltip"></span>
								</div>	

							<label name="vsub" id="adicion">Limite Superior</label>
							<input  class="ad" disabled type="number" placeholder="0.000" name="v_ls" id="v_ls" value="0.0">
    					
						</div>


						<div class="fila" >

							<label name="vsub" id="adicion">Limite Inferior </label>
							<input class="ad" disabled type="number" placeholder="0.000" name="v_lf" id="v_lf" value="0.0">

							<label name="vsub" id="adicion">Cuota Fija</label>
							<input  class="ad" disabled type="number" placeholder="0.000" name="v_cufij" id="v_cufij" value="0.0">

						</div>

						<div class="fila" >

							<label name="vsub" id="adicion">Porcentaje </label>
							<input class="ad" disabled type="number" placeholder="0.000" name="v_porc" id="v_porc" value="0.0">

							<label name="vacio"></label>
							<label name="vacio"></label>

						</div>
					</div>

					<div class="fila">
						
						<label name="tb_impuestos">Periodicidad</label>
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
					
						<label name="visrr" id="id_visrr">ISR Retenido</label>
						<input disabled type="text" placeholder="0.000" name="v_isrr" id="v_isrr" value="0.0">	 
					</div>

					<div class="fila">

						<label name="vpriesgo">Prima de riesgo</label>
						<input type="number" placeholder="Ingrese el valor de prima de riesgo a considerar" name="priesgo" id="priesgo" value="0.0">
					
						<label name="vmss">Cuota Imss</label>

						<div class=input-button-container>
							<input disabled type="text" placeholder="0.000" name="v_imss" id="v_imss" value= "0.0">
						
							<button class="toggle-button" type="button" onclick="toggle('fields2')" id="b1">▼</button>
						</div>
					</div>



					 <!--DIV DE CALCULO -->
					 
					<div id="additionalFields2" style="display: none;">
    					<div class="fila" >

							<label name="vsub" id="adicion">Excedente empleado</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="ex_emple" id="ex_emple" value="0.0">

							<label name="vsub" id="adicion">Prestaciones en dinero empleado</label>
							<input  class="ad" disabled type="number" placeholder="0.000" name="pres_emple" id="pres_emple" value="0.0">
    					
						</div>

						<div class="fila" >

							<label name="vsub" id="adicion">Gastos medicos para pensionados empleado</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="gast_emple" id="gast_emple" value="0.0">

							<label name="vsub" id="adicion">Invalidez y vida empleado</label>
							<input  class="ad" disabled type="number" placeholder="0.000" name="inva_emple" id="inva_emple" value="0.0">

						</div>

						<div class="fila" >

							<label name="vsub" id="adicion">Censentia y vejez</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="cen_emple" id="cen_emple" value="0.0">

							<label name="vacio"></label>
							<label name="vacio"></label>
						</div>
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
					
						<div class=input-button-container>
							<input disabled type="text" placeholder="0.000" name="v_imssp" id="v_imssp" value= "0.0">

							<button class="toggle-button" type="button" onclick="toggle('fields3')" id="b1">▼</button>
						</div>
					</div>
					 <!--DIV DE CALCULO -->
					 
					<div id="additionalFields3" style="display: none;">
    					
					 	<div class="fila" >

							<label name="vsub" id="adicion">Excedente Patrón</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="ex_patr" id="ex_patr" value="0.0">

							<label name="vsub" id="adicion">Prestaciones en dinero patrón</label>
							<input  class="ad" disabled type="number" placeholder="0.000" name="pres_patr" id="pres_patr" value="0.0">
    					
						</div>

						<div class="fila" >

							<label name="vsub" id="adicion">Gastos medicos para pensionados patrón</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="gast_patr" id="gast_patr" value="0.0">

							<label name="vsub" id="adicion">Riesgo de trabajo patrón</label>
							<input  class="ad" disabled type="number" placeholder="0.000" name="rt_patr" id="rt_patr" value="0.0">

						</div>

						<div class="fila" >

							<label name="vsub" id="adicion">Invalidez y vida patrón</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="inv_patr" id="inv_patr" value="0.0">

							<label name="vsub" id="adicion">Guarderia patrón</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="guar_patr" id="guar_patr" value="0.0">
						
						</div>

						<div class="fila" >

							<label name="vsub" id="adicion">Retiro patrón</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="ret_patr" id="ret_patr" value="0.0">

							<label name="vsub" id="adicion">Infonavit patrón</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="infona_patr" id="infona_patr" value="0.0">
						
						</div>


						<div class="fila" >

							<label name="vsub" id="adicion">Censentia y vejez patronal</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="cen_patr" id="cen_patr" value="0.0">

							<label name="cfija" id="adicion">Cuota Fija</label>
							<input class="ad" disabled type="number" placeholder="0.000" name="cufija" id="cufija" value="0.0">
						
						</div>

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

    </div>

<script src="js/script.js"></script>
</body>
</html>