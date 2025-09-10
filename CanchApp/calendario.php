<?php
session_start();
require_once 'conexiones/conDB.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    die("Solo los usuarios pueden reservar canchas.");
}

$id_usuario = $_SESSION['id'];
$msg = '';
$error = '';

// Fecha para mostrar (por defecto hoy)
$fecha_mostrar = $_GET['fecha'] ?? date('Y-m-d');

// buscar una cancha por nombre o lugar
$buscar = $_GET['buscar'] ?? '';

// Obtener todas las canchas disponibles con la busqueda hecha
try {
    $sql = "SELECT * FROM cancha WHERE (verificado = 1 OR verificado = 0)";
    $params = [];
    
    if (!empty($buscar)) {
        $sql .= " AND (nombre LIKE ? OR lugar LIKE ?)";
        // El % funciona para encontrar coincidiencias en la busqueda. ej: si busco "centro" me trae "Centro Deportivo" y otros resultados similares a eso
        $params[] = "%{$buscar}%";
        $params[] = "%{$buscar}%";
    }
    
    $sql .= " ORDER BY nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $canchas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar canchas: " . $e->getMessage();
    $canchas = [];
}

// Procesar reserva RÁPIDA (un clic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_rapida'])) {
    $id_cancha = $_POST['id_cancha'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    
    if (empty($id_cancha) || empty($fecha) || empty($hora_inicio)) {
        $error = "Datos incompletos para la reserva.";
    } else {
        // Calcular hora final automáticamente (1 hora después)
        $hora_final = date('H:i', strtotime($hora_inicio . ' +1 hour'));
        
        // Valida que la fecha no este atrasada, 
        $fecha_actual = date('Y-m-d');
        if ($fecha < $fecha_actual) {
            $error = "No puedes reservar en fechas ya pasadas.";
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
                    
                    // agarra el nombre de la cancha para el mensaje
                    $stmt = $pdo->prepare("SELECT nombre FROM cancha WHERE id_cancha = ?");
                    $stmt->execute([$id_cancha]);
                    $nombre_cancha = $stmt->fetchColumn();
                    
                    $msg = "¡Reserva realizada! 🎉<br>
                           <strong>{$nombre_cancha}</strong><br>
                            " . date('d/m/Y', strtotime($fecha)) . "<br>
                            {$hora_inicio} - {$hora_final}";
                }
            } catch (PDOException $e) {
                $error = "Error al procesar la reserva: " . $e->getMessage();
            }
        }
    }
}

// Función para obtener reservas de una cancha en una fecha específica
function obtenerreservas($pdo, $id_cancha, $fecha) {
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
function generarhorarios() {
    $horarios = [];
    for ($h = 8; $h <= 22; $h++) {
        $horarios[] = sprintf("%02d:00", $h);
    }
    return $horarios;
}

// Verificar si un horario está ocupado
function estaocupado($reservas, $hora) {
    $hora_fin = date('H:i', strtotime($hora . ' +1 hour'));
    
    foreach ($reservas as $reserva) {
        // Verificar si hay conflicto con reservas existentes
        if (($hora >= $reserva['hora_inicio'] && $hora < $reserva['hora_final']) ||
            ($hora_fin > $reserva['hora_inicio'] && $hora_fin <= $reserva['hora_final']) ||
            ($hora <= $reserva['hora_inicio'] && $hora_fin >= $reserva['hora_final'])) {
            return $reserva;
        }
    }
    return false;
}

$horarios = generarhorarios();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Reservas</title>
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
        
        <!-- Busqueda -->
        <div class="busqueda">
            <h3>Buscar Canchas</h3>
            <form method="get" class="search-container">
                <input type="text" name="buscar" class="search-input" placeholder="Buscar por nombre de cancha o ubicación..." value="<?= htmlspecialchars($buscar) ?>" id="busqueda">
                <!-- Mantener la fecha actual en el filtro -->
                <input type="hidden" name="fecha" value="<?= $fecha_mostrar ?>">
                
                <button type="submit" class="btn-buscar">Buscar</button>
                
                <?php if (!empty($buscar)): ?>
                    <a href="?fecha=<?= $fecha_mostrar ?>" class="btn-limpiar">Limpiar</a>
                <?php endif; ?>
            </form>
            
            <div class="resultados">
                <?php if (!empty($buscar)): ?>
                    Mostrando resultados para: "<strong><?= htmlspecialchars($buscar) ?></strong>" 
                    - <?= count($canchas) ?> cancha(s) encontrada(s)
                <?php else: ?>
                    Mostrando todas las canchas (<?= count($canchas) ?> total)
                <?php endif; ?>
            </div>
        </div>
            <!-- Selector de fecha para reservar -->
            <div class="fecha-selector">
                <h3>Selecciona el día</h3>
                <p><strong>Mostrando: <?= date('d/m/Y', strtotime($fecha_mostrar)) ?></strong></p>
                
                <div class="fecha-botones">
                    <?php
                    // Generar botones para los próximos 7 días
                    for ($i = 0; $i < 7; $i++) {
                        $fecha_btn = date('Y-m-d', strtotime("+$i days"));
                        $fecha_texto = date('d/m', strtotime("+$i days"));
                        $dia_semana = date('D', strtotime("+$i days"));
                        $clase_activo = ($fecha_btn == $fecha_mostrar) ? 'activo' : '';
                        
                        // Mantener el filtro de búsqueda al cambiar fecha
                        $url_params = "fecha={$fecha_btn}";
                        if (!empty($buscar)) {
                            $url_params .= "&buscar=" . urlencode($buscar);
                        }
                        
                        echo "<a href='?{$url_params}' class='{$clase_activo}'>";
                        echo "{$dia_semana}<br>{$fecha_texto}";
                        echo "</a>";
                    }
                    ?>
                </div>
                
                <!-- Formulario manual para seleccionar fecha -->
                <div style="margin-top: 15px;">
                    <form method="get" style="display: inline-block;">
                        <input type="date" name="fecha" value="<?= $fecha_mostrar ?>" min="<?= date('Y-m-d') ?>" style="width: 200px;">
                        <?php if (!empty($buscar)): ?>
                            <input type="hidden" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn" style="padding: 8px 15px; font-size: 14px;">Ver fecha</button>
                    </form>
                </div>
            </div>
            
            <!-- Calendario de reserva-->
            <h2> Haz clic para reservar</h2>
            
            <?php if (!empty($canchas)): ?>
                <div class="calendario-grid" id="canchas-grid">
                    <?php foreach ($canchas as $cancha): ?>
                        <?php $reservas = obtenerreservas($pdo, $cancha['id_cancha'], $fecha_mostrar); ?>
                        <div class="cancha-card" data-nombre="<?= strtolower(htmlspecialchars($cancha['nombre'])) ?>" data-lugar="<?= strtolower(htmlspecialchars($cancha['lugar'])) ?>">
                            <div class="cancha-header">
                                <h3><?= htmlspecialchars($cancha['nombre']) ?></h3>
                                <p style="margin: 5px 0; font-size: 14px;">📍 <?= htmlspecialchars($cancha['lugar']) ?></p>
                            </div>
                            
                            <div class="horario-grid">
                                <?php foreach ($horarios as $hora): ?>
                                    <?php 
                                    $ocupado = estaocupado($reservas, $hora);
                                    $hora_fin = date('H:i', strtotime($hora . ' +1 hour'));
                                    ?>
                                    
                                    <?php if ($ocupado): ?>
                                        <div class="horario-slot ocupado" title="Ocupado por <?= htmlspecialchars($ocupado['usuario_nombre']) ?>">
                                            <div><?= $hora ?></div>
                                            <div class="reserva-tooltip">Ocupado</div>
                                        </div>
                                    <?php else: ?>
                                        <form method="post" style="margin: 0;">
                                            <input type="hidden" name="id_cancha" value="<?= $cancha['id_cancha'] ?>">
                                            <input type="hidden" name="fecha" value="<?= $fecha_mostrar ?>">
                                            <input type="hidden" name="hora_inicio" value="<?= $hora ?>">
                                            <button type="submit" name="reserva_rapida" class="horario-slot disponible"
                                                    title="Clic para reservar <?= $hora ?> - <?= $hora_fin ?>">
                                                <div><?= $hora ?></div>
                                                <div class="reserva-tooltip"><?= $hora ?> - <?= $hora_fin ?></div>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-resultados">
                    <h3>No se encontraron canchas</h3>
                    <?php if (!empty($buscar)): ?>
                        <p>No hay canchas que coincidan con "<strong><?= htmlspecialchars($buscar) ?></strong>"</p>
                        <a href="?fecha=<?= $fecha_mostrar ?>" class="btn">Ver todas las canchas</a>
                    <?php else: ?>
                        <p>No hay canchas disponibles en este momento.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <p style="text-align: center;">
        <a href="index.php">Volver al inicio</a>
    </p>
</body>
</html>