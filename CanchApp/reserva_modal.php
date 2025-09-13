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
                // Verificar si ya hay una reserva activa en ese horario
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
                    $error = "Ya existe una reserva en ese horario. Por favor elige otro horario.";
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
                    
                    $msg = "¡Reserva realizada con éxito! 🎉<br><br>
                           <div style='background: #28a745; color: white; padding: 15px; border-radius: 10px; font-size: 20px; font-weight: bold; letter-spacing: 2px; text-align: center; font-family: monospace;'>{$codigo}</div><br>
                           <strong>{$nombre_cancha}</strong><br>
                           📅 " . date('d/m/Y', strtotime($fecha)) . "<br>
                           🕒 {$hora_inicio} - {$hora_final}<br>
                           👥 {$espacios} espacios reservados (de 4 disponibles)<br><br>
                           <strong>¡Presenta este código en la cancha!</strong>";
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
            background: #fff3cd;
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 14px;
            color: #856404;
        }
        
        .espacios-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 8px;
        }
        
        .espacio-btn {
            padding: 8px;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .espacio-btn:hover {
            border-color: #667eea;
        }
        
        .espacio-btn.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Reservar Cancha</h1>
            <p>Completa tus datos</p>
        </div>
        
        <?php if (!empty($msg)): ?>
            <div class="mensaje success">
                <?= $msg ?>
                <div style="margin-top: 15px;">
                    <a href="calendario.php?id=<?= $cancha['id_cancha'] ?>" class="btn">Ir al Calendario</a>
                </div>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($cancha && !$msg): ?>
            <div class="cancha-info">
                <h3><?= htmlspecialchars($cancha['nombre']) ?></h3>
                <p><strong></strong> <?= htmlspecialchars($cancha['lugar']) ?></p>
                <p><strong></strong> <?= htmlspecialchars($cancha['bio']) ?></p>
            </div>
            
            <?php if ($fecha && $hora_inicio): ?>
                <div class="reserva-details">
                    <div>
                        <strong>Fecha</strong>
                        <?= date('d/m/Y', strtotime($fecha)) ?>
                    </div>
                    <div>
                        <strong>Horario</strong>
                        <?= $hora_inicio ?> - <?= date('H:i', strtotime($hora_inicio . ' +1 hour')) ?>
                    </div>
                </div>
                
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
                            <option value="1">1 espacio (1 jugador)</option>
                            <option value="2">2 espacios (2 jugadores)</option>
                            <option value="3">3 espacios (3 jugadores)</option>
                            <option value="4">4 espacios (cancha completa)</option>
                        </select>
                        <div class="espacios-info">
                            💡Reserva solo los espacios que necesitas. Máximo 4 para cancha de padel.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3"
                                  placeholder="Información adicional (opcional)..."></textarea>
                    </div>
                    
                    <button type="submit" name="confirmar_reserva" class="btn">
                         Confirmar Reserva
                    </button>
                    
                    <button type="button" onclick="history.back()" class="btn btn-back">
                        ← Volver
                    </button>
                </form>
            <?php else: ?>
                <div class="mensaje error">
                    Faltan datos. Vuelve al calendario para seleccionar fecha y hora.
                </div>
                <a href="cancha.php?id=<?= $cancha['id_cancha'] ?>" class="btn">Ir al Calendario</a>
            <?php endif; ?>
        <?php elseif (!$msg): ?>
            <div class="mensaje error">
                No se pudo cargar la cancha.
            </div>
            <a href="cancha.php?id=<?= $cancha['id_cancha'] ?>" class="btn">Ir al Calendario</a>
        <?php endif; ?>
    </div>
</body>
</html>