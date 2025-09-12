<?php
session_start();
require_once 'conexiones/conDB.php';

// Función para convertir día de la semana a español
function diasespanol($dia_ingles) {
    $dias = [
        'Mon' => 'Lun',
        'Tue' => 'Mar', 
        'Wed' => 'Mié',
        'Thu' => 'Jue',
        'Fri' => 'Vie',
        'Sat' => 'Sáb',
        'Sun' => 'Dom'
    ];
    return $dias[$dia_ingles] ?? $dia_ingles;
}

$id_cancha = $_GET['id'] ?? null;
$id_usuario = $_SESSION['id'] ?? null;

if (!$id_cancha) {
    header("Location: cancha.php");
    exit;
}

$msg = '';
$error = '';

// Fecha para mostrar (por defecto hoy)
$fecha_mostrar = $_GET['fecha'] ?? date('Y-m-d');

// Procesar reserva RÁPIDA (un clic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_rapida']) && $id_usuario) {
    $fecha = $_POST['fecha'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    
    if (empty($fecha) || empty($hora_inicio)) {
        $error = "Datos incompletos para la reserva.";
    } else {
        // Calcular hora final automáticamente (1 hora después)
        $hora_final = date('H:i', strtotime($hora_inicio . ' +1 hour'));
        
        // Validar que la fecha no esté atrasada
        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i');
        
        if ($fecha < $fecha_actual) {
            $error = "No puedes reservar en fechas ya pasadas.";
        } elseif ($fecha === $fecha_actual && $hora_inicio <= $hora_actual) {
            $error = "No puedes reservar en horarios que ya pasaron hoy.";
        } else {
            try {
                // Verificar si ya existe una reserva en ese horario
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM reserva 
                    WHERE id_cancha = ? AND fecha = ? 
                    AND ((hora_inicio <= ? AND hora_final > ?) 
                    OR (hora_inicio < ? AND hora_final >= ?)
                    OR (hora_inicio >= ? AND hora_final <= ?))
                ");
                $stmt->execute([
                    $id_cancha, $fecha, 
                    $hora_inicio, $hora_inicio,
                    $hora_final, $hora_final,
                    $hora_inicio, $hora_final
                ]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Ya existe una reserva en ese horario.";
                } else {
                    // Crear la reserva
                    $stmt = $pdo->prepare("
                        INSERT INTO reserva (fecha, hora_inicio, hora_final, id_usuario, id_cancha) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$fecha, $hora_inicio, $hora_final, $id_usuario, $id_cancha]);
                    
                    $msg = "¡Reserva realizada! 🎉<br>
                            " . date('d/m/Y', strtotime($fecha)) . "<br>
                            {$hora_inicio} - {$hora_final}";
                }
            } catch (PDOException $e) {
                $error = "Error al procesar la reserva: " . $e->getMessage();
            }
        }
    }
}

// Obtener los datos de la cancha
try {
    $stmt = $pdo->prepare("
        SELECT c.*, d.nombre as duenio_nombre 
        FROM cancha c 
        LEFT JOIN duenio d ON c.id_duenio = d.id_duenio 
        WHERE c.id_cancha = ?
    ");
    $stmt->execute([$id_cancha]);
    $cancha = $stmt->fetch();
    
    if (!$cancha) {
        header("Location: cancha.php");
        exit;
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Función para obtener reservas de la cancha en una fecha específica
function obtenerReservas($pdo, $id_cancha, $fecha) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre as usuario_nombre 
        FROM reserva r 
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario 
        WHERE r.id_cancha = ? AND r.fecha = ?
        ORDER BY r.hora_inicio
    ");
    $stmt->execute([$id_cancha, $fecha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generar horarios disponibles
function generarHorarios() {
    $horarios = [];
    for ($h = 8; $h <= 22; $h++) {
        $horarios[] = sprintf("%02d:00", $h);
    }
    return $horarios;
}

// Verificar si un horario está ocupado o ya pasó
function estaOcupado($reservas, $hora, $fecha_mostrar) {
    $hora_fin = date('H:i', strtotime($hora . ' +1 hour'));
    
    // Si es hoy, verificar si la hora ya pasó
    if ($fecha_mostrar === date('Y-m-d')) {
        $hora_actual = date('H:i');
        if ($hora <= $hora_actual) {
            return ['usuario_nombre' => 'Hora pasada', 'tipo' => 'pasada'];
        }
    }
    
    foreach ($reservas as $reserva) {
        if (($hora >= $reserva['hora_inicio'] && $hora < $reserva['hora_final']) ||
            ($hora_fin > $reserva['hora_inicio'] && $hora_fin <= $reserva['hora_final']) ||
            ($hora <= $reserva['hora_inicio'] && $hora_fin >= $reserva['hora_final'])) {
            return array_merge($reserva, ['tipo' => 'ocupada']);
        }
    }
    return false;
}

$horarios = generarHorarios();
$reservas = obtenerReservas($pdo, $id_cancha, $fecha_mostrar);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cancha['nombre']) ?> - Detalles y Reservas</title>
    <link rel="stylesheet" href="css/calendario.css">

</head>
<body>
    <div class="container">
        <?php if (!empty($msg)): ?>
            <div class="mensaje success"><?= $msg ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Información de la cancha -->
        <div class="cancha-info">
            <h1><?= htmlspecialchars($cancha['nombre']) ?></h1>
            
            <?php if (!empty($cancha['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($cancha['foto']) ?>" width="400" height="250" style="border: 1px solid #ccc;">
            <?php else: ?>
                <div style="width: 400px; height: 250px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 8px;">
                    <span style="color: #6c757d;">Sin foto disponible</span>
                </div>
            <?php endif; ?>

            <div class="info-item">
                <strong>Ubicación:</strong> <?= htmlspecialchars($cancha['lugar']) ?>
            </div>
            <div class="info-item">
                <strong>Dueño:</strong> <?= htmlspecialchars($cancha['duenio_nombre']) ?>
            </div>
            <div class="info-item">
                <strong>Descripción:</strong> <?= htmlspecialchars($cancha['bio']) ?>
            </div>
        </div>

        <!-- Sistema de reservas -->
        <div class="calendario-section">
            <?php if (!$id_usuario): ?>
                <div class="alert-login">
                    <a href="login.php">Inicia sesión</a> para poder reservar esta cancha
                </div>
            <?php endif; ?>

            <!-- Selector de fecha -->
            <div class="fecha-selector">
                <h3>Selecciona el día para reservar</h3>
                <p><strong>Mostrando: <?= date('d/m/Y', strtotime($fecha_mostrar)) ?></strong></p>
                
                <div class="fecha-botones">
                    <?php
                    // Generar botones para los próximos 7 días
                    for ($i = 0; $i < 7; $i++) {
                        $fecha_btn = date('Y-m-d', strtotime("+$i days"));
                        $fecha_texto = date('d/m', strtotime("+$i days"));
                        $dia_semana_ingles = date('D', strtotime("+$i days"));
                        $dia_semana = diasespanol($dia_semana_ingles);
                        $clase_activo = ($fecha_btn == $fecha_mostrar) ? 'activo' : '';
                        
                        echo "<a href='?id={$id_cancha}&fecha={$fecha_btn}' class='{$clase_activo}'>";
                        echo "{$dia_semana}<br>{$fecha_texto}";
                        echo "</a>";
                    }
                    ?>
                </div>
                
                <!-- Formulario manual para seleccionar fecha -->
                <div style="margin-top: 15px;">
                    <form method="get" style="display: inline-block;">
                        <input type="hidden" name="id" value="<?= $id_cancha ?>">
                        <input type="date" name="fecha" value="<?= $fecha_mostrar ?>" min="<?= date('Y-m-d') ?>" style="width: 200px;">
                        <button type="submit" class="btn" style="padding: 8px 15px; font-size: 14px;">Ver fecha</button>
                    </form>
                </div>
            </div>

            <!-- Calendario de reservas -->
            <h2><?= $id_usuario ? 'Haz clic para reservar' : 'Horarios disponibles' ?></h2>
            
            <div class="calendario-grid">
                <div class="cancha-card">
                    <div class="cancha-header">
                        <h3><?= htmlspecialchars($cancha['nombre']) ?></h3>
                        <p style="margin: 5px 0; font-size: 14px;">📍 <?= htmlspecialchars($cancha['lugar']) ?></p>
                    </div>
                    
                    <div class="horario-grid">
                        <?php foreach ($horarios as $hora): ?>
                            <?php 
                            $ocupado = estaOcupado($reservas, $hora, $fecha_mostrar);
                            $hora_fin = date('H:i', strtotime($hora . ' +1 hour'));
                            ?>
                            
                            <?php if ($ocupado): ?>
                                <?php if ($ocupado['tipo'] === 'pasada'): ?>
                                    <div class="horario-slot pasado" title="Esta hora ya pasó">
                                        <div><?= $hora ?></div>
                                        <div class="reserva-tooltip">Hora pasada</div>
                                    </div>
                                <?php else: ?>
                                    <div class="horario-slot ocupado" title="Ocupado por <?= htmlspecialchars($ocupado['usuario_nombre']) ?>">
                                        <div><?= $hora ?></div>
                                        <div class="reserva-tooltip">Ocupado</div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($id_usuario): ?>
                                    <form method="post" style="margin: 0;">
                                        <input type="hidden" name="fecha" value="<?= $fecha_mostrar ?>">
                                        <input type="hidden" name="hora_inicio" value="<?= $hora ?>">
                                        <button type="submit" name="reserva_rapida" class="horario-slot disponible"
                                                title="Clic para reservar <?= $hora ?> - <?= $hora_fin ?>">
                                            <div><?= $hora ?></div>
                                            <div class="reserva-tooltip"><?= $hora ?> - <?= $hora_fin ?></div>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="horario-slot disponible" title="Disponible - <?= $hora ?> - <?= $hora_fin ?>">
                                        <div><?= $hora ?></div>
                                        <div class="reserva-tooltip">Disponible</div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p style="text-align: center; margin-top: 30px;">
        <a href="cancha.php">← Volver a todas las canchas</a>
    </p>
</body>
</html>