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

// Procesar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_reserva'])) {
    $nombre_organizador = trim($_POST['nombre_organizador'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $jugadores = (int)($_POST['jugadores'] ?? 1);
    
    if (empty($id_cancha) || empty($fecha) || empty($hora_inicio) || empty($nombre_organizador) || $jugadores < 1 || $jugadores > 4) {
        $error = "Todos los campos obligatorios deben estar completos y la cantidad de jugadores debe ser entre 1 y 4.";
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
                // Verificar disponibilidad
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM reserva 
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
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Ya existe una reserva activa en ese horario.";
                } else {
                    // Generar código único de 6 caracteres
                    do {
                        $codigo = strtoupper(substr(uniqid(), -6));
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva WHERE codigo_reserva = ?");
                        $stmt->execute([$codigo]);
                    } while ($stmt->fetchColumn() > 0);
                    
                    // Crear la reserva
                    $stmt = $pdo->prepare("
                        INSERT INTO reserva (codigo_reserva, fecha, hora_inicio, hora_final, id_usuario, id_cancha, jugadores_reservados, telefono, observaciones, estado) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa')
                    ");
                    $stmt->execute([$codigo, $fecha, $hora_inicio, $hora_final, $id_usuario, $id_cancha, $jugadores, $telefono, $observaciones]);
                    
                    // Obtener nombre de la cancha
                    $stmt = $pdo->prepare("SELECT nombre FROM cancha WHERE id_cancha = ?");
                    $stmt->execute([$id_cancha]);
                    $nombre_cancha = $stmt->fetchColumn();
                    
                    $msg = "¡Reserva creada exitosamente! 🎉<br>
                           <strong>Código de reserva: {$codigo}</strong><br>
                           <strong>{$nombre_cancha}</strong><br>
                           " . date('d/m/Y', strtotime($fecha)) . " - {$hora_inicio} a {$hora_final}<br>
                           Jugadores reservados: {$jugadores}/4<br><br>
                           <strong>¡Importante! Presenta este código en la cancha para confirmar tu reserva.</strong>";
                }
            } catch (PDOException $e) {
                $error = "Error al procesar la reserva: " . $e->getMessage();
            }
        }
    }
}

// Obtener datos de la cancha si se proporcionan
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
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .cancha-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }
        
        .cancha-info h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .reserva-details {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #2196f3;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .required {
            color: #e74c3c;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-top: 15px;
        }
        
        .mensaje {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
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
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .info-item strong {
            color: #495057;
            display: block;
            margin-bottom: 5px;
        }
        
        .jugadores-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid #ffc107;
        }
        
        .codigo-display {
            background: #28a745;
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }
        
        .jugadores-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .jugador-option {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .jugador-option:hover {
            border-color: #667eea;
            background: #e3f2fd;
        }
        
        .jugador-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .precio-info {
            background: #e8f5e8;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            color: #2e7d32;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .jugadores-selector {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏓 Reservar Cancha</h1>
            <p>Completa los datos para confirmar tu reserva</p>
        </div>
        
        <?php if (!empty($msg)): ?>
            <div class="mensaje success">
                <?= $msg ?>
                <div style="margin-top: 20px;">
                    <a href="calendario.php" class="btn">Ver Calendario</a>
                    <a href="perfil.php" class="btn btn-secondary">Ver Mis Reservas</a>
                </div>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($cancha && !$msg): ?>
            <div class="cancha-info">
                <h3><?= htmlspecialchars($cancha['nombre']) ?></h3>
                <p><strong>📍 Ubicación:</strong> <?= htmlspecialchars($cancha['lugar']) ?></p>
                <p><strong>📝 Descripción:</strong> <?= htmlspecialchars($cancha['bio']) ?></p>
            </div>
            
            <?php if ($fecha && $hora_inicio): ?>
                <div class="reserva-details">
                    <h3>📅 Detalles de la Reserva</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Fecha</strong>
                            <?= date('d/m/Y', strtotime($fecha)) ?>
                        </div>
                        <div class="info-item">
                            <strong>Horario</strong>
                            <?= $hora_inicio ?> - <?= date('H:i', strtotime($hora_inicio . ' +1 hour')) ?>
                        </div>
                    </div>
                </div>
                
                <div class="jugadores-info">
                    <strong>⚠️ Información importante:</strong><br>
                    • Esta es una cancha de padel (máximo 4 jugadores)<br>
                    • Puedes reservar de 1 a 4 espacios según la cantidad de jugadores<br>
                    • Recibirás un código para presentar en la cancha<br>
                    • El dueño confirmará tu llegada con el código
                </div>
                
                <form method="post">
                    <input type="hidden" name="id_cancha" value="<?= htmlspecialchars($id_cancha) ?>">
                    <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                    <input type="hidden" name="hora_inicio" value="<?= htmlspecialchars($hora_inicio) ?>">
                    
                    <div class="form-group">
                        <label for="nombre_organizador">Nombre del Responsable <span class="required">*</span></label>
                        <input type="text" id="nombre_organizador" name="nombre_organizador" 
                               placeholder="Ingresa tu nombre completo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono de Contacto <span class="required">*</span></label>
                        <input type="tel" id="telefono" name="telefono" 
                               placeholder="Ej: +54 9 11 1234-5678" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="jugadores">Cantidad de Jugadores <span class="required">*</span></label>
                        <select id="jugadores" name="jugadores" required>
                            <option value="1">1 jugador</option>
                            <option value="2">2 jugadores</option>
                            <option value="3">3 jugadores</option>
                            <option value="4">4 jugadores (cancha completa)</option>
                        </select>
                        <div class="precio-info">
                            💡 Tip: Reserva solo los espacios que necesitas. Otros jugadores pueden contactarte para compartir si no completaste los 4 espacios.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observaciones">Observaciones (opcional)</label>
                        <textarea id="observaciones" name="observaciones" rows="3"
                                  placeholder="Cualquier información adicional..."></textarea>
                    </div>
                    
                    <button type="submit" name="confirmar_reserva" class="btn">
                        ✅ Confirmar Reserva
                    </button>
                    
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        ← Volver
                    </a>
                </form>
            <?php else: ?>
                <div class="mensaje error">
                    Faltan datos de la reserva. Por favor, vuelve al calendario.
                </div>
                <a href="calendario.php" class="btn">Volver al Calendario</a>
            <?php endif; ?>
        <?php elseif (!$msg): ?>
            <div class="mensaje error">
                No se pudieron cargar los datos de la cancha.
            </div>
            <a href="calendario.php" class="btn">Volver al Calendario</a>
        <?php endif; ?>
    </div>
</body>
</html>