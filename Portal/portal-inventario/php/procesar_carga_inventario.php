<?php
/*
* Portal/portal-inventario/php/procesar_carga_inventario.php
* VERSIÓN V3: Corrección de lógica de ubicación (Prioridad Almacén)
*/

ini_set('auto_detect_line_endings', true);
ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

// 1. Seguridad
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

// 2. Conexión
require_once '../../../php/db_connect.php'; 

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error crítico de conexión BD.']);
    exit;
}

// 3. Validar Archivo
if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] != UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo.']);
    exit;
}

$csv_file = $_FILES['archivo_csv']['tmp_name'];
$tipo_carga = $_POST['tipo_carga'] ?? ''; 

$conn->begin_transaction();
$fila = 0;
$exitosos = 0;
$errores = [];

try {
    $handle = fopen($csv_file, 'r');
    if ($handle === FALSE) throw new Exception("No se pudo leer el archivo.");

    fgetcsv($handle); // Saltar encabezados
    $fila = 1; 

    while (($columna = fgetcsv($handle, 2000, ",")) !== FALSE) {
        $fila++;
        if (empty($columna[0])) continue;

        // Limpiar BOM
        $columna[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $columna[0]);

        // === CASO 1: FILTROS ===
        if ($tipo_carga === 'filtro') {
            
            $marca = trim(strtoupper($columna[0]));
            $parte = trim(strtoupper($columna[1]));
            
            // Detección de columnas (4 o 5)
            if (count($columna) == 4) {
                $serie = trim(strtoupper($columna[2]));
                $almacen_input = trim($columna[3]);
            } else {
                $serie = trim(strtoupper($columna[3]));
                $almacen_input = trim($columna[4]);
            }

            // A. Validar Catálogo
            $stmt_cat = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND numero_parte = ? LIMIT 1");
            $stmt_cat->bind_param("ss", $marca, $parte);
            $stmt_cat->execute();
            $res_cat = $stmt_cat->get_result();

            if ($res_cat->num_rows === 0) {
                $errores[] = "Fila $fila: Filtro '$marca - $parte' no existe en catálogo.";
                continue; 
            }
            $id_filtro = $res_cat->fetch_assoc()['id'];

            // B. Validar Almacén (CORREGIDO)
            $id_ubicacion = resolverUbicacion($conn, $almacen_input);
            if (!$id_ubicacion) {
                $errores[] = "Fila $fila: Almacén '$almacen_input' no encontrado (¿Es un Taller?).";
                continue;
            }

            // C. Insertar
            $sql_ins = "INSERT INTO tb_inventario_filtros (id_cat_filtro, numero_serie, id_ubicacion, estatus) VALUES (?, ?, ?, 'Disponible')";
            $stmt_ins = $conn->prepare($sql_ins);
            $stmt_ins->bind_param("isi", $id_filtro, $serie, $id_ubicacion);
            
            if ($stmt_ins->execute()) {
                $exitosos++;
            } else {
                if ($conn->errno == 1062) {
                    $errores[] = "Fila $fila: Serie '$serie' duplicada.";
                } else {
                    $errores[] = "Fila $fila: Error BD " . $stmt_ins->error;
                }
            }

        // === CASO 2: LUBRICANTES ===
        } elseif ($tipo_carga === 'lubricante') {
            $producto = trim(strtoupper($columna[0]));
            $almacen_input = trim($columna[1]);
            $litros = floatval($columna[2]);

            // A. Validar Producto
            $stmt_lub = $conn->prepare("SELECT id FROM tb_cat_lubricantes WHERE nombre_producto = ? LIMIT 1");
            $stmt_lub->bind_param("s", $producto);
            $stmt_lub->execute();
            $res_lub = $stmt_lub->get_result();

            if ($res_lub->num_rows === 0) {
                $errores[] = "Fila $fila: Producto '$producto' no existe.";
                continue;
            }
            $id_lubricante = $res_lub->fetch_assoc()['id'];

            // B. Validar Almacén
            $id_ubicacion = resolverUbicacion($conn, $almacen_input);
            if (!$id_ubicacion) {
                $errores[] = "Fila $fila: Ubicación '$almacen_input' inválida.";
                continue;
            }

            // C. Upsert
            $sql_upsert = "INSERT INTO tb_inventario_lubricantes (id_cat_lubricante, id_ubicacion, litros_disponibles) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE litros_disponibles = litros_disponibles + VALUES(litros_disponibles)";
            
            $stmt_up = $conn->prepare($sql_upsert);
            $stmt_up->bind_param("iid", $id_lubricante, $id_ubicacion, $litros);
            
            if ($stmt_up->execute()) {
                $exitosos++;
            } else {
                $errores[] = "Fila $fila: Error al guardar lubricante.";
            }
        }
    }
    fclose($handle);

    if ($exitosos > 0) {
        $conn->commit();
        $msj = "Proceso terminado. $exitosos registros exitosos.";
        if (count($errores) > 0) {
            $msj .= "\n\n⚠️ Errores no procesados:\n" . implode("\n", array_slice($errores, 0, 5));
        }
        echo json_encode(['success' => true, 'message' => $msj]);
    } else {
        $conn->rollback();
        $msj_err = "No se guardó nada.";
        if (count($errores) > 0) $msj_err .= "\nErrores:\n" . implode("\n", array_slice($errores, 0, 5));
        echo json_encode(['success' => false, 'message' => $msj_err]);
    }

} catch (Exception $e) {
    if ($conn) $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error Servidor: ' . $e->getMessage()]);
}

// --- FUNCIÓN CORREGIDA ---
// Ahora prioriza tipo = 'Almacén'
function resolverUbicacion($conn, $input) {
    if (empty($input)) return null;

    // 1. Si es ID numérico (ej: "4"), búsqueda directa exacta
    if (is_numeric($input)) {
        $id = intval($input);
        $sql = "SELECT id FROM tb_cat_ubicaciones WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) return $id;
        return null; 
    }

    // 2. Si es texto, búsqueda estricta por TIPO 'Almacén'
    // Esto evita que "Magdalena" seleccione el Taller (ID 1)
    $input = trim($input);
    $like = "%" . $input . "%";
    
    // Aquí está el truco: AND tipo = 'Almacén'
    $sql = "SELECT id FROM tb_cat_ubicaciones WHERE nombre LIKE ? AND tipo = 'Almacén' LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['id'];
    }
    
    // Si no encuentra nada en almacenes, devolvemos null para forzar el error
    // y evitar que se cargue inventario en un taller por accidente.
    return null;
}
?>