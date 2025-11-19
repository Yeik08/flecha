<?php
session_start(); // Inicia la sesión

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
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
    <title>Camiones</title>
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
						<li><a href="#">Camiones</a></li>
					</ul>


<!--					<ul class="menu">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle">Alta de camiones</a>
								<ul class="submenu">
									<li><a id="submenu" href="#">Ver Camiones</a></li>
									<li><a id="submenu" href="#">Alta</a></li>
								</ul>
						</li>
					</ul>-->
			
					<ul class="menu">
						<li><a href="../portal-inventario/alta-inventario.html">Inventario</a></li>
					</ul>


					<ul class="menu">
						<li><a href="../portal-personal/personal.html">Personal</a></li>
					</ul>

<!--					<ul class="menu">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle">Alta de Personal</a>
							<ul class="submenu">
								<li><a id="submenu" href="#">Mecanicos & almacen</a></li>
								<li><a id="submenu" href="#">Conductores</a></li>
							</ul>
						</li>
					</ul>-->

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
                <h1>Gestión de Flota de Camiones</h1>
            </div>

            <div class="acciones-header">
                <div class="tarjeta-accion" id="kpi-aprobaciones">
                    <h3>Aprobaciones Pendientes</h3>
                    <p class="numero-grande">4</p>
                    <p>Camiones esperando salida de taller.</p>

                    <!-- Lista desplegable oculta -->
                    <ul id="lista-aprobaciones" class="lista-oculta">
                        <li>ECO-101 — MAN ECLIPSE DD</li>
                        <li>ECO-203 — SCANIA i5</li>
                        <li>ECO-319 — BOXER OF</li>
                        <li>ECO-410 — CENTURY ACM</li>
                    </ul>
                </div>

                <div class="tarjeta-accion">
                    <div class="acciones-titulo">
                        <button id="btn-abrir-modal" class="btn-primario">+ Registrar Nuevo Camión</button>
                        <button class="btn-secundario" id="btn-abrir-modal-telemetria">Subir Telemetría</button>
                    </div>
                    <p>Dar de alta un vehículo en el sistema.</p>
                </div>
            </div>

            <div class="tabla-contenido">
               <table>
                    <div class="buscador">
                        <input type="text" id="buscar-eco" placeholder="Buscar por número ECO o palabra clave...">
                    </div>


                    <thead>
                        <tr>
                            <th>ID del Camión</th>
                            <th>Placas</th>
                            <th>Estatus</th>
                            <th>Último Mantenimiento  <br>(Filtro y Lubricante)</th>
                            <th>Próximo Mantenimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    
                    </tbody>
                </table>
            </div>
        </main>


    <div id="modal-formulario" class="modal-overlay oculto">
        <div class="modal-contenido">
            <button class="modal-cerrar">&times;</button>

            <div class="mod-carga">
                <button class="carga-link active" data-tab="tab-manual">Registro Manual</button>
                <button class="carga-link" data-tab="tab-archivo">Subir Archivo</button>
            </div>      

            <div id="tab-manual" class="tab-content active">

                <div class="form-header">
                    <h2>Registro de Nuevo Camión</h2>
                    <p>Introduce la información detallada del vehículo.</p>
                </div>

                <form id="form-alta-camion">

                    <div class="form-row"> <div class="form-lvip">
                            <label for="condicion">Condición del Vehículo</label>
                            <select id="condicion" name="condicion">
                                <option value="usado">Usado / En Servicio</option>
                                <option value="nuevo">Nuevo</option>
                            </select>
                        </div>
                    </div>
                    <hr class="form-divide">

                    <div class="form-row">
                        <div class="form-lvip">
                            <label for="identificador">ID del Vehículo (Ej: ECO-123)</label>
                            <input type="text" id="identificador" name="identificador" required>
                        </div>
                        <div class="form-lvip">
                            <label for="placas">Placas</label>
                            <input type="text" id="placas" name="placas" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-lvip">
                            <label for="numero_serie">Número de Serie (VIN)</label>
                            <input type="text" id="numero_serie" name="numero_serie"  required minlength="17" maxlength="17">
                        </div>
                        <div class="form-lvip">
                            <label for="id_conductor">ID del Conductor Asignado</label>
                            <input type="text" id="id_conductor" name="id_conductor" placeholder="Buscar conductor...">
                            <div id="sugerencias-conductor" class="sugerencias-lista"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-lvip">
                            <label for="marca">Marca</label>
                            <select id="marca" name="marca" required>
                                <option value="">Selecciona una marca</option>
                                <option value="Scania">Scania</option>
                                <option value="MAN">MAN</option>
                                <option value="Mercedes-Benz">Mercedes-Benz</option>
                                <option value="Volvo">Volvo</option>
                                <option value="International">International</option>
                                <option value="Volkswagen">Volkswagen</option>
                                <option value="Otro">Otro (dar de alta)</option>
                            </select>
                        </div>
                        <div class="form-lvip">
                            <label for="anio">Modelo (Año)</label>
                            <select id="anio" name="anio" required>
                                <option value="">Selecciona año</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-lvip">
                            <label for="tipo_unidad">Tecnología de carrococería</label>
                            <select id="tipo_unidad" name="tipo_unidad">
                                <option value="">Selecciona tipo de tecnologia</option>
                            </select>
                        </div>
                        <div class="form-lvip">
                            <label for="estatus_inicial">Estatus Inicial</label>
                            <select id="estatus_inicial" name="estatus_inicial">
                                <option value="trabajando">Listo para trabajar</option>
                                <option value="mantenimiento">En Taller / Mantenimiento</option>
                                <option value="inactivo">Inactivo / En espera</option>
                            </select>
                        </div>
                    </div>

                    <hr class="form-divide">
                    
                    <div class="form-row">
                        <div class="form-lvip">
                            <label for="kilometros">Kilómetros Recorridos</label>
                            <input type="number" id="kilometros" name="kilometros">
                        </div>
                        
                        <div class="form-lvip ocultar-si-es-nuevo">
                            <label for="fecha_mantenimiento">Último Mantenimiento General</label>
                            <input type="date" id="fecha_mantenimiento" name="fecha_mantenimiento">
                        </div>
                    </div>

                    <hr class="form-divide">
                    <p style="text-align: center; color: #666; margin-bottom: 15px;">Información de Filtros</p>

                    <div class="form-row">
                        <div class="form-lvip">
                            <label for="marca_filtro">Marca de Filtro de aceite</label>
                            <select id="marca_filtro" name="marca_filtro">
                                <option value="">Cargando marcas...</option>
                            </select>
                        </div>

                        <div class="form-lvip">
                            <label for="numero_serie_filtro_aceite">Número de Serie (Filtro de aceite)</label>
                            <input type="text" id="numero_serie_filtro_aceite" name="numero_serie_filtro_aceite">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-lvip">
                            <label for="marca_filtro_centrifugo">Marca de Filtro de centrífugo</label>
                            <select id="marca_filtro_centrifugo" name="marca_filtro_centrifugo">
                                <option value="">Cargando marcas...</option>
                            </select>
                        </div>
                        <div class="form-lvip">
                            <label for="numero_serie_filtro_centrifugo">Número de Serie (Filtro de centrífugo)</label>
                            <input type="text" id="numero_serie_filtro_centrifugo" name="numero_serie_filtro_centrifugo">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-lvip ocultar-si-es-nuevo">
                            <label for="fecha_cambio_filtro" >Último Cambio de Filtro de aceite</label>
                            <input type="date" id="fecha_cambio_filtro" name="fecha_cambio_filtro">
                        </div>
                        <div class="form-lvip ocultar-si-es-nuevo">
                            <label for="fecha_cambio_filtro_centrifugo">Ultimo Cambio de Filtro de centrifugo</label>
                            <input type="date" id="fecha_cambio_filtro_centrifugo" name="fecha_cambio_filtro_centrifugo">
                        </div>
                    </div>


                    <div class="form-row">
                        <div class="form-lvip">   
                            <label for="tipo_aceite" >Lubricante</label>
                                <select id="tipo_aceite" name="tipo_aceite" required>
                                    <option value="">Cargando lubricantes...</option>
                                </select>
                        </div>


                            <!--  

                                 <div class="form-lvip ocultar-si-es-nuevo"> <label for="recorridos-manual">Archivo de Recorridos (CSV)</label>

                                                   <input type="file" id="recorridos-manual" class="input-file" accept=".csv" name="nrecorridos-manu">
                               <input type="file" id="input-csv-recorridos" class="input-file" accept=".csv" name="archivo_recorridos">

                                </div>-->  
                    </div>

                    <div class="form-acciones">
                        <button type="button" class="btn-secundario btn-cerrar-modal">Cancelar</button>
                        <button type="submit" class="btn-primario">Registrar Camión</button>
                    </div>
                </form>
            </div>
            
                             <!--	---------------------------------------------- MACHOTE ----------------------------------------------	 -->

            <div id="tab-archivo" class="tab-content">


                <form id="form-alta-archivo">
                    <div class="form-header">
                        <h2>Carga Masiva de Camiones</h2>
                        <p>Sube los archivos CSV con los datos de los nuevos vehículos</p>
                    </div>

                    <div class="seccion-sube">

                        <div class="paso-sube">
                            <div class="paso-numero">1</div>

                            <div class="paso-info">
                                <h3>Definir Condición</h3>
                                <p>Selecciona si los camiones en el archivo son nuevos o usados</p>
                            </div>

                            <select id="condicion-archivo" class="select-paso" name="condicion-archivo">
                                <option value="usado">Usado / En Servicio</option>
                                <option value="nuevo">Nuevo</option> 
                            </select>
                        </div>

                        <div class="paso-sube">
                            <div class="paso-numero">2</div>
                            <div class="paso-info">
                                <h3>Descargar Plantilla de Alta</h3>
                                <p>Usa este archivo como guía para los datos de los camiones</p>
                            </div>
                            <button id="btn-descargar-plantilla-alta" class="btn-secundario">Descargar</button>
                        </div>

                        <div class="paso-sube">
                            <div class="paso-numero">3</div>
                            <div class="paso-info">
                                <h3>Subir Datos de Alta</h3>
                                <p>Selecciona el archivo CSV (Nuevos o Usados) con la lista</p>
                                <input type="file" id="input-csv-alta" class="input-file" accept=".csv" name="archivo_camiones" >
                            </div>
                        </div>
                        

                        <div class="form-acciones">
                            <button type="button" class="btn-secundario btn-cerrar-modal">Cancelar</button>
                            <button type="submit" class="btn-primario" id="btn-guardar-archivo-masivo">Confirmar y Guardar</button>                
                        </div>
                        <!--    ---------------------------------------------- RECORRIDOS ----------------------------------------------
                        <hr class="form-divide"> 
                        <div class="paso-sube">
                            <div class="paso-numero">4</div>
                            <div class="paso-info">
                                <h3>Descargar Plantilla de Recorridos</h3>
                                <p>Plantilla opcional para subir el historial de KM de las unidades</p>
                            </div>
                            <button id="btn-descargar-recorridos-archivo" class="btn-secundario">Descargar</button>
                        </div>

                        <div class="paso-sube">
                            <div class="paso-numero">5</div>
                            <div class="paso-info">
                                <h3>Subir Historial de Recorridos</h3>
                                <p>Selecciona el archivo CSV con los kilómetros de las unidades</p>

                                <input type="file" id="input-csv-recorridos" class="input-file" accept=".csv" name="archivo_recorridos">
                            </div>
                        </div>-->
                    </div>
                        <!--
                    <div id="preview-container" class="preview-container">

                    </div>

                    <div class="form-acciones">
                        <button type="button" class="btn-secundario btn-cerrar-modal">Cancelar</button>
                        <button type="submit" class="btn-primario" id="btn-guardar-csv" >Confirmar y Guardar</button>                
                    </div>      
                    -->
                </form>
            </div>

        </div>
    </div>



    <div id="modal-subir-telemetria" class="modal-overlay oculto">
        <div class="modal-contenido">
            <button class="modal-cerrar">&times;</button>
            
            <form id="form-subir-telemetria">
                <div class="form-header">
                    <h2>Subir Historial de Telemetría</h2>
                    <p>Sube el archivo CSV con los recorridos mensuales de las unidades.</p>
                </div>

                <div class="seccion-sube">
                    <div class="paso-sube">
                        <div class="paso-numero">1</div>
                        <div class="paso-info">
                            <h3>Descargar Plantilla de Recorridos</h3>
                            <p>Usa esta plantilla para registrar los datos de telemetría.</p>
                        </div>
                        <button id="btn-descargar-recorridos-archivo" class="btn-secundario">Descargar</button>
                    </div>

                    <div class="paso-sube">
                        <div class="paso-numero">2</div>
                        <div class="paso-info">
                            <h3>Subir Historial de Recorridos</h3>
                            <p>Selecciona el archivo CSV con los kilómetros de las unidades</p>
                            <input type="file" id="input-csv-recorridos" class="input-file" accept=".csv" name="archivo_recorridos">
                        </div>
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="button" class="btn-secundario btn-cerrar-modal">Cancelar</button>
                    <button type="submit" class="btn-primario" id="btn-guardar-csv-telemetria" disabled>Confirmar y Guardar</button>                
                </div>
            </form> 
        </div>
    </div>

    <div id="modal-editar" class="modal-overlay oculto">
        <div class="modal-contenido">
            <button class="modal-cerrar">&times;</button>
            
            <div class="form-header">
                <h2>Editar Camión <span id="titulo-eco-editar"></span></h2>
                <p>Modifica el estatus, conductor o corrige datos.</p>
            </div>

            <form id="form-editar-camion">
                <input type="hidden" id="edit_id_camion" name="id_camion">

                <div class="form-row">
                    <div class="form-lvip">
                        <label>Estatus Actual</label>
                        <select id="edit_estatus" name="estatus">
                            <option value="Activo">Activo</option>
                            <option value="En Taller">En Taller</option>
                            <option value="Inactivo">Inactivo</option>
                            <option value="Vendido">Vendido</option>
                        </select>
                    </div>
                    <div class="form-lvip">
                        <label>Kilometraje Total</label>
                        <input type="number" id="edit_kilometraje" name="kilometraje_total" step="0.01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-lvip">
                        <label>Conductor Asignado (ID Interno)</label>
                        <input type="text" id="edit_conductor" name="id_conductor" placeholder="Buscar conductor..." autocomplete="off">
                        
                        <div id="sugerencias-conductor-edit" class="sugerencias-lista"></div>
                    </div>
                    <div class="form-lvip">
                        <label>Placas</label>
                        <input type="text" id="edit_placas" name="placas">
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="button" class="btn-secundario btn-cerrar-modal">Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>



    

    <script src="js/scripts.js"></script>
</body>
</html>