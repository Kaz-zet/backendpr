<?php
session_start();
require_once 'conexiones/conDB.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'duenio') {
    die("Solo los dueños pueden acceder a esta página.");
}

$id_duenio = $_SESSION['id'];
$msg = '';
$error = '';

// Obtener canchas del dueño
try {
    $stmt = $pdo->prepare("SELECT * FROM cancha WHERE id_duenio = ? ORDER BY nombre");
    $stmt->execute([$id_duenio]);
    $miscanchas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar canchas: " . $e->getMessage();
    $miscanchas = [];
}

// Cancelar reserva (desde el lado del dueño)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {
    $id_reserva = $_POST['id_reserva'] ?? '';
    
    if (!empty($id_reserva)) {
        try {
            // Verificar que la reserva pertenece a una cancha del dueño
            $stmt = $pdo->prepare("
                SELECT r.* FROM reserva r
                INNER JOIN cancha c ON r.id_cancha = c.id_cancha
                WHERE r.id_reserva = ? AND c.id_duenio = ? AND r.estado = 'activa'
            ");
            $stmt->execute([$id_reserva, $id_duenio]);
            $reserva = $stmt->fetch();
            
            if ($reserva) {
                // Cambiar estado a cancelada
                $stmt = $pdo->prepare("UPDATE reserva SET estado = 'cancelada' WHERE id_reserva = ?");
                $stmt->execute([$id_reserva]);
                
                $msg = "Reserva cancelada correctamente. Código: " . $reserva['codigo_reserva'];
            } else {
                $error = "No tienes permisos para cancelar esta reserva o ya está cancelada.";
            }
        } catch (PDOException $e) {
            $error = "Error al cancelar la reserva: " . $e->getMessage();
        }
    }
}

// FUNCIÓN CORREGIDA: Obtener reservas del dueño
function obtenerreservasduenio($pdo, $id_duenio, $fecha_desde = null, $filtro_estado = 'todas') {
    $fecha_desde = $fecha_desde ?: date('Y-m-d');
    
    $sql = "
        SELECT 
            r.id_reserva,
            r.codigo_reserva,
            r.fecha,
            r.hora_inicio,
            r.hora_final,
            r.espacios_reservados,
            r.telefono,
            r.observaciones,
            r.estado,
            c.nombre as cancha_nombre,
            c.lugar as cancha_lugar,
            u.nombre as usuario_nombre,
            u.email as usuario_email
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        WHERE c.id_duenio = ? AND r.fecha >= ?
    ";
    
    $params = [$id_duenio, $fecha_desde];
    
    if ($filtro_estado !== 'todas') {
        $sql .= " AND r.estado = ?";
        $params[] = $filtro_estado;
    }
    
    $sql .= " ORDER BY r.fecha ASC, r.hora_inicio ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// FUNCIÓN CORREGIDA: Obtener estadísticas
function obtenerestadisticas($pdo, $id_duenio) {
    // Total de reservas activas este mes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_mes
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE c.id_duenio = ? 
        AND YEAR(r.fecha) = YEAR(CURDATE()) 
        AND MONTH(r.fecha) = MONTH(CURDATE())
        AND r.estado = 'activa'
    ");
    $stmt->execute([$id_duenio]);
    $total_mes = $stmt->fetchColumn();
    
    // Reservas hoy
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_hoy
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE c.id_duenio = ? AND r.fecha = CURDATE() AND r.estado = 'activa'
    ");
    $stmt->execute([$id_duenio]);
    $total_hoy = $stmt->fetchColumn();
    
    // Próximas reservas (próximos 7 días)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as proximas
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE c.id_duenio = ? 
        AND r.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND r.estado = 'activa'
    ");
    $stmt->execute([$id_duenio]);
    $proximas = $stmt->fetchColumn();
    
    // Total canceladas este mes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as canceladas
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE c.id_duenio = ? 
        AND YEAR(r.fecha) = YEAR(CURDATE()) 
        AND MONTH(r.fecha) = MONTH(CURDATE())
        AND r.estado = 'cancelada'
    ");
    $stmt->execute([$id_duenio]);
    $canceladas = $stmt->fetchColumn();
    
    // NUEVA ESTADÍSTICA: Total de espacios reservados este mes
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(r.espacios_reservados), 0) as espacios_mes
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE c.id_duenio = ? 
        AND YEAR(r.fecha) = YEAR(CURDATE()) 
        AND MONTH(r.fecha) = MONTH(CURDATE())
        AND r.estado = 'activa'
    ");
    $stmt->execute([$id_duenio]);
    $espacios_mes = $stmt->fetchColumn();
    
    return [
        'total_mes' => $total_mes,
        'total_hoy' => $total_hoy,
        'proximas' => $proximas,
        'canceladas' => $canceladas,
        'espacios_mes' => $espacios_mes
    ];
}

// NUEVA FUNCIÓN: Obtener ocupación de horarios por cancha y fecha
function obtenerOcupacionPorHorario($pdo, $id_duenio, $fecha = null) {
    $fecha = $fecha ?: date('Y-m-d');
    
    $sql = "
        SELECT 
            c.nombre as cancha_nombre,
            TIME_FORMAT(r.hora_inicio, '%H:%i') as hora,
            SUM(r.espacios_reservados) as espacios_ocupados,
            (4 - SUM(r.espacios_reservados)) as espacios_disponibles,
            GROUP_CONCAT(CONCAT(u.nombre, ' (', r.espacios_reservados, ')') SEPARATOR ', ') as usuarios
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        WHERE c.id_duenio = ? AND r.fecha = ? AND r.estado = 'activa'
        GROUP BY c.id_cancha, r.hora_inicio
        HAVING espacios_ocupados > 0
        ORDER BY c.nombre, r.hora_inicio
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_duenio, $fecha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$filtro_estado = $_GET['estado'] ?? 'activa';
$reservas = obtenerreservasduenio($pdo, $id_duenio, $_GET['desde'] ?? null, $filtro_estado);
$estadisticas = obtenerestadisticas($pdo, $id_duenio);
$ocupacion_hoy = obtenerOcupacionPorHorario($pdo, $id_duenio);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - Dueño</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        
        .content {
            padding: 30px;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* ESTADÍSTICAS MEJORADAS */
        .estadisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stat-card.espacios {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        /* OCUPACIÓN DE HOY - NUEVA SECCIÓN */
        .ocupacion-hoy {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        .ocupacion-hoy h3 {
            color: #495057;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .horario-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 12px;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .horario-info {
            flex: 1;
        }
        
        .cancha-nombre {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .hora-slot {
            color: #6c757d;
            font-size: 14px;
        }
        
        .espacios-visual-mini {
            display: flex;
            gap: 3px;
            margin-left: 15px;
        }
        
        .espacio-mini {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .espacio-ocupado-mini {
            background: #dc3545;
            color: white;
        }
        
        .espacio-disponible-mini {
            background: #28a745;
            color: white;
        }
        
        .usuarios-info {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .filtros {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .filtros h3 {
            margin: 0 0 15px 0;
            color: #495057;
        }
        
        .filtros-grid {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .reservas-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .reservas-table th {
            background: #495057;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .reservas-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .reservas-table tr:hover {
            background: #f8f9fa;
        }
        
        .codigo-reserva {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: bold;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
            display: inline-block;
        }
        
        .estado-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-activa {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* NUEVA CLASE PARA MOSTRAR ESPACIOS */
        .espacios-info-table {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .cancha-info {
            font-weight: 600;
            color: #495057;
        }
        
        .cliente-info {
            color: #6c757d;
            font-size: 14px;
        }
        
        .no-reservas {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-reservas h3 {
            margin-bottom: 10px;
        }
        
        .filtro-activo {
            background: #28a745 !important;
            color: white !important;
        }
        
        input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .estadisticas {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filtros-grid {
                flex-direction: column;
                align-items: stretch;
            }
            
            .reservas-table {
                font-size: 14px;
            }
            
            .reservas-table th,
            .reservas-table td {
                padding: 10px 8px;
            }
            
            .horario-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Gestión de Reservas</h1>
            <p>Panel de control para dueños de canchas</p>
        </div>
        
        <div class="content">
            <?php if (!empty($msg)): ?>
                <div class="mensaje success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- ESTADÍSTICAS CORREGIDAS -->
            <div class="estadisticas">
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_hoy'] ?></div>
                    <div class="stat-label">Reservas Hoy</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['proximas'] ?></div>
                    <div class="stat-label">Próximos 7 días</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['total_mes'] ?></div>
                    <div class="stat-label">Reservas este mes</div>
                </div>
                <div class="stat-card espacios">
                    <div class="stat-number"><?= $estadisticas['espacios_mes'] ?></div>
                    <div class="stat-label">Espacios reservados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['canceladas'] ?></div>
                    <div class="stat-label">Canceladas este mes</div>
                </div>
            </div>
            
            <!-- NUEVA SECCIÓN: Ocupación de hoy -->
            <?php if (!empty($ocupacion_hoy)): ?>
            <div class="ocupacion-hoy">
                <h3>🕒 Ocupación de hoy - <?= date('d/m/Y') ?></h3>
                <?php foreach ($ocupacion_hoy as $ocupacion): ?>
                    <div class="horario-item">
                        <div class="horario-info">
                            <div class="cancha-nombre"><?= htmlspecialchars($ocupacion['cancha_nombre']) ?></div>
                            <div class="hora-slot">🕒 <?= $ocupacion['hora'] ?> - <?= date('H:i', strtotime($ocupacion['hora'] . ' +1 hour')) ?></div>
                            <div class="usuarios-info">👥 <?= htmlspecialchars($ocupacion['usuarios']) ?></div>
                        </div>
                        <div>
                            <div style="text-align: center; margin-bottom: 8px;">
                                <strong><?= $ocupacion['espacios_ocupados'] ?>/4 espacios</strong>
                            </div>
                            <div class="espacios-visual-mini">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <div class="espacio-mini <?= $i <= $ocupacion['espacios_ocupados'] ? 'espacio-ocupado-mini' : 'espacio-disponible-mini' ?>">
                                        <?= $i <= $ocupacion['espacios_ocupados'] ? '●' : '○' ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="filtros">
                <h3>🔍 Filtros de búsqueda</h3>
                <div class="filtros-grid">
                    <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <label>Desde fecha:</label>
                        <input type="date" name="desde" value="<?= $_GET['desde'] ?? date('Y-m-d') ?>">
                        
                        <label>Estado:</label>
                        <a href="?desde=<?= $_GET['desde'] ?? date('Y-m-d') ?>&estado=activa" 
                           class="btn <?= $filtro_estado === 'activa' ? 'filtro-activo' : '' ?>">Activas</a>
                        <a href="?desde=<?= $_GET['desde'] ?? date('Y-m-d') ?>&estado=cancelada" 
                           class="btn <?= $filtro_estado === 'cancelada' ? 'filtro-activo' : '' ?>">Canceladas</a>
                        <a href="?desde=<?= $_GET['desde'] ?? date('Y-m-d') ?>&estado=todas" 
                           class="btn <?= $filtro_estado === 'todas' ? 'filtro-activo' : '' ?>">Todas</a>
                        
                        <button type="submit" class="btn">Aplicar fecha</button>
                        <a href="?" class="btn btn-secondary">Limpiar</a>
                    </form>
                </div>
            </div>
            
            <!-- Lista de reservas CORREGIDA -->
            <h2>📋 Reservas de mis canchas</h2>
            
            <?php if (!empty($reservas)): ?>
                <table class="reservas-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha y Hora</th>
                            <th>Cancha</th>
                            <th>Cliente</th>
                            <th>Espacios</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <?php
                            $fecha_reserva = $reserva['fecha'];
                            $fecha_actual = date('Y-m-d');
                            ?>
                            <tr>
                                <td>
                                    <div class="codigo-reserva">
                                        <?= htmlspecialchars($reserva['codigo_reserva']) ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($reserva['fecha'])) ?></strong><br>
                                    <span style="color: #666; font-size: 12px;">
                                        <?php
                                        $dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado'];
                                        echo $dias[date('l', strtotime($reserva['fecha']))];
                                        ?>
                                    </span><br>
                                    <strong><?= substr($reserva['hora_inicio'], 0, 5) ?> - <?= substr($reserva['hora_final'], 0, 5) ?></strong>
                                </td>
                                <td>
                                    <div class="cancha-info"><?= htmlspecialchars($reserva['cancha_nombre']) ?></div>
                                    <small style="color: #666;">📍 <?= htmlspecialchars($reserva['cancha_lugar']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($reserva['usuario_nombre']) ?></strong><br>
                                    <div class="cliente-info">📧 <?= htmlspecialchars($reserva['usuario_email']) ?></div>
                                    <?php if ($reserva['telefono']): ?>
                                        <div class="cliente-info">📞 <?= htmlspecialchars($reserva['telefono']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($reserva['observaciones']): ?>
                                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                            <strong>💬 Obs:</strong> <?= htmlspecialchars($reserva['observaciones']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="espacios-info-table">
                                        🎾 <?= $reserva['espacios_reservados'] ?>/4 espacios
                                    </div>
                                </td>
                                <td>
                                    <span class="estado-badge estado-<?= $reserva['estado'] ?>">
                                        <?= strtoupper($reserva['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reserva['estado'] === 'activa' && $fecha_reserva >= $fecha_actual): ?>
                                        <form method="post" style="display:inline;" 
                                              onsubmit="return confirm('¿Seguro que quieres cancelar esta reserva?\n\nCódigo: <?= $reserva['codigo_reserva'] ?>\nCliente: <?= htmlspecialchars($reserva['usuario_nombre']) ?>\nEspacios: <?= $reserva['espacios_reservados'] ?>/4');">
                                            <input type="hidden" name="id_reserva" value="<?= $reserva['id_reserva'] ?>">
                                            <button type="submit" name="cancelar_reserva" class="btn btn-danger">
                                                ❌ Cancelar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #666; font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-reservas">
                    <h3>No hay reservas</h3>
                    <p>No tienes reservas <?= $filtro_estado !== 'todas' ? strtolower($filtro_estado) . 's' : '' ?> 
                    <?= isset($_GET['desde']) ? 'desde la fecha seleccionada' : 'próximas' ?>.</p>
                </div>
            <?php endif; ?>
            
            <!-- Resumen de canchas -->
            <?php if (!empty($miscanchas)): ?>
                <div style="margin-top: 40px; padding: 25px; background: #f8f9fa; border-radius: 10px;">
                    <h3>🏟️ Mis canchas (<?= count($miscanchas) ?>)</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;">
                        <?php foreach ($miscanchas as $cancha): ?>
                            <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                                <strong>🎾 <?= htmlspecialchars($cancha['nombre']) ?></strong><br>
                                <small style="color: #666;">📍 <?= htmlspecialchars($cancha['lugar']) ?></small><br>
                                <small style="color: #666;">👥 Capacidad: 4 jugadores (Padel)</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <p style="text-align: center; margin-top: 30px;">
        <a href="index.php" class="btn">🏠 Volver al inicio</a> 
        <a href="calendario.php" class="btn">👁️ Ver calendario</a> 
        <a href="dueño.php" class="btn">➕ Crear nueva cancha</a>
    </p>
</body>
</html>
            