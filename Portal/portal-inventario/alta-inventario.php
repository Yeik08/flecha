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
    <title>Inventario</title>
    <link rel="shortcut icon" href="../img/bus_8502475.png">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Contenedor principal -->
    <div class="contenedor-principal">
        <div class="menu-superior">
            <!-- Opciones del menú -->
            <div class="opciones">
                <div class="fr">FLECHA ROJA</div>

                <ul class="menu">
                    <li><a href="../portal-camiones/camiones.php">Camiones</a></li>
                </ul>

                <ul class="menu">
                    <li><a href="#">Inventario</a></li>
                </ul>
                
                <ul class="menu">
                    <li><a href="../portal-personal/personal.html">Personal</a></li>
                </ul>
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

    <!-- Body -->
    <main class="modulo-contenido">
        <div class="modulo-titulo">
            <h1>Gestión de Inventario de Filtros y Lubricantes</h1>
        </div>

        <div class="tarjetas">
            <div class="tarjeta">
                <h3>Filtros Disponibles</h3> <p id="kpi-filtros">Cargando...</p> </div>
            <div class="tarjeta">
                <h3>Litros de Aceite</h3> <p id="kpi-litros">Cargando...</p> </div>
            <div class="tarjeta alerta">
                <h3>Alertas Stock Bajo</h3> <p id="kpi-alertas">Cargando...</p> </div>
            <div class="tarjeta">
                <h3>Filtros Instalados</h3>
                <p id="kpi-instalados">Cargando...</p> </div>
        </div>

        <div class="tabla-contenido">
            <div class="tabla-titulo">
                <h2>Filtros y Lubricantes Disponibles</h2>
                <button class="btn-primario" id="btn-agregar-inventario">+ Agregar Inventario</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Categoría</th>      <th>Descripción</th>    <th>Identificador</th>  <th>Stock Actual</th>   <th>Ubicación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-inventario-body">
                    <tr><td colspan="6" style="text-align:center">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="modal-fondo" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index:1000;">
            <div class="contenedor-formulario" id="modal-carga-masiva" style="display:block; max-width:600px; margin: 50px auto; position: relative;">
                <div class="modulo-titulo">
                    <h2>Carga Masiva de Inventario</h2>
                </div>

                <form id="form-carga-masiva">
                    <div class="campo-form">
                        <label>Selecciona el tipo de inventario:</label>
                        <select id="tipo-inventario-masivo" required>
                            <option value="filtro">Filtro</option>
                            <option value="lubricante">Lubricante</option>
                        </select>
                    </div>

                    <div class="acciones-form">
                        <button type="button" class="btn-primario" id="btn-descargar-plantilla">Descargar Plantilla</button>
                    </div>

                    <div class="campo-form">
                        <label>Subir archivo de Inventario (CSV)</label>
                        <input type="file" id="upload-excel-masivo" accept=".csv" />
                    </div>

                    <div class="acciones-form">
                        <button type="submit" class="btn-primario">Guardar y Continuar</button>
                        <button type="button" class="btn-eliminar" id="btn-cancelar-masivo">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        </main>

<div id="modal-editar-inventario" class="contenedor-formulario" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div class="modulo-titulo">
            <h2>Editar Inventario</h2>
        </div>
        
        <form id="form-editar-item">
            <input type="hidden" id="edit-id" name="id">
            <input type="hidden" id="edit-tipo" name="tipo_bien">

            <div class="campo-form">
                <label>Descripción:</label>
                <input type="text" id="edit-descripcion" readonly style="background-color: #f0f0f0; color: #555;">
            </div>
            <div class="campo-form">
                <label>Identificador (Serie/Granel):</label>
                <input type="text" id="edit-identificador" readonly style="background-color: #f0f0f0; color: #555;">
            </div>

            <div class="campo-form">
                <label>Ubicación:</label>
                <select id="edit-ubicacion" name="id_nueva_ubicacion" required>
                    <option value="3">Almacén Magdalena</option>
                    <option value="4">Almacén Poniente</option>
                </select>
            </div>

            <div class="campo-form" id="div-edit-litros" style="display:none;">
                <label>Litros Disponibles:</label>
                <input type="number" id="edit-litros" name="nuevos_litros" step="0.1" min="0">
            </div>

            <div class="acciones-form">
                <button type="button" class="btn-eliminar" onclick="document.getElementById('modal-editar-inventario').style.display='none'; document.getElementById('modal-fondo').style.display='none';">Cancelar</button>
                <button type="submit" class="btn-primario">Guardar Cambios</button>
            </div>
        </form>
    </div>
<script src="js/script.js"></script>
</body>
</html>
