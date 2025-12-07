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
                <h3>Total de Filtros</h3>
                <p>1,245</p>
            </div>
            <div class="tarjeta">
                <h3>Total de Lubricantes</h3>
                <p>200</p>
            </div>
            <div class="tarjeta alerta">
                <h3>Inventario Bajo</h3>
                <p>3</p>
            </div>
            <div class="tarjeta">
                <h3>Filtros Instalados</h3>
                <p>89</p>
            </div>
        </div>

        <div class="tabla-contenido">
            <div class="tabla-titulo">
                <h2>Filtros y Lubricantes Disponibles</h2>
                <button class="btn-primario" id="btn-agregar-inventario">+ Agregar Inventario</button>
            </div>
            
            <table>
                <!-- CABECERA DE TABLA -->
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Cantidad en Inventario</th>
                        <th>Ubicación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <!-- CUERPO DE TABLA -->
                <tbody>
                    <tr>
                        <td>FR-ACE-001</td>
                        <td>Filtro de Aceite</td>
                        <td>PATITO1</td>
                        <td>50</td>
                        <td>Almacén A</td>
                        <td class="acciones">
                            <button class="btn-editar">Editar</button>
                            <button class="btn-eliminar">Eliminar</button>
                        </td>
                    </tr>
                    <tr class="stock-bajo">
                        <td>FR-AIR-003</td>
                        <td>Filtro de Aire Primario</td>
                        <td>PATITO2</td>
                        <td>8</td>
                        <td>Almacén B</td>
                        <td class="acciones">
                            <button class="btn-editar">Editar</button>
                            <button class="btn-eliminar">Eliminar</button>
                        </td>
                    </tr>
                    <tr>
                        <td>FR-COMB-002</td>
                        <td>Filtro de Combustible</td>
                        <td>PATITO3</td>
                        <td>120</td>
                        <td>Almacén A</td>
                        <td class="acciones">
                            <button class="btn-editar">Editar</button>
                            <button class="btn-eliminar">Eliminar</button>
                        </td>
                    </tr>
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


<script src="js/script.js"></script>
</body>
</html>
