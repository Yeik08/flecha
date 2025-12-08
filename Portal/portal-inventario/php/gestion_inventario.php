<?php
/*
* Portal/portal-inventario/php/gestion_inventario.php
* VERSIÓN FINAL: Permisos para Almacén + Lógica de Cubetas
*/

session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

// 1. SEGURIDAD: Ahora permitimos Rol 6 (Almacén)
$roles_permitidos = [1, 2, 6];

if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role_id'], $roles_permitidos)) {
    echo json_encode(['success' => false, 'message' => '❌ Acceso denegado. No tienes permisos para modificar el inventario.']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$id = $_POST['id'] ?? '';
$tipo_bien = $_POST['tipo_bien'] ?? ''; // 'Filtro' o 'Lubricante'

if (empty($id) || empty($tipo_bien)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos identificadores.']);
    exit;
}

// Determinar tabla según el tipo
if ($tipo_bien === 'Filtro') {
    $tabla = 'tb_inventario_filtros';
} elseif ($tipo_bien === 'Lubricante') {
    $tabla = 'tb_inventario_lubricantes';
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo de bien desconocido.']);
    exit;
}

try {
    // =================================================================
    // CASO A: ELIMINAR (DAR DE BAJA)
    // =================================================================
    if ($accion === 'eliminar') {
        // No borramos el registro (DELETE), solo cambiamos estatus a 'Baja' para mantener historial
        $sql = "UPDATE $tabla SET estatus = 'Baja' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Ítem dado de baja correctamente.']);
        } else {
            throw new Exception("Error al actualizar BD.");
        }
    }

    // =================================================================
    // CASO B: EDITAR (CAMBIAR UBICACIÓN)
    // =================================================================
    elseif ($accion === 'editar') {
        $nueva_ubicacion = $_POST['id_nueva_ubicacion'] ?? '';
        
        if (empty($nueva_ubicacion)) {
            throw new Exception("Debes seleccionar una ubicación.");
        }

        // Solo actualizamos la ubicación. 
        // Nota: Ya no actualizamos litros porque ahora son cubetas unitarias.
        $sql = "UPDATE $tabla SET id_ubicacion = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $nueva_ubicacion, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Ubicación actualizada correctamente.']);
        } else {
            throw new Exception("Error al actualizar ubicación.");
        }
    } 
    else {
        throw new Exception("Acción no válida.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>