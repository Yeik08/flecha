<?php
/*
* Portal/portal-camiones/registrar_camion_masivo.php
* VERSIÓN V14 (AUDITORÍA):
* - Activa 'auto_detect_line_endings' para leer CSVs de Excel correctamente.
* - Reporta cuántas filas se leyeron realmente para evitar "falsos positivos".
*/

// 1. Configuración vital para leer CSVs de Excel/Mac
ini_set('auto_detect_line_endings', true); 
ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

// --- Seguridad ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}
require_once '../../php/db_connect.php'; 
if ($conn === false) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD.']);
    exit;
}

// --- Validar Archivo ---
if (!isset($_FILES['archivo_camiones']) || $_FILES['archivo_camiones']['error'] != UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error: No se recibió el archivo de camiones.']);
    exit;
}

$csv_file = $_FILES['archivo_camiones']['tmp_name'];
$tipo_carga = $_POST['condicion-archivo'] ?? 'usado';
$id_empleado_registra = $_SESSION['user_id'];

$conn->begin_transaction();
$fila = 0;
$errores = [];
$exitosos = 0;

try {
    function formatarFecha($fecha_str) {
        if (empty($fecha_str)) return null;
        $fecha_str = trim($fecha_str); // Limpieza de espacios
        // Intentamos formato DD/MM/YYYY
        $fecha_obj = DateTime::createFromFormat('d/m/Y', $fecha_str);
        if ($fecha_obj) return $fecha_obj->format('Y-m-d');
        // Intentamos formato YYYY-MM-DD
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_str);
        if ($fecha_obj) return $fecha_obj->format('Y-m-d');
        return null;
    }

    $file = fopen($csv_file, 'r');
    if ($file === FALSE) throw new Exception("No se pudo abrir el archivo CSV.");

    fgetcsv($file); // Omitir encabezados
    $fila = 1;

    // --- Mapas de Columnas ---
    $mapa_columnas = [];
    if ($tipo_carga == 'nuevo') {
        // Mapa para nuevos (15 columnas)
        $mapa_columnas = [
            'numero_economico' => 0, 'condicion' => 1, 'placas' => 2, 'vin' => 3, 'Marca' => 4, 'Anio' => 5,
            'id_tecnologia' => 6, 'ID_Conductor' => 7, 'estatus' => 8, 'kilometraje_total' => 9,
            'marca_filtro_aceite_actual' => 10, 'serie_filtro_aceite_actual' => 11,
            'marca_filtro_centrifugo_actual' => 12, 'serie_filtro_centrifugo_actual' => 13, 'lubricante_actual' => 14
        ];
    } else { 
        // Mapa para usados (18 columnas)
        $mapa_columnas = [
            'numero_economico' => 0, 'condicion' => 1, 'placas' => 2, 'vin' => 3, 'Marca' => 4, 'Anio' => 5,
            'id_tecnologia' => 6, 'ID_Conductor' => 7, 'estatus' => 8, 'kilometraje_total' => 9,
            'fecha_ult_mantenimiento' => 10, 'fecha_ult_cambio_aceite' => 11,
            'marca_filtro_aceite_actual' => 12, 'serie_filtro_aceite_actual' => 13,
            'fecha_ult_cambio_centrifugo' => 14, 'marca_filtro_centrifugo_actual' => 15,
            'serie_filtro_centrifugo_actual' => 16, 'lubricante_actual' => 17
        ];
    }

    while (($columna = fgetcsv($file, 2000, ",")) !== FALSE) {
        // Validamos que la fila no esté vacía (fix para Excel)
        if (count($columna) < 2 || empty($columna[0])) {
            continue; 
        }
        $fila++;
        
        try {
            // Función auxiliar para limpiar datos
            $getVal = function($key) use ($columna, $mapa_columnas) {
                $idx = $mapa_columnas[$key] ?? -1;
                $val = $columna[$idx] ?? null;
                return is_string($val) ? trim($val) : $val;
            };

            $numero_economico = $getVal('numero_economico');
            $condicion = $getVal('condicion');
            $placas = $getVal('placas');
            $vin = $getVal('vin');
            $marca = $getVal('Marca');
            $anio = $getVal('Anio');
            $id_tecnologia = $getVal('id_tecnologia');
            $id_conductor_interno = $getVal('ID_Conductor');
            $estatus_form = $getVal('estatus');
            $kilometraje = $getVal('kilometraje_total');
            
            $marca_filtro_aceite = $getVal('marca_filtro_aceite_actual');
            $serie_filtro_aceite = $getVal('serie_filtro_aceite_actual');
            $marca_filtro_cent = $getVal('marca_filtro_centrifugo_actual');
            $serie_filtro_cent = $getVal('serie_filtro_centrifugo_actual');
            $lubricante = $getVal('lubricante_actual');

            $fecha_mant = null;
            $fecha_filtro_aceite = null;
            $fecha_filtro_cent = null;

            if ($tipo_carga == 'usado') {
                $fecha_mant = formatarFecha($getVal('fecha_ult_mantenimiento'));
                $fecha_filtro_aceite = formatarFecha($getVal('fecha_ult_cambio_aceite'));
                $fecha_filtro_cent = formatarFecha($getVal('fecha_ult_cambio_centrifugo'));
            }

            // Validaciones
            if (empty($numero_economico) || empty($placas) || empty($vin)) {
                throw new Exception("Faltan datos obligatorios (ECO, Placas, VIN).");
            }

            // Buscar Conductor
            $id_conductor_numerico = null;
            if (!empty($id_conductor_interno)) {
                $stmt_find = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_interno = ? AND role_id = 7 LIMIT 1");
                $stmt_find->bind_param("s", $id_conductor_interno);
                $stmt_find->execute();
                $res = $stmt_find->get_result();
                if ($res->num_rows > 0) $id_conductor_numerico = $res->fetch_assoc()['id_empleado'];
            }

            // Traducir Estatus
            $estatus_db = 'Inactivo';
            switch (strtolower($estatus_form)) {
                case 'activo': case 'trabajando': $estatus_db = 'Activo'; break;
                case 'en taller': case 'mantenimiento': $estatus_db = 'En Taller'; break;
                case 'inactivo': $estatus_db = 'Inactivo'; break;
                case 'vendido': $estatus_db = 'Vendido'; break;
            }

            // Insertar Camión
            $sql_camion = "INSERT INTO tb_camiones (
                numero_economico, condicion, placas, vin, id_conductor_asignado, marca, anio, id_tecnologia, estatus, 
                kilometraje_total, fecha_ult_mantenimiento, 
                marca_filtro_aceite_actual, serie_filtro_aceite_actual, fecha_ult_cambio_aceite,
                marca_filtro_centrifugo_actual, serie_filtro_centrifugo_actual, fecha_ult_cambio_centrifugo,
                lubricante_actual
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_camion = $conn->prepare($sql_camion);
            $stmt_camion->bind_param("ssssisiisdssssssss", 
                $numero_economico, $condicion, $placas, $vin, $id_conductor_numerico, 
                $marca, $anio, $id_tecnologia, $estatus_db, $kilometraje, $fecha_mant,
                $marca_filtro_aceite, $serie_filtro_aceite, $fecha_filtro_aceite,
                $marca_filtro_cent, $serie_filtro_cent, $fecha_filtro_cent,
                $lubricante
            );
            
            if (!$stmt_camion->execute()) {
                throw new Exception("Error BD: " . $stmt_camion->error);
            }
            $nuevo_camion_id = $conn->insert_id;

            // Insertar Filtros (Inventario)
            $ubi = 5; 
            if (!empty($serie_filtro_aceite)) {
                $stmt_f = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Aceite' LIMIT 1");
                $stmt_f->bind_param("s", $marca_filtro_aceite);
                $stmt_f->execute();
                $res_f = $stmt_f->get_result();
                $id_cat = ($res_f->num_rows > 0) ? $res_f->fetch_assoc()['id'] : 1; 
                
                $stmt_inv = $conn->prepare("INSERT INTO tb_inventario_filtros (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) VALUES (?, ?, ?, 'Instalado', ?)");
                $stmt_inv->bind_param("isii", $id_cat, $serie_filtro_aceite, $ubi, $nuevo_camion_id);
                $stmt_inv->execute();
            }
            if (!empty($serie_filtro_cent)) {
                $stmt_f = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND tipo_filtro = 'Centrifugo' LIMIT 1");
                $stmt_f->bind_param("s", $marca_filtro_cent);
                $stmt_f->execute();
                $res_f = $stmt_f->get_result();
                $id_cat = ($res_f->num_rows > 0) ? $res_f->fetch_assoc()['id'] : 2;
                
                $stmt_inv = $conn->prepare("INSERT INTO tb_inventario_filtros (id_cat_filtro, numero_serie, id_ubicacion, estatus, id_camion_instalado) VALUES (?, ?, ?, 'Instalado', ?)");
                $stmt_inv->bind_param("isii", $id_cat, $serie_filtro_cent, $ubi, $nuevo_camion_id);
                $stmt_inv->execute();
            }
            
            $exitosos++;

        } catch (Exception $e) {
            if (isset($conn->errno) && $conn->errno == 1062) {
                $errores[] = "Fila $fila: Duplicado ($numero_economico / Placas / VIN).";
            } else {
                $errores[] = "Fila $fila: " . $e->getMessage();
            }
        }
    } // Fin while
    fclose($file);

    // Verificación final: Si no hubo errores pero tampoco éxitos, avisar al usuario
    if ($exitosos == 0 && empty($errores)) {
        echo json_encode(['success' => false, 'message' => 'El archivo parece estar vacío o el formato no es válido. No se insertaron camiones.']);
    } elseif (empty($errores)) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Carga exitosa: $exitosos camiones registrados."]);
    } else {
        $conn->commit(); 
        echo json_encode(['success' => true, 'message' => "Parcialmente exitoso ($exitosos guardados). ERRORES:\n" . implode("\n", $errores)]);
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>