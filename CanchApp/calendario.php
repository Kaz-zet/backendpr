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

// NUEVO: Procesar valoración si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_valoracion'])) {
    if (!$id_usuario) {
        $error = 'Debes iniciar sesión para valorar canchas';
    } elseif ($_SESSION['rol'] !== 'usuario') {
        $error = 'Solo los usuarios pueden valorar canchas';
    } else {
        $valor = (int)($_POST['valor'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');
        
        if ($valor >= 1 && $valor <= 5) {
            try {
                // Verificar si el usuario ya valoró esta cancha
                $stmt = $pdo->prepare("SELECT id_valoracion FROM valoracion WHERE id_usuario = ? AND id_cancha = ?");
                $stmt->execute([$id_usuario, $id_cancha]);
                $valoracion_existente = $stmt->fetch();
                
                if ($valoracion_existente) {
                    // Actualizar valoración existente
                    $stmt = $pdo->prepare("UPDATE valoracion SET valor = ?, comentario = ? WHERE id_usuario = ? AND id_cancha = ?");
                    $stmt->execute([$valor, $comentario, $id_usuario, $id_cancha]);
                    $msg = "Tu valoración ha sido actualizada correctamente";
                } else {
                    // Crear nueva valoración
                    $stmt = $pdo->prepare("INSERT INTO valoracion (valor, comentario, id_usuario, id_cancha) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$valor, $comentario, $id_usuario, $id_cancha]);
                    $msg = "¡Gracias por valorar esta cancha!";
                }
                
                // Actualizar el campo valoracion en la tabla cancha (promedio redondeado)
                $stmt = $pdo->prepare("
                    SELECT AVG(valor) as promedio
                    FROM valoracion 
                    WHERE id_cancha = ?
                ");
                $stmt->execute([$id_cancha]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                $promedio_redondeado = round($stats['promedio']);
                
                $stmt = $pdo->prepare("UPDATE cancha SET valoracion = ? WHERE id_cancha = ?");
                $stmt->execute([$promedio_redondeado, $id_cancha]);
                
            } catch (PDOException $e) {
                $error = "Error al procesar la valoración: " . $e->getMessage();
            }
        } else {
            $error = 'La valoración debe ser entre 1 y 5 estrellas';
        }
    }
}

//Saca el promedio de Valoraciones
function obtenerPromedioValoracion($pdo, $id_cancha) {
    $stmt = $pdo->prepare("
        SELECT 
            AVG(valor) as promedio,
            COUNT(*) as total_valoraciones
        FROM valoracion 
        WHERE id_cancha = ?
    ");
    $stmt->execute([$id_cancha]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'promedio' => $resultado['promedio'] ? round($resultado['promedio'], 1) : 0,
        'total' => $resultado['total_valoraciones'] ?? 0
    ];
}

//NUEVA FUNCIÓN: Obtiene todas las valoraciones con comentarios de una cancha
function obtenerValoracionesCompletas($pdo, $id_cancha) {
    $stmt = $pdo->prepare("
        SELECT 
            v.valor,
            v.comentario,
            u.nombre as usuario_nombre,
            v.id_valoracion
        FROM valoracion v
        INNER JOIN usuario u ON v.id_usuario = u.id_usuario
        WHERE v.id_cancha = ?
        ORDER BY v.valor DESC, v.id_valoracion DESC
    ");
    $stmt->execute([$id_cancha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Verifica si el usuario ya valoró la cancha y obtiene su valoración
function obtenerValoracionUsuario($pdo, $id_usuario, $id_cancha) {
    if (!$id_usuario) return false;
    
    $stmt = $pdo->prepare("SELECT valor, comentario FROM valoracion WHERE id_usuario = ? AND id_cancha = ?");
    $stmt->execute([$id_usuario, $id_cancha]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ?: false;
}

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

//Verifica estado del horario (disponible, parcialmente ocupado, completo, pasado)
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
        // Disponible
        return [
            'tipo' => 'disponible',
            'mensaje' => 'Disponible',
            'espacios_disponibles' => 4,
            'info_espacios' => '4 espacios libres'
        ];
    } elseif ($espacios_ocupados >= 4) {
        // Ocupado
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
        // Espacios disponibles
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

        /* ESTILOS MEJORADOS PARA VALORACIONES */
        .valoracion-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border: 2px solid #e9ecef;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .valoracion-promedio {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .promedio-numero {
            font-size: 3em;
            font-weight: bold;
            color: #fd7e14;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .estrellas-promedio {
            display: flex;
            gap: 8px;
        }

        .estrella {
            font-size: 28px;
            color: #ddd;
            transition: color 0.3s;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .estrella.activa {
            color: #ffd43b;
        }

        .estrella.media {
            color: #ffd43b;
            position: relative;
        }

        .total-valoraciones {
            color: #6c757d;
            font-size: 16px;
            font-weight: 500;
        }

        .valoracion-usuario {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .valoracion-usuario h4 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 18px;
        }

        .estrellas-seleccion {
            display: flex;
            gap: 8px;
            margin: 15px 0;
        }

        .radio-estrella {
            display: none;
        }

        .label-estrella {
            font-size: 36px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .label-estrella:hover {
            color: #ffd43b;
            transform: scale(1.1);
        }

        .radio-estrella:checked ~ .label-estrella,
        .radio-estrella:checked ~ .radio-estrella + .label-estrella {
            color: #ffd43b;
        }

        /* Estilo mejorado para las estrellas seleccionadas */
        .estrellas-container {
            display: flex;
            gap: 5px;
            margin: 15px 0;
        }

        .estrella-input {
            position: relative;
        }

        .estrella-input input[type="radio"] {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }

        .estrella-label {
            font-size: 36px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .estrella-input:hover .estrella-label {
            color: #ffd43b;
            transform: scale(1.1);
        }

        .estrella-input input[type="radio"]:checked + .estrella-label {
            color: #ffd43b;
        }

        /* Efecto de hover consecutivo */
        .estrellas-container:hover .estrella-input:hover ~ .estrella-input .estrella-label {
            color: #ddd;
        }

        .btn-valorar {
            background: linear-gradient(135deg, #fd7e14, #fab005);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(253, 126, 20, 0.3);
        }

        .btn-valorar:hover {
            background: linear-gradient(135deg, #e8590c, #fd7e14);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(253, 126, 20, 0.4);
        }

        .valoracion-existente {
            background: #e7f3ff;
            border: 2px solid #1976d2;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .valoracion-existente h4 {
            color: #1976d2;
            margin: 0 0 15px 0;
        }

        .comentario-section {
            margin-top: 20px;
        }

        .comentario-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .comentario-section textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 2px solid #ced4da;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .comentario-section textarea:focus {
            outline: none;
            border-color: #fd7e14;
            box-shadow: 0 0 0 3px rgba(253, 126, 20, 0.1);
        }

        .mensaje {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }

        .mensaje.success {
            background: #d1f2eb;
            color: #00695c;
            border: 2px solid #4caf50;
        }

        .mensaje.error {
            background: #ffeaea;
            color: #c62828;
            border: 2px solid #f44336;
        }

        /* NUEVOS ESTILOS PARA MOSTRAR COMENTARIOS */
        .comentarios-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-top: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .comentarios-lista {
            display: grid;
            gap: 15px;
            margin-top: 20px;
        }

        .comentario-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #fd7e14;
            transition: all 0.3s ease;
        }

        .comentario-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .comentario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .comentario-usuario {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .usuario-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #fd7e14, #fab005);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .usuario-nombre {
            font-weight: 600;
            color: #495057;
            font-size: 16px;
        }

        .comentario-estrellas {
            display: flex;
            gap: 2px;
        }

        .comentario-estrellas .estrella {
            font-size: 18px;
        }

        .comentario-texto {
            color: #495057;
            font-size: 15px;
            line-height: 1.6;
            margin-top: 10px;
            font-style: italic;
        }

        .sin-comentarios {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .sin-comentarios h4 {
            margin-bottom: 10px;
            color: #495057;
        }

        .mostrar-comentarios-btn {
            background: linear-gradient(135deg, #6f42c1, #563d7c);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .mostrar-comentarios-btn:hover {
            background: linear-gradient(135deg, #563d7c, #452a5c);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($msg)): ?>
            <div class="mensaje success"><?= htmlspecialchars($msg) ?></div>
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
                    <?php 
                    $reservas = obtenerreservas($pdo, $cancha['id_cancha'], $fecha_mostrar);
                    
                    // Si estamos mostrando múltiples canchas, obtener valoración promedio
                    if (!$id_cancha) {
                        $val_info = obtenerPromedioValoracion($pdo, $cancha['id_cancha']);
                    }
                    ?>
                    <div class="cancha-card" data-nombre="<?= strtolower(htmlspecialchars($cancha['nombre'])) ?>" data-lugar="<?= strtolower(htmlspecialchars($cancha['lugar'])) ?>">
                        <div class="cancha-header">
                            <h3><?= htmlspecialchars($cancha['nombre']) ?></h3>
                            <p style="margin: 5px 0; font-size: 14px;">📍 <?= htmlspecialchars($cancha['lugar']) ?></p>
                            
                            <!-- Mostrar valoración en vista de múltiples canchas -->
                            <?php if (!$id_cancha && isset($val_info)): ?>
                                <div style="display: flex; align-items: center; gap: 5px; margin-top: 5px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="estrella <?= $i <= $val_info['promedio'] ? 'activa' : '' ?>" style="font-size: 14px;">★</span>
                                    <?php endfor; ?>
                                    <span style="font-size: 12px; color: #666;">
                                        (<?= $val_info['promedio'] ?>/5 - <?= $val_info['total'] ?> valoraciones)
                                    </span>
                                </div>
                            <?php endif; ?>
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
        
        <!-- SECCIÓN DE VALORACIONES MEJORADA -->
        <?php 
        $valoracion_info = obtenerPromedioValoracion($pdo, $id_cancha);
        $valoracion_usuario = obtenerValoracionUsuario($pdo, $id_usuario, $id_cancha);
        $valoraciones_completas = obtenerValoracionesCompletas($pdo, $id_cancha);
        ?>
        
        <div class="valoracion-section">
            <h3>Valoraciones</h3>
            
            <!-- Promedio de valoraciones -->
            <div class="valoracion-promedio">
                <div class="promedio-numero">
                    <?= $valoracion_info['promedio'] > 0 ? $valoracion_info['promedio'] : '-' ?>
                </div>
                <div>
                    <div class="estrellas-promedio">
                        <?php 
                        $promedio = $valoracion_info['promedio'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($promedio >= $i) {
                                echo '<span class="estrella activa">★</span>';
                            } elseif ($promedio >= ($i - 0.5)) {
                                echo '<span class="estrella media">☆</span>';
                            } else {
                                echo '<span class="estrella">☆</span>';
                            }
                        }
                        ?>
                    </div>
                    <div class="total-valoraciones">
                        <?= $valoracion_info['total'] ?> valoración<?= $valoracion_info['total'] != 1 ? 'es' : '' ?>
                    </div>
                </div>
            </div>

            <!-- Formulario para valorar (solo usuarios logueados) -->
            <?php if ($id_usuario): ?>
                <div class="valoracion-usuario">
                    <?php if ($valoracion_usuario !== false): ?>
                        <div class="valoracion-existente">
                            <h4>Tu valoración actual:</h4>
                            <div class="estrellas-promedio">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="estrella <?= $i <= $valoracion_usuario['valor'] ? 'activa' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <p><small>Has valorado esta cancha con <?= $valoracion_usuario['valor'] ?> estrella<?= $valoracion_usuario['valor'] > 1 ? 's' : '' ?>. Puedes cambiar tu valoración abajo.</small></p>
                            <?php if (!empty($valoracion_usuario['comentario'])): ?>
                                <p><strong>Tu comentario:</strong> "<?= htmlspecialchars($valoracion_usuario['comentario']) ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <h4>¿Qué te pareció esta cancha?</h4>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="enviar_valoracion" value="1">
                        
                        <div class="estrellas-container">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="estrella-input">
                                    <input type="radio" name="valor" value="<?= $i ?>" id="estrella<?= $i ?>" 
                                           <?= ($valoracion_usuario && $valoracion_usuario['valor'] == $i) ? 'checked' : '' ?>
                                           required>
                                    <label class="estrella-label" for="estrella<?= $i ?>">★</label>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="comentario-section">
                            <label for="comentario">Comentario (opcional):</label>
                            <textarea name="comentario" id="comentario" rows="4" 
                                      placeholder="Comparte tu experiencia..."><?= $valoracion_usuario ? htmlspecialchars($valoracion_usuario['comentario']) : '' ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-valorar">
                            <?= $valoracion_usuario ? 'Actualizar valoración' : 'Enviar valoración' ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="valoracion-usuario">
                    <p style="text-align: center; font-size: 16px;">
                        <a href="login.php" style="color: #fd7e14; font-weight: bold;">Inicia sesión</a> para valorar esta cancha
                    </p>
                </div>
            <?php endif; ?>

            <!-- NUEVA SECCIÓN: Comentarios de usuarios -->
            <?php if (!empty($valoraciones_completas)): ?>
                <div class="comentarios-section">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h4>Comentarios de usuarios (<?= count($valoraciones_completas) ?>)</h4>
                        <button class="mostrar-comentarios-btn" onclick="toggleComentarios()">Ver comentarios</button>
                    </div>
                    
                    <div id="comentarios-lista" class="comentarios-lista" style="display: none;">
                        <?php foreach ($valoraciones_completas as $val): ?>
                            <div class="comentario-item">
                                <div class="comentario-header">
                                    <div class="comentario-usuario">
                                        <div class="usuario-avatar">
                                            <?= strtoupper(substr($val['usuario_nombre'], 0, 1)) ?>
                                        </div>
                                        <span class="usuario-nombre"><?= htmlspecialchars($val['usuario_nombre']) ?></span>
                                    </div>
                                    <div class="comentario-estrellas">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="estrella <?= $i <= $val['valor'] ? 'activa' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php if (!empty($val['comentario'])): ?>
                                    <div class="comentario-texto">
                                        "<?= htmlspecialchars($val['comentario']) ?>"
                                    </div>
                                <?php else: ?>
                                    <div class="comentario-texto" style="color: #9ca3af; font-style: italic;">
                                        Usuario no dejó comentario escrito
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($valoracion_info['total'] > 0): ?>
                <div class="comentarios-section">
                    <div class="sin-comentarios">
                        <h4>Sin comentarios escritos</h4>
                        <p>Los usuarios han valorado esta cancha pero no han dejado comentarios por escrito.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <p style="text-align: center;">
        <?php if ($id_cancha): ?>
            <a href="cancha.php">← Volver a todas las canchas</a>
        <?php else: ?>
            <a href="index.php">Volver al inicio</a>
        <?php endif; ?>
    </p>

    <script>
        // .addEventListener('DOMContentLoaded') para asegurar que el DOM esté cargado. DOM (Document Object Model) es el HTML que se puede usar con JavaScript.
        document.addEventListener('DOMContentLoaded', function() {
            // querySelector selecciona lo primero que coincida con el CSS que se indico.
            const estrellasContainer = document.querySelector('.estrellas-container');
            if (!estrellasContainer) return;
            // querySelectorAll selecciona cualquier CSS que coincida con lo que se le indico.
            const estrellas = estrellasContainer.querySelectorAll('.estrella-input');
            
            estrellas.forEach((estrella, index) => {
                estrella.addEventListener('mouseenter', function() {
                    // Resalta las estrellas de amarillo hasta la que se está pasando el mouse
                    for (let i = 0; i <= index; i++) {
                        estrellas[i].querySelector('.estrella-label').style.color = '#ffd43b';
                        estrellas[i].querySelector('.estrella-label').style.transform = 'scale(1.1)';
                    }
                    // pone las estrellas que estén después del mouse en gris
                    for (let i = index + 1; i < estrellas.length; i++) {
                        estrellas[i].querySelector('.estrella-label').style.color = '#ddd';
                        estrellas[i].querySelector('.estrella-label').style.transform = 'scale(1)';
                    }
                });
            });
            // .addEventListener('mouseleave') es para cuando el mouse sale del contenedor de las estrellas.
            estrellasContainer.addEventListener('mouseleave', function() {
                // Mantiene las estrellas amarillas según la cantidad que indico el usuario
                // input[type="radio"]:checked hace referencia a la estrella que el usuario selecciono.
                const seleccionada = estrellasContainer.querySelector('input[type="radio"]:checked');
                //parseInt convierte un string en un número entero. Si no hay ninguna seleccionada, valorSeleccionado será 0.
                const valorSeleccionado = seleccionada ? parseInt(seleccionada.value) : 0;
                
                estrellas.forEach((estrella, index) => {
                    const label = estrella.querySelector('.estrella-label');
                    if (index < valorSeleccionado) {
                        label.style.color = '#ffd43b';
                    } else {
                        label.style.color = '#ddd';
                    }
                    label.style.transform = 'scale(1)';
                });
            });
        });

        // Función para mostrar/ocultar comentarios
        function toggleComentarios() {
            const comentariosList = document.getElementById('comentarios-lista');
            const toggleBtn = document.querySelector('.mostrar-comentarios-btn');
            
            if (comentariosList.style.display === 'none' || comentariosList.style.display === '') {
                comentariosList.style.display = 'grid';
                toggleBtn.textContent = 'Ocultar comentarios';
            } else {
                comentariosList.style.display = 'none';
                toggleBtn.textContent = 'Ver comentarios';
            }
        }
    </script>
</body>
</html>