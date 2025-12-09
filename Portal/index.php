<?php
/*
* Portal/index.php
* DASHBOARD MESA DE MANTENIMIENTO - DATOS EN TIEMPO REAL V2
*/
session_start(); 

// 1. Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.html?error=acceso_denegado");
    exit;
}

$nombre_usuario = $_SESSION['nombre_completo'] ?? 'Usuario'; 

require_once '../php/db_connect.php';

// Inicializar contadores
$kpis = [
    'operando' => 0,
    'taller' => 0,
    'vencidos' => 0,
    'pendientes' => [], // Camiones esperando mecánico
    'terminados' => []  // Camiones terminados HOY
];

$logs = []; // Para el feed de actividad

try {
    // --- 1. LLENADO DE KPIs ---

    // A. Operando
    $res = $conn->query("SELECT COUNT(*) as c FROM tb_camiones WHERE estatus = 'Activo'");
    $kpis['operando'] = $res->fetch_assoc()['c'];

    // B. En Taller (Incluye 'En Proceso' y 'Recibido')
    $res = $conn->query("SELECT COUNT(*) as c FROM tb_entradas_taller WHERE estatus_entrada IN ('Recibido', 'En Proceso')");
    $kpis['taller'] = $res->fetch_assoc()['c'];

    // C. Vencidos
    $res = $conn->query("SELECT COUNT(*) as c FROM tb_camiones WHERE estado_salud = 'Vencido' OR estado_centrifugo = 'Vencido'");
    $kpis['vencidos'] = $res->fetch_assoc()['c'];

    // D. Pendientes de Asignar (El "Cuello de Botella")
    $sql_pen = "SELECT c.numero_economico FROM tb_entradas_taller t 
                JOIN tb_camiones c ON t.id_camion = c.id 
                WHERE t.estatus_entrada = 'Recibido'";
    $res = $conn->query($sql_pen);
    while($r = $res->fetch_assoc()) $kpis['pendientes'][] = $r['numero_economico'];

    // E. Terminados HOY (Productividad del día)
    $hoy = date('Y-m-d');
    $sql_fin = "SELECT c.numero_economico FROM tb_entradas_taller t 
                JOIN tb_camiones c ON t.id_camion = c.id 
                WHERE t.estatus_entrada = 'Entregado' AND DATE(t.fecha_fin_reparacion) = '$hoy'";
    $res = $conn->query($sql_fin);
    while($r = $res->fetch_assoc()) $kpis['terminados'][] = $r['numero_economico'];


    // --- 2. GENERACIÓN DE LOGS (ACTIVIDAD RECIENTE) ---
    
    // Log A: Últimos 3 Ingresos
    $sql_log1 = "SELECT 'ingreso' as tipo, c.numero_economico, t.fecha_ingreso as fecha 
                 FROM tb_entradas_taller t JOIN tb_camiones c ON t.id_camion = c.id 
                 ORDER BY fecha_ingreso DESC LIMIT 3";
    $res = $conn->query($sql_log1);
    while($r = $res->fetch_assoc()) $logs[] = $r;

    // Log B: Últimas 3 Salidas
    $sql_log2 = "SELECT 'salida' as tipo, c.numero_economico, t.fecha_fin_reparacion as fecha 
                 FROM tb_entradas_taller t JOIN tb_camiones c ON t.id_camion = c.id 
                 WHERE t.estatus_entrada = 'Entregado'
                 ORDER BY fecha_fin_reparacion DESC LIMIT 3";
    $res = $conn->query($sql_log2);
    while($r = $res->fetch_assoc()) $logs[] = $r;

    // Ordenar logs por fecha (el más reciente arriba)
    usort($logs, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    // Log C: Alertas de Inventario (Stock Crítico)
    // Checamos si hay filtros con menos de 3 unidades
    $sql_inv = "SELECT cf.tipo_filtro, COUNT(*) as stock 
                FROM tb_inventario_filtros f 
                JOIN tb_cat_filtros cf ON f.id_cat_filtro = cf.id 
                WHERE f.estatus = 'Disponible' 
                GROUP BY cf.tipo_filtro HAVING stock < 3";
    $res_inv = $conn->query($sql_inv);
    $alertas_inv = [];
    while($r = $res_inv->fetch_assoc()) $alertas_inv[] = $r;

} catch (Exception $e) {
    error_log("Error Dashboard: " . $e->getMessage());
}

// Función auxiliar para formato de tiempo "Hace X min"
function tiempo_transcurrido($fecha) {
    if(empty($fecha)) return "";
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;
    
    if ($diferencia < 60) return "hace unos segundos";
    if ($diferencia < 3600) return "hace " . floor($diferencia / 60) . " min";
    if ($diferencia < 86400) return "hace " . floor($diferencia / 3600) . " horas";
    return "hace " . floor($diferencia / 86400) . " días";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel Principal</title>
	  <link rel="shortcut icon" href="img/bus_8502475.png">
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .badge-log { padding: 3px 8px; border-radius: 4px; font-size: 0.75em; font-weight: bold; text-transform: uppercase; margin-right: 8px; }
        .badge-ingreso { background: #e3f2fd; color: #0d47a1; }
        .badge-salida { background: #e8f5e9; color: #1b5e20; }
        .badge-alerta { background: #ffebee; color: #b71c1c; }
        
        /* Ajuste para que las tarjetas de abajo se vean diferentes */
        .card-secondary { background-color: #f8f9fa; border: 1px dashed #ccc; }
        .kpi-title-small { font-size: 0.9em; color: #666; font-weight: bold; }
    </style>
</head>

<body>

    <div class="contenedor-principal">
        <div class="menu-superior">
            <div class="opciones">
                <div class="fr">FLECHA ROJA</div>
                <ul class="menu"><li><a href="/flecha/Portal/portal-camiones/camiones.php">Camiones</a></li></ul>
                <ul class="menu"><li><a href="/flecha/Portal/portal-inventario/alta-inventario.php">Inventario</a></li></ul>
   <!--             <ul class="menu"><li><a href="/flecha/Portal/portal-personal/personal.php">Personal</a></li></ul>-->
            </div>

            <div class="perfil">
                <a href="index.php"><img src="img/cinta_principal2.png" class="img-perfil" /></a>
                <?php echo htmlspecialchars($nombre_usuario); ?>
                <a href="../php/logout.php"><button type="button">Cerrar sesión</button></a>
            </div>
        </div>
    </div>

    <main>
        <div class="bienvenida">
            <h1>¡Hola, <?php echo htmlspecialchars($nombre_usuario); ?>! 👋</h1>
            <p style="color:#666; margin-top:5px;">Aquí tienes el resumen operativo de hoy.</p>
        </div>

        <div class="dashboard-grid">
            
            <div class="card kpi-card" id="operando">
                <h2>FLOTA ACTIVA</h2>
                <p class="kpi-number" style="color:#316960;"><?php echo $kpis['operando']; ?></p>
                <p class="kpi-description">Camiones en ruta</p>
            </div>

            <div class="card kpi-card" id="taller">
                <h2>EN TALLER</h2>
                <p class="kpi-number" style="color:#e67e22;"><?php echo $kpis['taller']; ?></p>
                <p class="kpi-description">Unidades siendo atendidas</p>
            </div>

            <div class="card kpi-card" id="vencidos" style="<?php echo $kpis['vencidos'] > 0 ? 'border-left: 5px solid #e74c3c;' : ''; ?>">
                <h2>VENCIDOS</h2>
                <p class="kpi-number" style="<?php echo $kpis['vencidos'] > 0 ? 'color: #e74c3c;' : ''; ?>">
                    <?php echo $kpis['vencidos']; ?>
                </p>
                <p class="kpi-description">Mantenimientos urgentes</p>
            </div>

            <div class="card kpi-card card-secondary" id="pendientes">
                <div class="kpi-title-small">⏳ ESPERANDO MECÁNICO</div>
                <p class="kpi-number" style="font-size: 2em;"><?php echo count($kpis['pendientes']); ?></p>
                <ul class="camiones-lista <?php echo count($kpis['pendientes'])==0 ? '' : 'oculto'; ?>">
                    <?php if(empty($kpis['pendientes'])) echo "<li>Sin pendientes</li>"; ?>
                    <?php foreach($kpis['pendientes'] as $eco): ?>
                        <li>🚛 <?php echo $eco; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card kpi-card card-secondary" id="terminados">
                <div class="kpi-title-small">✅ FINALIZADOS HOY</div>
                <p class="kpi-number" style="font-size: 2em;"><?php echo count($kpis['terminados']); ?></p>
                <ul class="camiones-lista <?php echo count($kpis['terminados'])==0 ? '' : 'oculto'; ?>">
                    <?php if(empty($kpis['terminados'])) echo "<li>Nada hoy</li>"; ?>
                    <?php foreach($kpis['terminados'] as $eco): ?>
                        <li>🏁 <?php echo $eco; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card widget-large">
                <h2>ESTADO DE LA FLOTA</h2>
                <canvas id="graficaFlota"></canvas>
            </div>

            <div class="card widget-large">
                <h2>BITÁCORA EN VIVO</h2>
                <ul class="activity-feed" style="max-height: 250px; overflow-y: auto;">
                    
                    <?php if(!empty($alertas_inv)): ?>
                        <?php foreach($alertas_inv as $alerta): ?>
                        <li style="border-left: 3px solid red; background:#fff5f5;">
                            <span class="badge-log badge-alerta">STOCK BAJO</span>
                            Quedan pocas unidades de <strong><?php echo $alerta['tipo_filtro']; ?></strong>.
                            <span class="time">Revisar Almacén</span>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if(empty($logs) && empty($alertas_inv)): ?>
                        <li>No hay actividad reciente.</li>
                    <?php endif; ?>

                    <?php foreach($logs as $log): ?>
                        <li>
                            <?php if($log['tipo'] == 'ingreso'): ?>
                                <span class="badge-log badge-ingreso">ENTRADA</span>
                                La unidad <strong><?php echo $log['numero_economico']; ?></strong> ingresó a taller.
                            <?php else: ?>
                                <span class="badge-log badge-salida">SALIDA</span>
                                La unidad <strong><?php echo $log['numero_economico']; ?></strong> finalizó servicio.
                            <?php endif; ?>
                            
                            <span class="time"><?php echo tiempo_transcurrido($log['fecha']); ?></span>
                        </li>
                    <?php endforeach; ?>

                </ul>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // Expandir listas de pendientes/terminados
        const kpiCards = document.querySelectorAll(".kpi-card");
        kpiCards.forEach(card => {
            card.addEventListener("click", (event) => {
                if (event.target.tagName === "LI") return;
                const lista = card.querySelector(".camiones-lista");
                if (lista) {
                    lista.classList.toggle("oculto");
                    card.classList.toggle("expandido");
                }
            });
        });

        // Gráfica
        const ctx = document.getElementById("graficaFlota");
        if (ctx) {
            // Obtener valores limpios del DOM
            const getVal = (id) => parseInt(document.querySelector(`#${id} .kpi-number`)?.textContent || 0);
            
            new Chart(ctx, {
                type: "doughnut",
                data: {
                    labels: ["Activos", "En Taller", "Vencidos"],
                    datasets: [{
                        data: [getVal('operando'), getVal('taller'), getVal('vencidos')],
                        backgroundColor: ["#316960", "#f1c40f", "#e74c3c"],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: "right" }
                    },
                    cutout: '70%'
                }
            });
        }
    });
    </script>

</body>
</html>