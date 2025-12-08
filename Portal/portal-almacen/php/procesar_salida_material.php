<?php
session_start();
header('Content-Type: application/json');
require_once '../../../php/db_connect.php';

if (!isset($_SESSION['loggedin']) || ($_SESSION['role_id'] != 6 && $_SESSION['role_id'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']); exit;
}

$conn->begin_transaction();

try {
    // 1. Recibir Datos
    $id_entrada = $_POST['id_entrada'] ?? null;
    $id_camion = $_POST['id_camion'] ?? null;
    
    // Filtros Viejos (Input del almacenista)
    $viejo_aceite_input = trim($_POST['filtro_viejo_serie'] ?? ''); // Aceite
    // $viejo_cent_input = trim($_POST['filtro_viejo_centrifugo'] ?? ''); // Opcional si aplica

    // Material Nuevo (Input del almacenista)
    $nuevo_aceite = trim($_POST['filtro_nuevo_serie'] ?? '');
    $nuevo_centrifugo = trim($_POST['filtro_nuevo_centrifugo'] ?? ''); // Opcional
    $cubeta1 = trim($_POST['cubeta_1'] ?? '');
    $cubeta2 = trim($_POST['cubeta_2'] ?? '');

    if (!$id_entrada || !$id_camion) throw new Exception("Faltan datos de la orden.");

    // 2. VALIDACIÓN DE FILTROS VIEJOS (Seguridad)
    // Obtenemos lo que la BD dice que tiene el camión
    $sql_camion = "SELECT serie_filtro_aceite_actual FROM tb_camiones WHERE id = ?";
    $stmt = $conn->prepare($sql_camion);
    $stmt->bind_param("i", $id_camion);
    $stmt->execute();
    $datos_camion = $stmt->get_result()->fetch_assoc();

    // Comparamos (Si hay un filtro registrado, debe coincidir)
    if (!empty($datos_camion['serie_filtro_aceite_actual'])) {
        if (strtoupper($viejo_aceite_input) !== strtoupper($datos_camion['serie_filtro_aceite_actual'])) {
            throw new Exception("⛔ ERROR CRÍTICO: El filtro de aceite devuelto ($viejo_aceite_input) NO COINCIDE con el registrado en el sistema (" . $datos_camion['serie_filtro_aceite_actual'] . ").");
        }
    }

    // 3. VALIDACIÓN Y ASIGNACIÓN DE MATERIAL NUEVO
    
    // Función auxiliar para validar inventario
    function validarYReservar($conn, $serie, $tabla, $campo_id_entrada, $id_entrada) {
        if (empty($serie)) return;

        // Verificar disponibilidad
        $sql = "SELECT id, estatus FROM $tabla WHERE numero_serie = ? LIMIT 1 FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serie);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) throw new Exception("El item '$serie' no existe en inventario.");
        $item = $res->fetch_assoc();
        
        if ($item['estatus'] !== 'Disponible') throw new Exception("El item '$serie' no está disponible (Estatus: {$item['estatus']}).");

        // Reservar (Cambiar a 'Asignado')
        $sql_up = "UPDATE $tabla SET estatus = 'Asignado' WHERE id = ?";
        $stmt_up = $conn->prepare($sql_up);
        $stmt_up->bind_param("i", $item['id']);
        $stmt_up->execute();

        // Vincular al Ticket
        $sql_ticket = "UPDATE tb_entradas_taller SET $campo_id_entrada = ? WHERE id = ?";
        $stmt_t = $conn->prepare($sql_ticket);
        $stmt_t->bind_param("si", $serie, $id_entrada);
        $stmt_t->execute();
    }

    // Procesar Filtro Aceite Nuevo
    validarYReservar($conn, $nuevo_aceite, 'tb_inventario_filtros', 'filtro_aceite_entregado', $id_entrada);
    
    // Procesar Filtro Centrífugo Nuevo
    validarYReservar($conn, $nuevo_centrifugo, 'tb_inventario_filtros', 'filtro_centrifugo_entregado', $id_entrada);

    // Procesar Cubetas
    validarYReservar($conn, $cubeta1, 'tb_inventario_lubricantes', 'cubeta_1_entregada', $id_entrada);
    validarYReservar($conn, $cubeta2, 'tb_inventario_lubricantes', 'cubeta_2_entregada', $id_entrada);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Material validado y entregado al mecánico correctamente.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
