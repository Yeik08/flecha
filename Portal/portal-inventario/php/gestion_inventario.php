<?php
/*
* Portal/portal-inventario/php/gestion_inventario.php
* Maneja acciones: Eliminar (Baja) y Editar (Ajustes).
*/

session_start();
header('Content-Type: application/json');

require_once '../../../php/db_connect.php'; 

// 1. Seguridad: Solo Mesa de Mantenimiento (2) y Admin (1)
if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// 2. Recibir datos comunes
$accion = $_POST['accion'] ?? '';
$id = $_POST['id'] ?? null;
$tipo = $_POST['tipo_bien'] ?? ''; // 'Filtro' o 'Lubricante'

if (!$id || !$accion) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

try {
    $conn->begin_transaction();

    // --- ACCIÓN: ELIMINAR (DAR DE BAJA) ---
    if ($accion === 'eliminar') {
        if ($tipo === 'Filtro') {
            // Soft Delete para filtros (Historial)
            $sql = "UPDATE tb_inventario_filtros SET estatus = 'Baja' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
        } elseif ($tipo === 'Lubricante') {
            // Hard Delete para lubricantes (Error de carga o vaciado)
            $sql = "DELETE FROM tb_inventario_lubricantes WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item eliminado/baja correctamente.']);
    } 
    
    // --- ACCIÓN: EDITAR (MOVER O AJUSTAR) ---
    elseif ($accion === 'editar') {
        
        $nueva_ubicacion = $_POST['id_nueva_ubicacion'] ?? null;
        
        if (!$nueva_ubicacion) {
            throw new Exception("Debes seleccionar una ubicación válida.");
        }

        if ($tipo === 'Filtro') {
            // Filtros: Solo movemos de ubicación
            $sql = "UPDATE tb_inventario_filtros SET id_ubicacion = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nueva_ubicacion, $id);
            
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Filtro reubicado correctamente.']);
            } else {
                throw new Exception("Error al mover filtro: " . $stmt->error);
            }

        } elseif ($tipo === 'Lubricante') {
            // Lubricantes: Ajustamos Ubicación y Litros
            $nuevos_litros = $_POST['nuevos_litros'] ?? null;
            
            if ($nuevos_litros === null || $nuevos_litros < 0) {
                throw new Exception("La cantidad de litros no es válida.");
            }

            $sql = "UPDATE tb_inventario_lubricantes SET id_ubicacion = ?, litros_disponibles = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idi", $nueva_ubicacion, $nuevos_litros, $id);

            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Inventario de lubricante ajustado.']);
            } else {
                throw new Exception("Error al ajustar lubricante: " . $stmt->error);
            }
        }
    } else {
        throw new Exception("Acción desconocida.");
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>