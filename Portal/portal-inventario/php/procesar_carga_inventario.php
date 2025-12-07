<?php
/*
* Portal/portal-inventario/php/procesar_carga_inventario.php
* Versión Corregida y Simplificada
*/

// Configuración básica
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
require_once '../../php/db_connect.php'; 

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD.']);
    exit;
}

// 3. Validaciones de Archivo
if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] != UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error: No se recibió el archivo CSV.']);
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
    if ($handle === FALSE) {
        throw new Exception("No se pudo abrir el archivo.");
    }

    // Ignorar encabezados
    fgetcsv($handle); 
    $fila = 1; 

    while (($columna = fgetcsv($handle, 2000, ",")) !== FALSE) {
        $fila++;
        
        // Saltar filas vacías
        if (empty($columna[0])) {
            continue;
        }

        // Limpiar caracteres extraños (BOM) del inicio
        $columna[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $columna[0]);

        // === CASO 1: FILTROS ===
        if ($tipo_carga === 'filtro') {
            // CSV: 0:MARCA, 1:PARTE, 2:TIPO, 3:SERIE, 4:ALMACEN
            $marca = trim(strtoupper($columna[0]));
            $parte = trim(strtoupper($columna[1]));
            $serie = trim(strtoupper($columna[3]));
            $almacen = trim($columna[4]);

            // A. Buscar ID Filtro (Catálogo)
            $stmt_cat = $conn->prepare("SELECT id FROM tb_cat_filtros WHERE marca = ? AND numero_parte = ? LIMIT 1");
            $stmt_cat->bind_param("ss", $marca, $parte);
            $stmt_cat->execute();
            $res_cat = $stmt_cat->get_result();

            if ($res_cat->num_rows === 0) {
                $errores[] = "Fila $fila: Filtro '$marca - $parte' no existe en catálogo.";
                continue; // Saltar a la siguiente fila
            }
            $row_cat = $res_cat->fetch_assoc();
            $id_filtro = $row_cat['id'];

            // B. Buscar Ubicación
            $id_ubicacion = buscarUbicacion($conn, $almacen);
            if (!$id_ubicacion) {
                $errores[] = "Fila $fila: Almacén '$almacen' no válido.";
                continue;
            }

            // C. Insertar
            // Usamos lógica simple para evitar errores rojos en VS Code
            $sql_ins = "INSERT INTO tb_inventario_filtros (id_cat_filtro, numero_serie, id_ubicacion, estatus) VALUES (?, ?, ?, 'Disponible')";
            $stmt_ins = $conn->prepare($sql_ins);
            $stmt_ins->bind_param("isi", $id_filtro, $serie, $id_ubicacion);
            
            // Ejecutamos y verificamos si falla por duplicado
            if ($stmt_ins->execute()) {
                $exitosos++;
            } else {
                if ($conn->errno == 1062) { // Código MySQL para DUPLICATE ENTRY
                    $errores[] = "Fila $fila: Serie '$serie' duplicada.";
                } else {
                    $errores[] = "Fila $fila: Error BD " . $stmt_ins->error;
                }
            }

        // === CASO 2: LUBRICANTES ===
        } elseif ($tipo_carga === 'lubricante') {
            // CSV: 0:PRODUCTO, 1:ALMACEN, 2:LITROS
            $producto = trim(strtoupper($columna[0]));
            $almacen = trim($columna[1]);
            $litros = floatval($columna[2]);

            // A. Buscar Producto
            $stmt_lub = $conn->prepare("SELECT id FROM tb_cat_lubricantes WHERE nombre_producto = ? LIMIT 1");
            $stmt_lub->bind_param("s", $producto);
            $stmt_lub->execute();
            $res_lub = $stmt_lub->get_result();

            if ($res_lub->num_rows === 0) {
                $errores[] = "Fila $fila: Lubricante '$producto' no existe en catálogo.";
                continue;
            }
            $row_lub = $res_lub->fetch_assoc();
            $id_lubricante = $row_lub['id'];

            // B. Buscar Ubicación
            $id_ubicacion = buscarUbicacion($conn, $almacen);
            if (!$id_ubicacion) {
                $errores[] = "Fila $fila: Almacén '$almacen' no válido.";
                continue;
            }

            // C. Actualizar Stock
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

    // --- RESPUESTA FINAL ---
    if ($exitosos > 0) {
        $conn->commit();
        $msj = "Se cargaron $exitosos items exitosamente.";
        if (count($errores) > 0) {
            // Mostramos los primeros 3 errores para no saturar la alerta
            $msj .= "\n\n⚠️ Errores detectados:\n" . implode("\n", array_slice($errores, 0, 3));
            if (count($errores) > 3) $msj .= "\n... y " . (count($errores) - 3) . " más.";
        }
        echo json_encode(['success' => true, 'message' => $msj]);
    } else {
        $conn->rollback();
        $msj_err = "No se cargaron datos.";
        if (count($errores) > 0) {
            $msj_err .= "\nErrores:\n" . implode("\n", array_slice($errores, 0, 5));
        }
        echo json_encode(['success' => false, 'message' => $msj_err]);
    }

} catch (Exception $e) {
    if ($conn) $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error crítico: ' . $e->getMessage()]);
}

// --- FUNCIÓN AUXILIAR (Fuera del try/catch principal) ---
function buscarUbicacion($conn, $texto) {
    $texto = trim($texto);
    if (empty($texto)) return null;

    $sql = "SELECT id FROM tb_cat_ubicaciones WHERE nombre LIKE ? LIMIT 1";
    $like = "%" . $texto . "%";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $fila = $res->fetch_assoc();
        return $fila['id'];
    }
    return null;
}
?>