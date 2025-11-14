<?php
/*
* Portal/portal-camiones/registrar_camion_masivo.php
* VERSIÓN DEFINITIVA:
* - Inicializa las variables de fecha en NULL para que 'bind_param' no falle
* al registrar camiones "nuevos".
* - Quita 'display_errors' para evitar corrupción de JSON.
*/

// --- Quitamos los 'display_errors' ---
// ini_set('display_errors', 1); // <--- Borrado
// ini_set('display_startup_errors', 1); // <--- Borrado
// error_reporting(E_ALL); // <--- Borrado

session_start();
header('Content-Type: application/json');

// --- 1. Seguridad y Conexión ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}
require_once '../../php/db_connect.php'; 
if ($conn === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD.']);
    exit;
}

// --- 2. Validar Archivo ---
if (!isset($_FILES['archivo_camiones']) || $_FILES['archivo_camiones']['error'] != UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: No se recibió el archivo de camiones.']);
    exit;
}

// --- 3. Obtener variables ---
$csv_file = $_FILES['archivo_camiones']['tmp_name'];
$tipo_carga = $_POST['condicion-archivo'] ?? 'usado';
$id_empleado_registra = $_SESSION['user_id'];
$ubicacion_taller = 1; 

$conn->begin_transaction();
$fila = 0;
$errores = [];
$exitosos = 0;

try {
    
    function formatarFecha($fecha_str) {
        if (empty($fecha_str)) return null;
        $fecha_obj = DateTime::createFromFormat('d/m/Y', $fecha_str);
        if ($fecha_obj) return $fecha_obj->format('Y-m-d');
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_str);
        if ($fecha_obj) return $fecha_obj->format('Y-m-d');
        return null;
    }

    $file = fopen($csv_file, 'r');
    if ($file === FALSE) throw new Exception("No se pudo abrir el archivo CSV.");

    $encabezados = fgetcsv($file); 
    $fila = 1;

    // --- 4. Definir qué columnas esperamos ---
    $mapa_columnas = [];
    if ($tipo_carga == 'nuevo') {
        $mapa_columnas = [
            'numero_economico' => 0, 'condicion' => 1, 'placas' => 2, 'vin' => 3, 'Marca' => 4, 'Anio' => 5,
            'id_tecnologia' => 6, 'ID_Conductor' => 7, 'estatus' => 8, 'kilometraje_total' => 9,
            'marca_filtro_aceite_actual' => 10, 'serie_filtro_aceite_actual' => 11,
            'marca_filtro_centrifugo_actual' => 12, 'serie_filtro_centrifugo_actual' => 13, 'lubricante_actual' => 14
        ];
    } else { // 'usado'
        $mapa_columnas = [
            'numero_economico' => 0, 'condicion' => 1, 'placas' => 2, 'vin' => 3, 'Marca' => 4, 'Anio' => 5,
            'id_tecnologia' => 6, 'ID_Conductor' => 7, 'estatus' => 8, 'kilometraje_total' => 9,
            'fecha_ult_mantenimiento' => 10, 'fecha_ult_cambio_aceite' => 11,
            'marca_filtro_aceite_actual' => 12, 'serie_filtro_aceite_actual' => 13,
            'fecha_ult_cambio_centrifugo' => 14, 'marca_filtro_centrifugo_actual' => 15,
            'serie_filtro_centrifugo_actual' => 16, 'lubricante_actual' => 17
        ];
    }

    // --- 5. Leer el CSV línea por línea ---
    while (($columna = fgetcsv($file, 2000, ",")) !== FALSE) {
        $fila++;
        
        try {
            // --- 5a. Obtener datos de la fila ---
            $numero_economico = $columna[$mapa_columnas['numero_economico']];
            $condicion = $columna[$mapa_columnas['condicion']];
            $placas = $columna[$mapa_columnas['placas']];
            $vin = $columna[$mapa_columnas['vin']];
            $marca = $columna[$mapa_columnas['Marca']];
            $anio = $columna[$mapa_columnas['Anio']];
            $id_tecnologia = $columna[$mapa_columnas['id_tecnologia']];
            $id_conductor_interno = $columna[$mapa_columnas['ID_Conductor']];
            $estatus_form = $columna[$mapa_columnas['estatus']];
            $kilometraje = $columna[$mapa_columnas['kilometraje_total']];
            $marca_filtro_aceite = $columna[$mapa_columnas['marca_filtro_aceite_actual']];
            $serie_filtro_aceite = $columna[$mapa_columnas['serie_filtro_aceite_actual']];
            $marca_filtro_cent = $columna[$mapa_columnas['marca_filtro_centrifugo_actual']];
            $serie_filtro_cent = $columna[$mapa_columnas['serie_filtro_centrifugo_actual']];
            $lubricante = $columna[$mapa_columnas['lubricante_actual']];

            // --- (INICIO DE CORRECCIÓN) ---
            // Inicializamos las fechas como null por defecto
            $fecha_mant = null;
            $fecha_filtro_aceite = null;
            $fecha_filtro_cent = null;

            // SOLO si el tipo es 'usado', intentamos leer las fechas
            if ($tipo_carga == 'usado') {
                $fecha_mant_raw = $columna[$mapa_columnas['fecha_ult_mantenimiento']] ?? null;
                $fecha_filtro_aceite_raw = $columna[$mapa_columnas['fecha_ult_cambio_aceite']] ?? null;
                $fecha_filtro_cent_raw = $columna[$mapa_columnas['fecha_ult_cambio_centrifugo']] ?? null;

                $fecha_mant = formatarFecha($fecha_mant_raw);
                $fecha_filtro_aceite = formatarFecha($fecha_filtro_aceite_raw);
                $fecha_filtro_cent = formatarFecha($fecha_filtro_cent_raw);
            }
            // --- (FIN DE CORRECCIÓN) ---

            // --- 5b. Validar y "Traducir" datos ---
            if (empty($numero_economico) || empty($placas) || empty($vin)) {
                throw new Exception("Faltan N° Económico, Placas o VIN.");
            }

            // Buscar ID de conductor
            $id_conductor_numerico = null;
            if (!empty($id_conductor_interno)) {
                $stmt_find_conductor = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? AND role_id = 7 LIMIT 1");
                $stmt_find_conductor->bind_param("s", $id_conductor_interno);
                $stmt_find_conductor->execute();
                $result_conductor = $stmt_find_conductor->get_result();
                if ($result_conductor->num_rows > 0) {
                    $id_conductor_numerico = $result_conductor->fetch_assoc()['id_empleado'];
                } else {
                    throw new Exception("ID Conductor '{$id_conductor_interno}' no encontrado.");
                }
            }

            // Traducir Estatus
            $estatus_db = 'Inactivo';
            switch (strtolower($estatus_form)) {
                case 'activo': $estatus_db = 'Activo'; break;
                case 'en taller': $estatus_db = 'En Taller'; break;
                case 'inactivo': $estatus_db = 'Inactivo'; break;
                case 'vendido': $estatus_db = 'Vendido'; break;
            }

            // --- 5c. Insertar Camión ---
            $sql_camion = "INSERT INTO tb_camiones (
                numero_economico, condicion, placas, vin, id_conductor_asignado, marca, anio, id_tecnologia, estatus, 
                kilometraje_total, fecha_ult_mantenimiento, 
                marca_filtro_aceite_actual, serie_filtro_aceite_actual, fecha_ult_cambio_aceite,
                marca_filtro_centrifugo_actual, serie_filtro_centrifugo_actual, fecha_ult_cambio_centrifugo,
                lubricante_actual
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_camion = $conn->prepare($sql_camion);
            $stmt_camion->bind_param("ssssisidsdssssssss", 
                $numero_economico, $condicion, $placas, $vin, $id_conductor_numerico, 
                $marca, $anio, $id_tecnologia, $estatus_db, $kilometraje, $fecha_mant,
                $marca_filtro_aceite, $serie_filtro_aceite, $fecha_filtro_aceite,
                $marca_filtro_cent, $serie_filtro_cent, $fecha_filtro_cent,
                $lubricante
            );
            $stmt_camion->execute();
            $nuevo_camion_id = $conn->insert_id;

            // --- 5d. Insertar Filtros en Inventario ---
            if (!empty($serie_filtro_aceite)) {
                $stmt_find_aceite = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Aceite' LIMIT 1");
                $stmt_find_aceite->bind_param("s", $marca_filtro_aceite); 
                $stmt_find_aceite->execute();
                $result_aceite = $stmt_find_aceite->get_result();
                $id_cat_filtro_aceite = ($result_aceite->num_rows > 0) ? $result_aceite->fetch_assoc()['id'] : 1; 
                
                $sql_inv_aceite = "INSERT INTO tb_inventario_filtros (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) VALUES (?, ?, ?, 'Instalado', ?)";
                $stmt_inv_aceite = $conn->prepare($sql_inv_aceite);
                $stmt_inv_aceite->bind_param("isii", $id_cat_filtro_aceite, $serie_filtro_aceite, $ubicacion_taller, $nuevo_camion_id);
                $stmt_inv_aceite->execute();
            }
            if (!empty($serie_filtro_cent)) {
                $stmt_find_cent = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Centrifugo' LIMIT 1");
                $stmt_find_cent->bind_param("s", $marca_filtro_cent);
                $stmt_find_cent->execute();
                $result_cent = $stmt_find_cent->get_result();
                $id_cat_filtro_cent = ($result_cent->num_rows > 0) ? $result_cent->fetch_assoc()['id'] : 2; 

                $sql_inv_cent = "INSERT INTO tb_inventario_filtros (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) VALUES (?, ?, ?, 'Instalado', ?)";
                $stmt_inv_cent = $conn->prepare($sql_inv_cent);
                $stmt_inv_cent->bind_param("isii", $id_cat_filtro_cent, $serie_filtro_cent, $ubicacion_taller, $nuevo_camion_id);
                $stmt_inv_cent->execute();
            }
            
            $exitosos++;

        } catch (Exception $e) {
            if (isset($conn->errno) && $conn->errno == 1062) {
                $errores[] = "Fila $fila: Error de Duplicado (VIN, Placa o N° Económico ya existe).";
            } else {
                $errores[] = "Fila $fila: Error -> " . $e->getMessage();
            }
        }
    } // Fin del while
    fclose($file);

    if (empty($errores)) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Proceso completado. $exitosos camiones registrados con éxito."]);
    } else {
        throw new Exception("Proceso completado. $exitosos éxitos. Errores: " . implode(" | ", $errores));
    }

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>