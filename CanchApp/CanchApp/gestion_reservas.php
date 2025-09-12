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

// cancelar la reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {
    $id_reserva = $_POST['id_reserva'] ?? '';
    
    if (!empty($id_reserva)) {
        try {
            // Verificar que la reserva pertenece a una cancha del dueño
            $stmt = $pdo->prepare("
                SELECT r.* FROM reserva r
                INNER JOIN cancha c ON r.id_cancha = c.id_cancha
                WHERE r.id_reserva = ? AND c.id_duenio = ?
            ");
            $stmt->execute([$id_reserva, $id_duenio]);
            
            if ($stmt->fetch()) {
                // Eliminar la reserva
                $stmt = $pdo->prepare("DELETE FROM reserva WHERE id_reserva = ?");
                $stmt->execute([$id_reserva]);
                $msg = "Reserva cancelada correctamente.";
            } else {
                $error = "No tienes permisos para cancelar esta reserva.";
            }
        } catch (PDOException $e) {
            $error = "Error al cancelar la reserva: " . $e->getMessage();
        }
    }
}

// Obtener todas las reservas de las canchas del dueño
function obtenerreservasduenio($pdo, $id_duenio, $fecha_desde = null) {
    $fecha_desde = $fecha_desde ?: date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT 
            r.id_reserva,
            r.fecha,
            r.hora_inicio,
            r.hora_final,
            c.nombre as cancha_nombre,
            c.lugar as cancha_lugar,
            u.nombre as usuario_nombre,
            u.email as usuario_email
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        WHERE c.id_duenio = ? AND r.fecha >= ?
        ORDER BY r.fecha ASC, r.hora_inicio ASC
    ");
    $stmt->execute([$id_duenio, $fecha_desde]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener estadísticas
function obtenerestadisticas($pdo, $id_duenio) {
    // Total de reservas este mes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_mes
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE c.id_duenio = ? 
        AND YEAR(r.fecha) = YEAR(CURDATE()) 
        AND MONTH(r.fecha) = MONTH(CURDATE())
    ");
    $stmt->execute([$id_duenio]);
    $total_mes = $stmt->fetchColumn();
    
    // Reservas hoy
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_hoy
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE c.id_duenio = ? AND r.fecha = CURDATE()
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
    ");
    $stmt->execute([$id_duenio]);
    $proximas = $stmt->fetchColumn();
    
    return [
        'total_mes' => $total_mes,
        'total_hoy' => $total_hoy,
        'proximas' => $proximas
    ];
}

$reservas = obtenerreservasduenio($pdo, $id_duenio, $_GET['desde'] ?? null);
$estadisticas = obtenerestadisticas($pdo, $id_duenio);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - Dueño</title>
    <link rel="stylesheet" href="css/gestion.css">
</head>
<body>
    <div class="container">
        <h1>📊 Gestión de Reservas</h1>
        
        <?php if (!empty($msg)): ?>
            <div class="mensaje success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
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
                <div class="stat-label">Este mes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($miscanchas) ?></div>
                <div class="stat-label">Mis canchas</div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="fecha-filtro">
            <h3>🔍 Filtrar reservas</h3>
            <form method="get" style="display: inline-block;">
                <input type="date" name="desde" value="<?= $_GET['desde'] ?? date('Y-m-d') ?>" 
                       onchange="this.form.submit()">
                <button type="submit" class="btn btn-primary">Filtrar desde esta fecha</button>
            </form>
            <a href="?" class="btn btn-primary">Ver todas las próximas</a>
        </div>
        
        <!-- Lista de reservas -->
        <h2>📋 Reservas de mis canchas</h2>
        
        <?php if (!empty($reservas)): ?>
            <table class="reservas-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Cancha</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <?php
                        $fecha_reserva = $reserva['fecha'];
                        $fecha_actual = date('Y-m-d');
                        $estado_clase = '';
                        $estado_texto = '';
                        
                        if ($fecha_reserva == $fecha_actual) {
                            $estado_clase = 'estado-hoy';
                            $estado_texto = 'HOY';
                        } elseif ($fecha_reserva <= date('Y-m-d', strtotime('+7 days'))) {
                            $estado_clase = 'estado-proxima';
                            $estado_texto = 'PRÓXIMA';
                        } else {
                            $estado_clase = 'estado-futura';
                            $estado_texto = 'FUTURA';
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?= date('d/m/Y', strtotime($reserva['fecha'])) ?></strong><br>
                                <small style="color: #666;"><?= ucfirst(date('l', strtotime($reserva['fecha']))) ?></small>
                            </td>
                            <td>
                                <strong><?= substr($reserva['hora_inicio'], 0, 5) ?> - <?= substr($reserva['hora_final'], 0, 5) ?></strong><br>
                                <small style="color: #666;"><?php 
                                    $inicio = new DateTime($reserva['hora_inicio']);
                                    $final = new DateTime($reserva['hora_final']);
                                    $duracion = $inicio->diff($final);
                                    echo $duracion->h . 'h ' . $duracion->i . 'm';
                                ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($reserva['cancha_nombre']) ?></strong><br>
                                <small class="cancha-info"><?= htmlspecialchars($reserva['cancha_lugar']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($reserva['usuario_nombre']) ?></strong><br>
                                <small style="color: #666;"><?= htmlspecialchars($reserva['usuario_email']) ?></small>
                            </td>
                            <td>
                                <span class="estado-reserva <?= $estado_clase ?>"><?= $estado_texto ?></span>
                            </td>
                            <td>
                                <form method="post" style="display:inline;" 
                                      onsubmit="return confirm('¿Seguro que quieres cancelar esta reserva?');">
                                    <input type="hidden" name="id_reserva" value="<?= $reserva['id_reserva'] ?>">
                                    <button type="submit" name="cancelar_reserva" class="btn btn-danger">
                                        Cancelar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-reservas">
                <h3>No hay reservas</h3>
                <p>No tienes reservas <?= isset($_GET['desde']) ? 'desde la fecha seleccionada' : 'próximas' ?>.</p>
            </div>
        <?php endif; ?>
        
        <!-- Resumen de canchas -->
        <?php if (!empty($miscanchas)): ?>
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3>Mis canchas (<?= count($miscanchas) ?>)</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
                    <?php foreach ($miscanchas as $cancha): ?>
                        <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;">
                            <strong><?= htmlspecialchars($cancha['nombre']) ?></strong><br>
                            <small><?= htmlspecialchars($cancha['lugar']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
    
    <p style="text-align: center;">
        <a href="index.php">Volver al inicio</a> 
        <a href="cancha.php">Ver todas las canchas</a> 
        <a href="dueño.php">Crear nueva cancha</a>
    </p>
</body>
</html>