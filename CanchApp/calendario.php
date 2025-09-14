<?php
session_start();
require_once 'conexiones/conDB.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

$id_usuario = $_SESSION['id'] ?? null;
$msg = '';
$error = '';

//Sacamos la ID de la cancha.
$id_cancha = $_GET['id'] ?? null;

//Mostramos la fecha de hoy.
$fecha_mostrar = $_GET['fecha'] ?? date('Y-m-d');

//Mostramos y preparamos los datos de la cancha espicifica usando la ID q sacamos.
if ($id_cancha) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, d.nombre as duenio_nombre 
            FROM cancha c 
            LEFT JOIN duenio d ON c.id_duenio = d.id_duenio 
            WHERE c.id_cancha = ? AND (c.verificado = 1 OR c.verificado = 0)
        ");
        $stmt->execute([$id_cancha]);
        $cancha = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cancha) {
            header("Location: cancha.php");
            exit;
        }
        
        $canchas = [$cancha];
        
    } catch (PDOException $e) {
        $error = "Error al cargar la cancha: " . $e->getMessage();
        $canchas = [];
    }
} else {
    //------------------------------------------BUSCA CANCHAS-----------------------------------------------
    $buscar = $_GET['buscar'] ?? '';
    
    try {
        $sql = "SELECT * FROM cancha WHERE (verificado = 1 OR verificado = 0)";
        $params = [];
        
        if (!empty($buscar)) {
            $sql .= " AND (nombre LIKE ? OR lugar LIKE ?)";
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
}

//FUNCIÓN CORREGIDA: Obtiene reservas y calcula espacios ocupados por horario
function obtenerreservas($pdo, $id_cancha, $fecha) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre as usuario_nombre 
        FROM reserva r 
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario 
        WHERE r.id_cancha = ? AND r.fecha = ? AND r.estado = 'activa'
        ORDER BY r.hora_inicio
    ");
    $stmt->execute([$id_cancha, $fecha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//NUEVA FUNCIÓN: Calcula espacios ocupados por horario específico
function obtenerEspaciosOcupados($reservas, $hora) {
    $espacios_ocupados = 0;
    $reservas_en_horario = [];
    $hora_fin = date('H:i', strtotime($hora . ' +1 hour'));
    
    foreach ($reservas as $reserva) {
        $r_inicio = substr($reserva['hora_inicio'], 0, 5);
        $r_final  = substr($reserva['hora_final'], 0, 5);

        // Verificar si hay solapamiento de horarios
        if (
            ($hora >= $r_inicio && $hora < $r_final) ||
            ($hora_fin > $r_inicio && $hora_fin <= $r_final) ||
            ($hora <= $r_inicio && $hora_fin >= $r_final)
        ) {
            $espacios_ocupados += (int)($reserva['espacios_reservados'] ?? 1);
            $reservas_en_horario[] = $reserva;
        }
    }
    
    return [
        'espacios_ocupados' => $espacios_ocupados,
        'espacios_disponibles' => 4 - $espacios_ocupados,
        'reservas' => $reservas_en_horario
    ];
}

//----------------------------------SE GENERAN HORARIOS DISPONIBLES-----------------------------------------
function generarhorarios() {
    $horarios = [];
    for ($h = 8; $h <= 22; $h++) {
        $horarios[] = sprintf("%02d:00", $h);
    }
    return $horarios;
}

//FUNCIÓN CORREGIDA: Verifica estado del horario (disponible, parcialmente ocupado, completo, pasado)
function obtenerEstadoHorario($reservas, $hora, $fecha_mostrar) {
    // Si la fecha es HOY, verificar si la hora ya pasó
    if ($fecha_mostrar === date('Y-m-d')) {
        $hora_actual_ts = strtotime(date('Y-m-d H:i'));
        $hora_slot_ts   = strtotime($fecha_mostrar . ' ' . $hora);

        if ($hora_slot_ts <= $hora_actual_ts) {
            return [
                'tipo' => 'pasado',
                'mensaje' => 'Hora pasada',
                'espacios_disponibles' => 0
            ];
        }
    }
    
    $info_espacios = obtenerEspaciosOcupados($reservas, $hora);
    $espacios_ocupados = $info_espacios['espacios_ocupados'];
    $espacios_disponibles = $info_espacios['espacios_disponibles'];
    
    if ($espacios_ocupados === 0) {
        // Completamente disponible
        return [
            'tipo' => 'disponible',
            'mensaje' => 'Disponible',
            'espacios_disponibles' => 4,
            'info_espacios' => '4 espacios libres'
        ];
    } elseif ($espacios_ocupados >= 4) {
        // Completamente ocupado
        $reservas_info = [];
        foreach ($info_espacios['reservas'] as $reserva) {
            $reservas_info[] = $reserva['usuario_nombre'];
        }
        return [
            'tipo' => 'completo',
            'mensaje' => 'Cancha completa',
            'espacios_disponibles' => 0,
            'info_espacios' => '4/4 espacios ocupados',
            'usuarios' => $reservas_info
        ];
    } else {
        // Parcialmente ocupado
        $reservas_info = [];
        foreach ($info_espacios['reservas'] as $reserva) {
            $reservas_info[] = $reserva['usuario_nombre'] . ' (' . $reserva['espacios_reservados'] . ' espacios)';
        }
        return [
            'tipo' => 'parcial',
            'mensaje' => 'Parcialmente ocupado',
            'espacios_disponibles' => $espacios_disponibles,
            'info_espacios' => "{$espacios_ocupados}/4 espacios ocupados",
            'usuarios' => $reservas_info
        ];
    }
}

//SE PASA DE INGLÉS DEFAULT A ESPAÑOL
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

$horarios = generarhorarios();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id_cancha && !empty($canchas) ? htmlspecialchars($canchas[0]['nombre']) . ' - Reservas' : 'Calendario de Reservas' ?></title>
    <link rel="stylesheet" href="css/calendario.css">
    <style>
        /* ESTILOS CORREGIDOS PARA MOSTRAR DIFERENTES ESTADOS */
        .horario-slot {
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        /* Completamente disponible */
        .horario-slot.disponible {
            background: linear-gradient(135deg, #51cf66, #69db7c);
            color: white;
            cursor: pointer;
        }
        
        .horario-slot.disponible:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            background: linear-gradient(135deg, #40c057, #51cf66);
        }
        
        /* Parcialmente ocupado - NUEVO */
        .horario-slot.parcial {
            background: linear-gradient(135deg, #ffd43b, #fab005);
            color: #000;
            cursor: pointer;
            border: 2px solid #fd7e14;
        }
        
        .horario-slot.parcial:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(253, 126, 20, 0.3);
            background: linear-gradient(135deg, #fab005, #fd7e14);
            color: white;
        }
        
        /* Completamente ocupado */
        .horario-slot.completo {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            cursor: not-allowed;
        }
        
        /* Hora pasada */
        .horario-slot.pasado {
            background: #868e96;
            color: white;
            cursor: not-allowed;
        }
        
        .slot-hora {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .slot-info {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .espacios-info {
            font-size: 10px;
            margin-top: 2px;
            font-weight: bold;
        }
        
        .reserva-btn {
            background: none;
            border: none;
            width: 100%;
            height: 100%;
            color: inherit;
            font-size: inherit;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        /* Tooltip para mostrar usuarios */
        .horario-slot[title] {
            position: relative;
        }
        
        /* Leyenda de colores */
        .leyenda {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
            justify-content: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }
        
        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .leyenda-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .color-disponible { background: linear-gradient(135deg, #51cf66, #69db7c); }
        .color-parcial { background: linear-gradient(135deg, #ffd43b, #fab005); }
        .color-completo { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
        .color-pasado { background: #868e96; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($msg)): ?>
            <div class="mensaje success"><?= $msg ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!$id_cancha): ?>
        <!--BUSCAR CANCHA--------------------------------------------------------------------------------->
        <div class="busqueda">
            <h3>Buscar Canchas</h3>
            <form method="get" class="search-container">
                <input type="text" name="buscar" class="search-input" placeholder="Buscar por nombre de cancha o ubicación..." value="<?= htmlspecialchars($buscar ?? '') ?>" id="busqueda">
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
        <?php else: ?>

        <!--CANCHA ESPECIFICA POR ID DE CANCHA.PHP -->
        <?php if (!empty($canchas)): ?>
        <div class="cancha-info">
            <h1><?= htmlspecialchars($canchas[0]['nombre']) ?></h1>
            
            <?php if (!empty($canchas[0]['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($canchas[0]['foto']) ?>" width="400" height="250" style="border: 1px solid #ccc; border-radius: 8px;">
            <?php else: ?>
                <div style="width: 400px; height: 250px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 8px;">
                    <span style="color: #6c757d;">Sin foto disponible</span>
                </div>
            <?php endif; ?>

            <div class="info-item">
                <strong>Ubicación:</strong> <?= htmlspecialchars($canchas[0]['lugar']) ?>
            </div>
            <div class="info-item">
                <strong>Dueño:</strong> <?= htmlspecialchars($canchas[0]['duenio_nombre'] ?? 'No especificado') ?>
            </div>
            <div class="info-item">
                <strong>Descripción:</strong> <?= htmlspecialchars($canchas[0]['bio']) ?>
            </div>
            <div class="info-item" style="background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0;">
                <strong>🎾 Cancha de Padel - Máximo 4 jugadores por reserva</strong>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!$id_usuario && $id_cancha): ?>
            <div class="alert-login">
                <a href="login.php">Inicia sesión</a> para poder reservar esta cancha
            </div>
        <?php endif; ?>
            
        <!--CALENDARIO CON FECHAS---------------------------------------------->
        <div class="fecha-selector">
            <h3>Selecciona el día</h3>
            <p><strong>Mostrando: <?= date('d/m/Y', strtotime($fecha_mostrar)) ?></strong></p>
            
            <div class="fecha-botones">
                <?php
                for ($i = 0; $i < 7; $i++) {
                    $fecha_btn = date('Y-m-d', strtotime("+$i days"));
                    $fecha_texto = date('d/m', strtotime("+$i days"));
                    $dia_semana_ingles = date('D', strtotime("+$i days"));
                    $dia_semana = diasespanol($dia_semana_ingles);
                    $clase_activo = ($fecha_btn == $fecha_mostrar) ? 'activo' : '';
                    
                    $url_params = "fecha={$fecha_btn}";
                    if ($id_cancha) {
                        $url_params .= "&id={$id_cancha}";
                    }
                    if (!empty($buscar ?? '')) {
                        $url_params .= "&buscar=" . urlencode($buscar);
                    }
                    
                    echo "<a href='?{$url_params}' class='{$clase_activo}'>";
                    echo "{$dia_semana}<br>{$fecha_texto}";
                    echo "</a>";
                }
                ?>
            </div>
            
            <div style="margin-top: 15px;">
                <form method="get" style="display: inline-block;">
                    <?php if ($id_cancha): ?>
                        <input type="hidden" name="id" value="<?= $id_cancha ?>">
                    <?php endif; ?>
                    <input type="date" name="fecha" value="<?= $fecha_mostrar ?>" min="<?= date('Y-m-d') ?>" style="width: 200px;">
                    <?php if (!empty($buscar ?? '')): ?>
                        <input type="hidden" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn" style="padding: 8px 15px; font-size: 14px;">Ver fecha</button>
                </form>
            </div>
        </div>
        
        <!-- LEYENDA DE COLORES -->
        <div class="leyenda">
            <div class="leyenda-item">
                <div class="leyenda-color color-disponible"></div>
                <span>Disponible (4 espacios libres)</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-color color-parcial"></div>
                <span>Parcialmente ocupado (puedes unirte)</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-color color-completo"></div>
                <span>Cancha completa (4/4 ocupado)</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-color color-pasado"></div>
                <span>Hora pasada</span>
            </div>
        </div>
        
        <h2><?= $id_usuario ? 'Haz clic para reservar' : 'Horarios disponibles' ?></h2>
        
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
                                $estado = obtenerEstadoHorario($reservas, $hora, $fecha_mostrar);
                                $hora_fin = date('H:i', strtotime($hora . ' +1 hour'));
                                
                                // Crear tooltip con información detallada
                                $tooltip = "Horario: {$hora} - {$hora_fin}\\n";
                                $tooltip .= "Estado: {$estado['mensaje']}\\n";
                                $tooltip .= "Espacios disponibles: {$estado['espacios_disponibles']}/4";
                                
                                if (isset($estado['usuarios']) && !empty($estado['usuarios'])) {
                                    $tooltip .= "\\nReservado por: " . implode(', ', $estado['usuarios']);
                                }
                                ?>
                                
                                <div class="horario-slot <?= $estado['tipo'] ?>" title="<?= htmlspecialchars($tooltip) ?>">
                                    <?php if ($estado['tipo'] === 'disponible' || $estado['tipo'] === 'parcial'): ?>
                                        <?php if ($id_usuario): ?>
                                            <a href="reserva_modal.php?id_cancha=<?= $cancha['id_cancha'] ?>&fecha=<?= $fecha_mostrar ?>&hora_inicio=<?= $hora ?>&espacios_disponibles=<?= $estado['espacios_disponibles'] ?>" 
                                               class="reserva-btn">
                                                <div class="slot-hora"><?= $hora ?></div>
                                                <div class="slot-info"><?= $estado['mensaje'] ?></div>
                                                <div class="espacios-info"><?= $estado['info_espacios'] ?></div>
                                            </a>
                                        <?php else: ?>
                                            <div class="slot-hora"><?= $hora ?></div>
                                            <div class="slot-info"><?= $estado['mensaje'] ?></div>
                                            <div class="espacios-info"><?= $estado['info_espacios'] ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="slot-hora"><?= $hora ?></div>
                                        <div class="slot-info"><?= $estado['mensaje'] ?></div>
                                        <?php if (isset($estado['info_espacios'])): ?>
                                            <div class="espacios-info"><?= $estado['info_espacios'] ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-resultados">
                <h3>No se encontraron canchas</h3>
                <p>La cancha solicitada no existe o no está disponible.</p>
                <a href="cancha.php" class="btn">Ver todas las canchas</a>
            </div>
        <?php endif; ?>
    </div>
    
    <p style="text-align: center;">
        <?php if ($id_cancha): ?>
            <a href="cancha.php">← Volver a todas las canchas</a>
        <?php else: ?>
            <a href="index.php">Volver al inicio</a>
        <?php endif; ?>
    </p>
</body>
</html>