<?php
/*
* Portal/portal-inventario/php/procesar_carga_inventario.php
* VERSIÓN V4: Soporte para Cubetas Serializadas (Trazabilidad Total)
*/

ini_set('auto_detect_line_endings', true);
ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

// 1. Seguridad (Admin, Mesa, Almacén)
$roles_permitidos = [1, 2, 6];
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role_id'], $roles_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Rol no autorizado.']);
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
        
        // Limpieza básica de columna vacía
        if (empty($columna[0])) continue;

        // Limpiar BOM (Byte Order Mark) que a veces traen los Excel
        $columna[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $columna[0]);

        // =========================================================
        // CASO 1: FILTROS (Lógica existente - Mantenida)
        // Estructura CSV: Marca, NumeroParte, Serie, Almacen
        // =========================================================
        if ($tipo_carga === 'filtro') {
            
            $marca = trim(strtoupper($columna[0]));
            $parte = trim(strtoupper($columna[1]));
            
            // Ajuste por si el CSV tiene diferente orden de columnas
            if (count($columna) == 4) {
                $serie = trim(strtoupper($columna[2]));
                $almacen_input = trim($columna[3]);
            } else {
                // Fallback por si usan formato viejo de 5 columnas
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
            $id_item_cat = $res_cat->fetch_assoc()['id'];
            $tabla_destino = "tb_inventario_filtros";
            $col_fk = "id_cat_filtro";

        // =========================================================
        // CASO 2: LUBRICANTES (Cubetas Serializadas) - ¡ACTUALIZADO!
        // Estructura CSV: NombreProducto, NumeroSerie, Almacen
        // =========================================================
        } elseif ($tipo_carga === 'lubricante') {
            
            $producto = trim(strtoupper($columna[0])); // Ej: SAE 15W40
            $serie = trim(strtoupper($columna[1]));    // Ej: CUB-LUB-1001
            $almacen_input = trim($columna[2]);        // Ej: Magdalena

            // A. Validar Producto en Catálogo
            // Usamos LIKE para ser un poco flexibles con el nombre
            $stmt_lub = $conn->prepare("SELECT id FROM tb_cat_lubricantes WHERE nombre_producto = ? LIMIT 1");
            $stmt_lub->bind_param("s", $producto);
            $stmt_lub->execute();
            $res_lub = $stmt_lub->get_result();

            if ($res_lub->num_rows === 0) {
                $errores[] = "Fila $fila: El aceite '$producto' no existe en el catálogo.";
                continue;
            }
            $id_item_cat = $res_lub->fetch_assoc()['id'];
            $tabla_destino = "tb_inventario_lubricantes";
            $col_fk = "id_cat_lubricante";
        }

        // =========================================================
        // PROCESO COMÚN DE INSERCIÓN (Para Filtros y Cubetas)
        // =========================================================
        
        // B. Validar Almacén
        $id_ubicacion = resolverUbicacion($conn, $almacen_input);
        if (!$id_ubicacion) {
            $errores[] = "Fila $fila: Almacén '$almacen_input' no válido. Debe ser un Almacén (no Taller).";
            continue;
        }

        // C. Insertar Item Individual
        // Nota: Ambos inventarios tienen la misma estructura básica (id, id_cat_..., serie, ubicacion, estatus)
        $sql_ins = "INSERT INTO $tabla_destino ($col_fk, numero_serie, id_ubicacion, estatus) VALUES (?, ?, ?, 'Disponible')";
        $stmt_ins = $conn->prepare($sql_ins);
        $stmt_ins->bind_param("isi", $id_item_cat, $serie, $id_ubicacion);
        
        try {
            if ($stmt_ins->execute()) {
                $exitosos++;
            } else {
                throw new Exception($stmt_ins->error);
            }
        } catch (Exception $e) {
            // Capturar error de duplicados (Código 1062 en MySQL)
            if ($conn->errno == 1062) {
                $errores[] = "Fila $fila: La serie '$serie' YA EXISTE en el sistema. (Duplicada)";
            } else {
                $errores[] = "Fila $fila: Error BD - " . $e->getMessage();
            }
        }
    }
    fclose($handle);

    if ($exitosos > 0) {
        $conn->commit();
        $msj = "✅ Carga Exitosa: $exitosos ítems registrados.";
        if (count($errores) > 0) {
            $msj .= "\n\n⚠️ Se omitieron algunos errores:\n" . implode("\n", array_slice($errores, 0, 5));
            if(count($errores) > 5) $msj .= "\n... y " . (count($errores)-5) . " más.";
        }
        echo json_encode(['success' => true, 'message' => $msj]);
    } else {
        $conn->rollback();
        $msj_err = "⛔ No se guardó nada.";
        if (count($errores) > 0) $msj_err .= "\nErrores detectados:\n" . implode("\n", array_slice($errores, 0, 10));
        echo json_encode(['success' => false, 'message' => $msj_err]);
    }

} catch (Exception $e) {
    if ($conn) $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error Servidor: ' . $e->getMessage()]);
}

// --- FUNCIÓN DE AYUDA PARA UBICACIÓN ---
function resolverUbicacion($conn, $input) {
    if (empty($input)) return null;

    // 1. Si es número (ID directo)
    if (is_numeric($input)) {
        $id = intval($input);
        // Validamos que sea tipo Almacén para evitar errores
        $sql = "SELECT id FROM tb_cat_ubicaciones WHERE id = ? AND tipo = 'Almacén' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) return $id;
        return null; 
    }

    // 2. Si es texto (Nombre)
    $input = trim($input);
    $like = "%" . $input . "%";
    
    // Busca coincidencias SOLO en Almacenes (Magdalena, Poniente, etc.)
    $sql = "SELECT id FROM tb_cat_ubicaciones WHERE nombre LIKE ? AND tipo = 'Almacén' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['id'];
    }
    return null;
}
?>