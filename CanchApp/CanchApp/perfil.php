<?php
session_start();
require_once 'conexiones/conDB.php';

// Solo usuarios pueden ver su perfil
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    die("Solo los usuarios pueden ver su perfil."); //Unicamente usuarios pueden ver el perfil.
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

//Actualizar perfil. Acá podemos actualizarlo como queramos.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena_nueva = $_POST['contrasena_nueva'] ?? ''; //Se crean contrananueva y actual para mas adelante determinar la foto de perdil.
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $foto_actual = $usuario['foto']; //Se mantiene la foto actual y cualquier cosa creamos la variable foto nueva que pasa  ser foto actual cuando foto actual se cambia.
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
                        //Esta se crea en caso de que no exista.
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
                        //Determina que foto usar-.
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

//--------------------------------------------------------------HISTORIAL DE RESERVAS--------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.id_reserva,
            r.fecha,
            r.hora_inicio,
            r.hora_final,
            c.nombre as cancha_nombre,
            c.lugar as cancha_lugar,
            CASE 
                WHEN r.fecha < CURDATE() THEN 'pasada'
                WHEN r.fecha = CURDATE() AND r.hora_final <= CURTIME() THEN 'pasada'
                WHEN r.fecha = CURDATE() THEN 'hoy'
                ELSE 'futura'
            END as estado
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
    $error_reservas = 'Error al cargar reservas: ' . $e->getMessage(); //Se buscan todas las reservas hechas usando el ID.
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
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            color: black;
            padding: 30px;
            text-align: center;
        }
        .profile-photo { /*No se toca!!*/ 
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 4px solid white;
            object-fit: cover;
            display: block;
        }
        .profile-photo-placeholder { /*No se toca!!*/ 
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
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 40px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #000000ff;
            outline: none;
        }
        .btn {
            background: #000000ff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #000000ff;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .mensaje {
            padding: 15px;
            border-radius: 6px;
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
        .reservas-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .reservas-table th,
        .reservas-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .reservas-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .reservas-table tr:hover {
            background-color: #f8f9fa;
        }
        .estado-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
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
            background: #f8d7da;
            color: #721c24;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            padding: 15px 25px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-right: 5px;
        }
        .tab.active {
            background: #000000ff;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            border-left: 4px solid #030303ff; /*Color de las cartas*/ 
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #000000ff; /*Color del número*/ 
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>

</head>
<body>
    <div class="container">
        <div class="header"> <!--Header con foto del usuario-->
            <?php if (!empty($usuario['foto'])): ?>
                <img src="uploads/usuarios/<?= htmlspecialchars($usuario['foto']) ?>" 
                     alt="Foto de perfil" class="profile-photo">
            <?php else: ?>
                <div class="profile-photo-placeholder"> <!--Si no tiene se pone una foto default.-->
                    👤
                </div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
            <p><?= htmlspecialchars($usuario['email']) ?></p>
        </div>
        
        <div class="content"> <!--MENSAJES DE ERROR O SUCCESS.-->
            <?php if (!empty($msg)): ?>
                <div class="mensaje success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            
            <!--TABSS!!----------------------------------------------------------------------------->
            <div class="tabs">
                <button class="tab active" onclick="showTab('perfil')">Mi Perfil</button>
                <button class="tab" onclick="showTab('reservas')">Historial</button>
            </div>
            
            <!--TAB PERFIL-->
            <div id="perfil" class="tab-content active">
                <div class="section">
                    <h2>Editar Perfil</h2>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Foto de Perfil:</label>
                            <input type="file" name="foto" accept="image/*">
                            <small style="color: #666;">Formatos: JPG, JPEG, PNG. Máximo 5MB.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Nombre:</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Nueva Contraseña (opcional):</label>
                            <input type="password" name="contrasena_nueva" placeholder="Dejar vacío para mantener la actual">
                            <small style="color: #666;">Mínimo 3 caracteres. Dejala vacía si no quieres cambiarla.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Contraseña Actual:</label>
                            <input type="password" name="contrasena_actual" required>
                            <small style="color: #666;">Ingresá tu contraseña actual para confirmar cualquier cambio!</small>
                        </div>
                        
                        <button type="submit" name="actualizar_perfil" class="btn">Actualizar Perfil</button>
                        <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
                    </form>
                </div>
            </div>






            
            <!-- Tab: Mis Reservas -->
            <div id="reservas" class="tab-content">
                <div class="section">
                    <h2>Mi Historial de Reservas</h2>
                    
                    <?php
                    // Calcular estadísticas
                    $total_reservas = count($reservas);
                    $reservas_hoy = count(array_filter($reservas, function($r) { return $r['estado'] === 'hoy'; }));
                    $reservas_futuras = count(array_filter($reservas, function($r) { return $r['estado'] === 'futura'; }));
                    $reservas_pasadas = count(array_filter($reservas, function($r) { return $r['estado'] === 'pasada'; }));
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $total_reservas ?></div>
                            <div class="stat-label">Total Reservas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $reservas_futuras ?></div>
                            <div class="stat-label">Próximas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $reservas_hoy ?></div>
                            <div class="stat-label">Hoy</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $reservas_pasadas ?></div>
                            <div class="stat-label">Completadas</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($reservas)): ?>
                        <table class="reservas-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Horario</th>
                                    <th>Cancha</th>
                                    <th>Ubicación</th>
                                    <th>Estado</th>
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
                                            <?php
                                            $estado_class = 'estado-' . $reserva['estado'];
                                            $estado_text = [
                                                'hoy' => 'HOY',
                                                'futura' => 'PRÓXIMA',
                                                'pasada' => 'COMPLETADA'
                                            ][$reserva['estado']] ?? 'DESCONOCIDO';
                                            ?>
                                            <span class="estado-badge <?= $estado_class ?>"><?= $estado_text ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <h3>No tienes reservas todavía</h3>
                            <p>¡Reserva tu rpimera cancha!!!</p>
                            <a href="cancha.php" class="btn">Ver Canchas Disponibles</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_reservas)): ?>
                        <div class="mensaje error"><?= htmlspecialchars($error_reservas) ?></div>
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