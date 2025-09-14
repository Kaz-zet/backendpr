<?php
session_start();
require_once 'conexiones/conDB.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

$id_usuario = $_SESSION['id'] ?? null;
$msg = '';
$error = '';

if (!$id_usuario) {
    header("Location: login.php");
    exit;
}

// Obtener datos del formulario
$id_cancha = $_GET['id_cancha'] ?? $_POST['id_cancha'] ?? '';
$fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? '';
$hora_inicio = $_GET['hora_inicio'] ?? $_POST['hora_inicio'] ?? '';
$espacios_disponibles_max = (int)($_GET['espacios_disponibles'] ?? 4); // Nuevo parámetro

// Procesar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_reserva'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $espacios = (int)($_POST['espacios'] ?? 1);
    
    if (empty($id_cancha) || empty($fecha) || empty($hora_inicio) || empty($nombre) || $espacios < 1 || $espacios > 4) {
        $error = "Por favor completa todos los campos obligatorios. Los espacios deben ser entre 1 y 4.";
    } else {
        // Calcular hora final
        $hora_final = date('H:i', strtotime($hora_inicio . ' +1 hour'));
        
        // Validar fecha y hora
        $fecha_actual = date('Y-m-d');
        $ts_actual = strtotime(date('Y-m-d H:i'));
        $ts_solicitada = strtotime($fecha . ' ' . $hora_inicio);
        
        if ($fecha < $fecha_actual) {
            $error = "No puedes reservar en fechas ya pasadas.";
        } elseif ($fecha === $fecha_actual && $ts_solicitada <= $ts_actual) {
            $error = "No puedes reservar en horarios que ya pasaron hoy.";
        } else {
            try {
                // NUEVA LÓGICA: Calcular espacios ya ocupados en ese horario
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(espacios_reservados), 0) as espacios_ocupados
                    FROM reserva 
                    WHERE id_cancha = ? AND fecha = ? AND estado = 'activa'
                    AND (
                        (hora_inicio <= ? AND hora_final > ?) 
                        OR
                        (hora_inicio < ? AND hora_final >= ?)
                        OR
                        (hora_inicio >= ? AND hora_final <= ?)
                    )
                ");
                $stmt->execute([
                    $id_cancha, $fecha, 
                    $hora_inicio, $hora_inicio,
                    $hora_final, $hora_final,
                    $hora_inicio, $hora_final
                ]);
                
                $espacios_ocupados = (int)$stmt->fetchColumn();
                $espacios_disponibles = 4 - $espacios_ocupados;
                
                // Verificar si hay suficientes espacios disponibles
                if ($espacios > $espacios_disponibles) {
                    if ($espacios_disponibles === 0) {
                        $error = "Este horario ya está completamente ocupado (4/4 espacios). Por favor elige otro horario.";
                    } else {
                        $error = "Solo quedan {$espacios_disponibles} espacios disponibles en este horario. Reduce tu reserva a {$espacios_disponibles} espacios o menos.";
                    }
                } else {
                    // Generar código único de 6 caracteres
                    do {
                        $codigo = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva WHERE codigo_reserva = ?");
                        $stmt->execute([$codigo]);
                    } while ($stmt->fetchColumn() > 0);
                    
                    // Crear la reserva
                    $stmt = $pdo->prepare("
                        INSERT INTO reserva (codigo_reserva, fecha, hora_inicio, hora_final, id_usuario, id_cancha, espacios_reservados, telefono, observaciones, estado) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa')
                    ");
                    $stmt->execute([$codigo, $fecha, $hora_inicio, $hora_final, $id_usuario, $id_cancha, $espacios, $telefono, $observaciones]);
                    
                    // Obtener nombre de la cancha
                    $stmt = $pdo->prepare("SELECT nombre FROM cancha WHERE id_cancha = ?");
                    $stmt->execute([$id_cancha]);
                    $nombre_cancha = $stmt->fetchColumn();
                    
                    // Calcular espacios restantes después de esta reserva
                    $espacios_restantes = $espacios_disponibles - $espacios;
                    
                    $msg = "¡Reserva realizada con éxito! 🎉<br><br>
                           <div style='background: #28a745; color: white; padding: 15px; border-radius: 10px; font-size: 20px; font-weight: bold; letter-spacing: 2px; text-align: center; font-family: monospace;'>{$codigo}</div><br>
                           <strong>{$nombre_cancha}</strong><br>
                           📅 " . date('d/m/Y', strtotime($fecha)) . "<br>
                           🕒 {$hora_inicio} - {$hora_final}<br>
                           👥 {$espacios} espacios reservados<br>";
                    
                    if ($espacios_restantes > 0) {
                        $msg .= "<br><div style='background: #ffc107; color: #000; padding: 10px; border-radius: 8px; font-size: 14px;'>
                                 ℹ️ Quedan {$espacios_restantes} espacios disponibles. Otros jugadores pueden unirse a este horario.
                                 </div>";
                    } else {
                        $msg .= "<br><div style='background: #dc3545; color: white; padding: 10px; border-radius: 8px; font-size: 14px;'>
                                 🔒 Has reservado la cancha completa (4/4 espacios).
                                 </div>";
                    }
                    
                    $msg .= "<br><strong>¡Presenta este código en la cancha!</strong>";
                }
            } catch (PDOException $e) {
                $error = "Error al procesar la reserva: " . $e->getMessage();
            }
        }
    }
}

// Obtener datos de la cancha
$cancha = null;
if ($id_cancha) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cancha WHERE id_cancha = ?");
        $stmt->execute([$id_cancha]);
        $cancha = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error al cargar los datos de la cancha.";
    }
}

// Obtener espacios ocupados actuales para mostrar información actualizada
$espacios_ocupados_actual = 0;
$espacios_disponibles_actual = 4;

if ($id_cancha && $fecha && $hora_inicio) {
    try {
        $hora_final = date('H:i', strtotime($hora_inicio . ' +1 hour'));
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(espacios_reservados), 0) as espacios_ocupados,
                   GROUP_CONCAT(CONCAT(u.nombre, ' (', r.espacios_reservados, ' espacios)') SEPARATOR ', ') as reservas_info
            FROM reserva r 
            LEFT JOIN usuario u ON r.id_usuario = u.id_usuario
            WHERE r.id_cancha = ? AND r.fecha = ? AND r.estado = 'activa'
            AND (
                (r.hora_inicio <= ? AND r.hora_final > ?) 
                OR
                (r.hora_inicio < ? AND r.hora_final >= ?)
                OR
                (r.hora_inicio >= ? AND r.hora_final <= ?)
            )
        ");
        $stmt->execute([
            $id_cancha, $fecha, 
            $hora_inicio, $hora_inicio,
            $hora_final, $hora_final,
            $hora_inicio, $hora_final
        ]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $espacios_ocupados_actual = (int)$resultado['espacios_ocupados'];
        $espacios_disponibles_actual = 4 - $espacios_ocupados_actual;
        $reservas_existentes = $resultado['reservas_info'];
        
    } catch (PDOException $e) {
        // En caso de error, mantener valores por defecto
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Cancha</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 5px;
            font-size: 2.2em;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .cancha-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }
        
        .cancha-info h3 {
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .reserva-details {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
        }
        
        .reserva-details div {
            text-align: center;
        }
        
        .reserva-details strong {
            display: block;
            color: #1976d2;
            margin-bottom: 5px;
        }
        
        /* NUEVO: Información de ocupación actual */
        .ocupacion-actual {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .ocupacion-actual h4 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .espacios-visual {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .espacio-visual {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .espacio-ocupado {
            background: #dc3545;
            color: white;
        }
        
        .espacio-disponible {
            background: #28a745;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-back {
            background: #6c757d;
            margin-top: 10px;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .mensaje {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
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
        
        .espacios-info {
            background: #e8f4fd;
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 14px;
            color: #0c5460;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎾 Reservar Cancha</h1>
            <p>Completa tus datos</p>
        </div>
        
        <?php if (!empty($msg)): ?>
            <div class="mensaje success">
                <?= $msg ?>
                <div style="margin-top: 15px;">
                    <a href="calendario.php?id=<?= $cancha['id_cancha'] ?>&fecha=<?= $fecha ?>" class="btn">Ver Calendario</a>
                </div>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($cancha && !$msg): ?>
            <div class="cancha-info">
                <h3><?= htmlspecialchars($cancha['nombre']) ?></h3>
                <p><strong>📍</strong> <?= htmlspecialchars($cancha['lugar']) ?></p>
                <p><strong>📝</strong> <?= htmlspecialchars($cancha['bio']) ?></p>
            </div>
            
            <?php if ($fecha && $hora_inicio): ?>
                <div class="reserva-details">
                    <div>
                        <strong>📅 Fecha</strong>
                        <?= date('d/m/Y', strtotime($fecha)) ?>
                    </div>
                    <div>
                        <strong>🕒 Horario</strong>
                        <?= $hora_inicio ?> - <?= date('H:i', strtotime($hora_inicio . ' +1 hour')) ?>
                    </div>
                </div>
                
                <!-- NUEVA SECCIÓN: Estado actual de ocupación -->
                <div class="ocupacion-actual">
                    <h4>🎯 Estado actual del horario</h4>
                    
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <span><strong>Espacios ocupados:</strong> <?= $espacios_ocupados_actual ?>/4</span>
                        <span><strong>Disponibles:</strong> <?= $espacios_disponibles_actual ?>/4</span>
                    </div>
                    
                    <div class="espacios-visual">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="espacio-visual <?= $i <= $espacios_ocupados_actual ? 'espacio-ocupado' : 'espacio-disponible' ?>">
                                <?= $i <= $espacios_ocupados_actual ? '👤' : '◯' ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($espacios_ocupados_actual > 0): ?>
                        <div style="font-size: 13px; color: #6c757d; margin-top: 8px;">
                            <strong>Ya reservado por:</strong> <?= htmlspecialchars($reservas_existentes ?? 'Usuarios anteriores') ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($espacios_disponibles_actual > 0): ?>
                <form method="post">
                    <input type="hidden" name="id_cancha" value="<?= htmlspecialchars($id_cancha) ?>">
                    <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                    <input type="hidden" name="hora_inicio" value="<?= htmlspecialchars($hora_inicio) ?>">
                    
                    <div class="form-group">
                        <label for="nombre">Tu Nombre <span class="required">*</span></label>
                        <input type="text" id="nombre" name="nombre" 
                               placeholder="Nombre completo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono <span class="required">*</span></label>
                        <input type="tel" id="telefono" name="telefono" 
                               placeholder="Ej: +54 9 11 1234-5678" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="espacios">Espacios a Reservar <span class="required">*</span></label>
                        <select id="espacios" name="espacios" required>
                            <?php for ($i = 1; $i <= min(4, $espacios_disponibles_actual); $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> espacio<?= $i > 1 ? 's' : '' ?> (<?= $i ?> jugador<?= $i > 1 ? 'es' : '' ?>)</option>
                            <?php endfor; ?>
                        </select>
                        
                        <div class="espacios-info">
                            💡 Puedes reservar hasta <?= $espacios_disponibles_actual ?> espacios disponibles.
                            <?php if ($espacios_ocupados_actual > 0): ?>
                                <br>🤝 Te unirás a otros jugadores en este horario.
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3"
                                  placeholder="Información adicional (opcional)..."></textarea>
                    </div>
                    
                    <button type="submit" name="confirmar_reserva" class="btn">
                        🎾 Confirmar Reserva
                    </button>
                    
                    <button type="button" onclick="history.back()" class="btn btn-back">
                        ← Volver
                    </button>
                </form>
                
                <?php else: ?>
                    <div class="warning">
                        <strong>❌ Horario completo</strong><br>
                        Este horario ya tiene los 4 espacios ocupados. Por favor selecciona otro horario.
                    </div>
                    <button type="button" onclick="history.back()" class="btn">
                        ← Elegir otro horario
                    </button>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="mensaje error">
                    Faltan datos. Vuelve al calendario para seleccionar fecha y hora.
                </div>
                <a href="calendario.php?id=<?= $cancha['id_cancha'] ?>" class="btn">Ir al Calendario</a>
            <?php endif; ?>
        <?php elseif (!$msg): ?>
            <div class="mensaje error">
                No se pudo cargar la cancha.
            </div>
            <a href="calendario.php" class="btn">Ir al Calendario</a>
        <?php endif; ?>
    </div>
</body>
</html>