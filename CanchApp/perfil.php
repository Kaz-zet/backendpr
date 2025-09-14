<?php
session_start();
require_once 'conexiones/conDB.php';

// Solo usuarios pueden ver su perfil
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    die("Solo los usuarios pueden ver su perfil.");
}

$id_usuario = $_SESSION['id'];
$msg = '';
$error = '';

//Obtenemos los datos del usuario usando la ID.
try {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        die("Usuario no encontrado.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CANCELAR RESERVA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {
    $id_reserva = $_POST['id_reserva'] ?? '';
    
    if (!empty($id_reserva)) {
        try {
            // Verificar que la reserva pertenece al usuario y está activa
            $stmt = $pdo->prepare("
                SELECT * FROM reserva 
                WHERE id_reserva = ? AND id_usuario = ? AND estado = 'activa'
            ");
            $stmt->execute([$id_reserva, $id_usuario]);
            $reserva = $stmt->fetch();
            
            if ($reserva) {
                // Verificar si la reserva es futura (no se puede cancelar si ya pasó)
                $fecha_hora_reserva = $reserva['fecha'] . ' ' . $reserva['hora_inicio'];
                $ts_reserva = strtotime($fecha_hora_reserva);
                $ts_actual = time();
                
                if ($ts_reserva > $ts_actual) {
                    // Cambiar estado a cancelada
                    $stmt = $pdo->prepare("UPDATE reserva SET estado = 'cancelada' WHERE id_reserva = ?");
                    $stmt->execute([$id_reserva]);
                    
                    $msg = "Reserva cancelada exitosamente. Código: " . $reserva['codigo_reserva'];
                } else {
                    $error = "No puedes cancelar una reserva que ya comenzó o pasó.";
                }
            } else {
                $error = "Reserva no encontrada o no tienes permisos para cancelarla.";
            }
        } catch (PDOException $e) {
            $error = "Error al cancelar la reserva: " . $e->getMessage();
        }
    }
}

//Actualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena_nueva = $_POST['contrasena_nueva'] ?? '';
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $foto_actual = $usuario['foto'];
    $foto_nueva = null;

    //Se validan nombre y email.
    if ($nombre === '' || $email === '') {
        $error = 'El nombre y email son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } elseif ($contrasena_actual !== $usuario['contrasena']) {
        $error = 'La contraseña actual es incorrecta.';
    } else {
        //Si el email ya lo agarró otro.
        try {
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ?");
            $stmt->execute([$email, $id_usuario]);
            
            if ($stmt->fetch()) {
                $error = 'Este email ya está en uso por otro usuario.';
            } else {

                //--------------------------------------------------------------PARA SUBIR FOTO-----------------------------------------
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    $maxSize = 5 * 1024 * 1024;
                    
                    if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
                        $error = 'Solo se permiten archivos JPG, JPEG y PNG.';
                    } elseif ($_FILES['foto']['size'] > $maxSize) {
                        $error = 'El archivo es muy grande. Máximo 5MB.';
                    } else {
                        //Crea una carpeta en la carpeta Uploads donde se guardan las fotos de los usuarios.
                        if (!file_exists('uploads/usuarios')) {
                            mkdir('uploads/usuarios', 0777, true);
                        }
                        
                        //Si ya existía una foto antes se borra.
                        if ($foto_actual && file_exists('uploads/usuarios/' . $foto_actual)) {
                            unlink('uploads/usuarios/' . $foto_actual);
                        }
                        
                        //Para subir foto nueva.
                        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                        $filename = 'usuario_' . $id_usuario . '_' . time() . '.' . $extension;
                        $uploadPath = 'uploads/usuarios/' . $filename;
                        
                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
                            $foto_nueva = $filename;
                        } else {
                            $error = 'Error al subir la imagen.';
                        }
                    }
                }

                //--------------------------ACTUALIZAR PERFIL-------------------------------------------------
                
                //Si no hay ningún error, se actualiza.
                if (empty($error)) {
                    try {
                        //Determina que foto usar.
                        $foto_final = $foto_nueva ?: $foto_actual;
                        
                        //Se determina que contraseña usar, si la contraseña nueva está vacía usa la anterior, sino usa la nueva.
                        $contrasena_final = !empty($contrasena_nueva) ? $contrasena_nueva : $usuario['contrasena'];
                        
                        //Se valida la nueva contraseña.
                        if (!empty($contrasena_nueva) && strlen($contrasena_nueva) < 3) {
                            $error = 'La nueva contraseña debe tener al menos 3 caracteres.';
                        } else {
                            //Se actualizan los datos con los cambios y no cambios.
                            $stmt = $pdo->prepare("
                                UPDATE usuario 
                                SET nombre = ?, email = ?, contrasena = ?, foto = ?
                                WHERE id_usuario = ?
                            ");
                            $stmt->execute([$nombre, $email, $contrasena_final, $foto_final, $id_usuario]);
                            
                            //Se actualiza la sesion.
                            $_SESSION['nombre'] = $nombre;
                            
                            //Se recargan los datos del usuario.
                            $usuario['nombre'] = $nombre;
                            $usuario['email'] = $email;
                            $usuario['contrasena'] = $contrasena_final;
                            $usuario['foto'] = $foto_final;
                            
                            $msg = 'Perfil actualizado correctamente.';
                        }
                    } catch (PDOException $e) {
                        $error = 'Error al actualizar perfil: ' . $e->getMessage();
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Error al verificar email: ' . $e->getMessage();
        }
    }
}

//--------------------------------------------------------------HISTORIAL DE RESERVAS CORREGIDO--------------------------------------
try {
    $stmt = $pdo->prepare("
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
            CASE 
                WHEN r.estado = 'cancelada' THEN 'cancelada'
                WHEN r.fecha < CURDATE() THEN 'pasada'
                WHEN r.fecha = CURDATE() AND r.hora_final <= CURTIME() THEN 'pasada'
                WHEN r.fecha = CURDATE() THEN 'hoy'
                ELSE 'futura'
            END as estado_calculado
        FROM reserva r
        INNER JOIN cancha c ON r.id_cancha = c.id_cancha
        WHERE r.id_usuario = ?
        ORDER BY r.fecha DESC, r.hora_inicio DESC
        LIMIT 20
    ");
    $stmt->execute([$id_usuario]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservas = [];
    $error_reservas = 'Error al cargar reservas: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= htmlspecialchars($usuario['nombre']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 4px solid white;
            object-fit: cover;
            display: block;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .profile-photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            margin: 0 auto 20px;
            border: 4px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.2em;
        }
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 14px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        }
        .btn-danger {
            background: #dc3545;
            padding: 8px 16px;
            font-size: 14px;
        }
        .btn-danger:hover {
            background: #c82333;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .mensaje {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
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
        .reservas-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .reservas-table th,
        .reservas-table td {
            padding: 18px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .reservas-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .reservas-table tr:hover {
            background-color: #f8f9fa;
        }
        .reservas-table tr:last-child td {
            border-bottom: none;
        }
        .estado-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .estado-hoy {
            background: #fff3cd;
            color: #856404;
        }
        .estado-futura {
            background: #d1ecf1;
            color: #0c5460;
        }
        .estado-pasada {
            background: #d4edda;
            color: #155724;
        }
        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 30px;
        }
        .tab {
            padding: 18px 30px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-right: 5px;
            border-radius: 10px 10px 0 0;
            transition: all 0.3s;
        }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tab:not(.active):hover {
            background: #e9ecef;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 30px 25px;
            text-align: center;
            border-radius: 15px;
            border-left: 5px solid #667eea;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        .codigo-reserva {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: bold;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .espacios-info {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
            margin-top: 5px;
        }
        .no-reservas {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .no-reservas h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        .no-reservas p {
            font-size: 1.1em;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (!empty($usuario['foto'])): ?>
                <img src="uploads/usuarios/<?= htmlspecialchars($usuario['foto']) ?>" 
                     alt="Foto de perfil" class="profile-photo">
            <?php else: ?>
                <div class="profile-photo-placeholder">
                    👤
                </div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
            <p><?= htmlspecialchars($usuario['email']) ?></p>
        </div>
        
        <div class="content">
            <?php if (!empty($msg)): ?>
                <div class="mensaje success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- TABS -->
            <div class="tabs">
                <button class="tab active" onclick="showTab('perfil')">✏️ Mi Perfil</button>
                <button class="tab" onclick="showTab('reservas')">📋 Mis Reservas</button>
            </div>
            
            <!-- TAB PERFIL -->
            <div id="perfil" class="tab-content active">
                <div class="section">
                    <h2>Editar Perfil</h2>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>📸 Foto de Perfil:</label>
                            <input type="file" name="foto" accept="image/*">
                            <small>Formatos: JPG, JPEG, PNG. Máximo 5MB.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>👤 Nombre:</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>📧 Email:</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>🔒 Nueva Contraseña (opcional):</label>
                            <input type="password" name="contrasena_nueva" placeholder="Dejar vacío para mantener la actual">
                            <small>Mínimo 3 caracteres. Déjala vacía si no quieres cambiarla.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>🔐 Contraseña Actual:</label>
                            <input type="password" name="contrasena_actual" required>
                            <small>Ingresa tu contraseña actual para confirmar cualquier cambio.</small>
                        </div>
                        
                        <button type="submit" name="actualizar_perfil" class="btn">✅ Actualizar Perfil</button>
                        <a href="index.php" class="btn btn-secondary">🏠 Volver al Inicio</a>
                    </form>
                </div>
            </div>

            <!-- TAB HISTORIAL CORREGIDO -->
            <div id="reservas" class="tab-content">
                <div class="section">
                    <h2>📋 Mi Historial de Reservas</h2>
                    
                    <?php
                    // Calcular estadísticas
                    $total_reservas = count($reservas);
                    $reservas_activas = count(array_filter($reservas, function($r) { return $r['estado'] === 'activa'; }));
                    $reservas_hoy = count(array_filter($reservas, function($r) { return $r['estado_calculado'] === 'hoy' && $r['estado'] === 'activa'; }));
                    $reservas_futuras = count(array_filter($reservas, function($r) { return $r['estado_calculado'] === 'futura' && $r['estado'] === 'activa'; }));
                    $reservas_canceladas = count(array_filter($reservas, function($r) { return $r['estado'] === 'cancelada'; }));
                    
                    // NUEVA ESTADÍSTICA: Total de espacios reservados
                    $total_espacios = array_sum(array_column(array_filter($reservas, function($r) { return $r['estado'] === 'activa'; }), 'espacios_reservados'));
                    ?>  
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $total_reservas ?></div>
                            <div class="stat-label">Total Reservas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $reservas_activas ?></div>
                            <div class="stat-label">Activas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $reservas_futuras ?></div>
                            <div class="stat-label">Próximas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $total_espacios ?></div>
                            <div class="stat-label">Espacios Reservados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $reservas_canceladas ?></div>
                            <div class="stat-label">Canceladas</div>
                        </div>
                    </div>

                    <?php if (!empty($reservas)): ?>
                        <table class="reservas-table">
                            <thead>
                                <tr>
                                    <th>📅 Fecha</th>
                                    <th>🕒 Horario</th>
                                    <th>🎾 Cancha</th>
                                    <th>📍 Ubicación</th>
                                    <th>👥 Espacios</th>
                                    <th>🔢 Código</th>
                                    <th>📊 Estado</th>
                                    <th>⚡ Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservas as $reserva): ?>
                                    <tr>
                                        <td>
                                            <strong><?= date('d/m/Y', strtotime($reserva['fecha'])) ?></strong><br>
                                            <small style="color: #666;">
                                                <?php
                                                $dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado'];
                                                echo $dias[date('l', strtotime($reserva['fecha']))];
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong><?= substr($reserva['hora_inicio'], 0, 5) ?> - <?= substr($reserva['hora_final'], 0, 5) ?></strong>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($reserva['cancha_nombre']) ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($reserva['cancha_lugar']) ?>
                                        </td>
                                        <td>
                                            <div class="espacios-info">
                                                🎾 <?= $reserva['espacios_reservados'] ?>/4 espacios
                                            </div>
                                        </td>
                                        <td>
                                            <div class="codigo-reserva">
                                                <?= htmlspecialchars($reserva['codigo_reserva']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $estado_final = $reserva['estado'] === 'cancelada' ? 'cancelada' : $reserva['estado_calculado'];
                                            $estado_class = 'estado-' . $estado_final;
                                            $estado_text = [
                                                'hoy' => '🔥 HOY',
                                                'futura' => '⏳ PRÓXIMA',
                                                'pasada' => '✅ COMPLETADA',
                                                'cancelada' => '❌ CANCELADA'
                                            ][$estado_final] ?? '❓ DESCONOCIDO';
                                            ?>
                                            <span class="estado-badge <?= $estado_class ?>"><?= $estado_text ?></span>
                                        </td>
                                        <td>
                                            <?php if ($reserva['estado'] === 'activa' && $reserva['estado_calculado'] === 'futura'): ?>
                                                <form method="post" style="display: inline;" 
                                                      onsubmit="return confirm('¿Estás seguro de que quieres cancelar esta reserva?\n\nCódigo: <?= $reserva['codigo_reserva'] ?>\nFecha: <?= date('d/m/Y', strtotime($reserva['fecha'])) ?>\nHora: <?= substr($reserva['hora_inicio'], 0, 5) ?>\nEspacios: <?= $reserva['espacios_reservados'] ?>/4');">
                                                    <input type="hidden" name="id_reserva" value="<?= $reserva['id_reserva'] ?>">
                                                    <button type="submit" name="cancelar_reserva" class="btn btn-danger">
                                                        ❌ Cancelar
                                                    </button>
                                                </form>
                                            <?php elseif ($reserva['estado'] === 'activa' && ($reserva['estado_calculado'] === 'hoy' || $reserva['estado_calculado'] === 'pasada')): ?>
                                                <span style="color: #666; font-size: 12px;">No se puede cancelar</span>
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
                            <h3>🎾 No tienes reservas todavía</h3>
                            <p>¡Reserva tu primera cancha y comienza a jugar!</p>
                            <a href="calendario.php" class="btn">🔍 Ver Canchas Disponibles</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    


    <script>
        //-----------------------------------SCRIPT DE TAB-----------------------------
        function showTab(tabName) {
            // Ocultar todos los contenidos de tabs
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Desactivar todos los tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar el contenido seleccionado
            document.getElementById(tabName).classList.add('active');
            
            // Activar el tab clickeado
            event.target.classList.add('active');
        }
    </script>

</body>
</html>