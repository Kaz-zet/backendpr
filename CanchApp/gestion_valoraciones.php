<?php
session_start();
require_once 'conexiones/conDB.php';

// Solo dueños pueden acceder
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'duenio') {
    header("Location: login.php");
    exit;
}

$id_duenio = $_SESSION['id'];
$msg = '';
$error = '';

// Cancelar reserva (desde el lado del dueño)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {
    $id_reserva = $_POST['id_reserva'] ?? '';
    
    if (!empty($id_reserva)) {
        try {
            // Verificar que la reserva pertenece a una cancha del dueño
            $stmt = $pdo->prepare("
                SELECT r.* FROM reserva r
                INNER JOIN cancha c ON r.id_cancha = c.id_cancha
                WHERE r.id_reserva = ? AND c.id_duenio = ? AND r.estado = 'activa'
            ");
            $stmt->execute([$id_reserva, $id_duenio]);
            $reserva = $stmt->fetch();
            
            if ($reserva) {
                // Cambiar estado a cancelada
                $stmt = $pdo->prepare("UPDATE reserva SET estado = 'cancelada' WHERE id_reserva = ?");
                $stmt->execute([$id_reserva]);
                
                $msg = "Reserva cancelada correctamente. Código: " . $reserva['codigo_reserva'];
            } else {
                $error = "No tienes permisos para cancelar esta reserva o ya está cancelada.";
            }
        } catch (PDOException $e) {
            $error = "Error al cancelar la reserva: " . $e->getMessage();
        }
    }
}

// Obtener canchas del dueño con sus valoraciones
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            COUNT(v.id_valoracion) as total_valoraciones,
            AVG(v.valor) as promedio_valoraciones,
            ROUND(AVG(v.valor), 1) as promedio_redondeado
        FROM cancha c
        LEFT JOIN valoracion v ON c.id_cancha = v.id_cancha
        WHERE c.id_duenio = ?
        GROUP BY c.id_cancha
        ORDER BY c.nombre
    ");
    $stmt->execute([$id_duenio]);
    $canchas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error al cargar las canchas: " . $e->getMessage();
    $canchas = [];
}

// Función para obtener valoraciones detalladas de una cancha específica
function obtenerValoracionesDetalladas($pdo, $id_cancha) {
    $stmt = $pdo->prepare("
        SELECT 
            v.*,
            u.nombre as usuario_nombre,
            u.email as usuario_email
        FROM valoracion v
        INNER JOIN usuario u ON v.id_usuario = u.id_usuario
        WHERE v.id_cancha = ?
        ORDER BY v.valor DESC, v.id_valoracion DESC
    ");
    $stmt->execute([$id_cancha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener reservas de una cancha específica
function obtenerReservasCancha($pdo, $id_cancha, $fecha_desde = null, $filtro_estado = 'activa') {
    $fecha_desde = $fecha_desde ?: date('Y-m-d');
    
    $sql = "
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
            u.nombre as usuario_nombre,
            u.email as usuario_email
        FROM reserva r
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        WHERE r.id_cancha = ? AND r.fecha >= ?
    ";
    
    $params = [$id_cancha, $fecha_desde];
    
    if ($filtro_estado !== 'todas') {
        $sql .= " AND r.estado = ?";
        $params[] = $filtro_estado;
    }
    
    $sql .= " ORDER BY r.fecha ASC, r.hora_inicio ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Si se solicita ver detalles de una cancha específica
$cancha_detalle = null;
$valoraciones_detalle = [];
$reservas_cancha = [];
$vista_actual = 'general'; // puede ser 'general', 'detalle', 'reservas'

if (isset($_GET['detalle']) && is_numeric($_GET['detalle'])) {
    $id_cancha_detalle = (int)$_GET['detalle'];
    $vista_actual = 'detalle';
    
    // Verificar que la cancha pertenezca al dueño
    $stmt = $pdo->prepare("SELECT * FROM cancha WHERE id_cancha = ? AND id_duenio = ?");
    $stmt->execute([$id_cancha_detalle, $id_duenio]);
    $cancha_detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cancha_detalle) {
        $valoraciones_detalle = obtenerValoracionesDetalladas($pdo, $id_cancha_detalle);
    }
} elseif (isset($_GET['reservas']) && is_numeric($_GET['reservas'])) {
    $id_cancha_detalle = (int)$_GET['reservas'];
    $vista_actual = 'reservas';
    
    // Verificar que la cancha pertenezca al dueño
    $stmt = $pdo->prepare("SELECT * FROM cancha WHERE id_cancha = ? AND id_duenio = ?");
    $stmt->execute([$id_cancha_detalle, $id_duenio]);
    $cancha_detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cancha_detalle) {
        $filtro_estado = $_GET['estado'] ?? 'activa';
        $fecha_desde = $_GET['desde'] ?? null;
        $reservas_cancha = obtenerReservasCancha($pdo, $id_cancha_detalle, $fecha_desde, $filtro_estado);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Valoraciones y Reservas - Mis Canchas</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f8f9fa;
        }

        .header h1 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .header p {
            color: #6c757d;
            font-size: 1.1em;
        }

        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #d39e00);
            transform: translateY(-2px);
        }

        /* Estilos para la vista general */
        .canchas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .cancha-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .cancha-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: #007bff;
        }

        .cancha-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .cancha-title {
            font-size: 1.4em;
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }

        .cancha-ubicacion {
            color: #6c757d;
            font-size: 0.95em;
        }

        .valoracion-resumen {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 5px solid #ffd43b;
        }

        .estrellas-grandes {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .promedio-numero {
            font-size: 2.5em;
            font-weight: bold;
            color: #fd7e14;
            margin-right: 15px;
        }

        .estrellas {
            display: flex;
            gap: 3px;
        }

        .estrella {
            font-size: 24px;
            color: #ddd;
        }

        .estrella.activa {
            color: #ffd43b;
        }

        .stats-text {
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
        }

        .distribucion-estrellas {
            margin-top: 15px;
        }

        .distribucion-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .star-label {
            width: 60px;
            color: #495057;
        }

        .bar-container {
            flex: 1;
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            margin: 0 10px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #ffd43b, #fd7e14);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .count-label {
            width: 40px;
            text-align: right;
            color: #6c757d;
            font-weight: 500;
        }

        /* Estilos para vista detallada */
        .detalle-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }

        .valoraciones-lista {
            display: grid;
            gap: 15px;
            margin-top: 25px;
        }

        .valoracion-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #fd7e14;
        }

        .valoracion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .valoracion-usuario {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .usuario-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .usuario-info {
            flex: 1;
        }

        .usuario-nombre {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .usuario-email {
            color: #6c757d;
            font-size: 14px;
        }

        .valoracion-estrellas {
            display: flex;
            gap: 3px;
        }

        .comentario-texto {
            color: #495057;
            font-size: 15px;
            line-height: 1.6;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #ffd43b;
            font-style: italic;
        }

        .sin-comentario {
            color: #9ca3af;
            font-style: italic;
            margin-top: 10px;
            font-size: 14px;
        }

        .sin-valoraciones {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .sin-valoraciones h3 {
            margin-bottom: 15px;
            color: #495057;
        }

        .estadisticas-generales {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            display: block;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
        }

        /* NUEVOS ESTILOS PARA COMENTARIOS EN VISTA GENERAL */
        .comentarios-preview {
            margin-top: 15px;
            max-height: 150px;
            overflow: hidden;
            position: relative;
        }

        .comentario-mini {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid #ffd43b;
            font-size: 13px;
        }

        .comentario-usuario-mini {
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }

        .comentario-texto-mini {
            color: #6c757d;
            font-style: italic;
            line-height: 1.4;
        }

        .ver-mas-comentarios {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, #f8f9fa);
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .btn-mini {
            background: #6c757d;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-mini:hover {
            background: #545b62;
        }

        /* ESTILOS PARA RESERVAS */
        .filtros {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .filtros h3 {
            margin: 0 0 15px 0;
            color: #495057;
        }
        
        .filtros-grid {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filtro-activo {
            background: #28a745 !important;
            color: white !important;
        }

        .reservas-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .reservas-table th {
            background: #495057;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .reservas-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .reservas-table tr:hover {
            background: #f8f9fa;
        }
        
        .codigo-reserva {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: bold;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
            display: inline-block;
        }
        
        .estado-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-activa {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
        }

        .espacios-info-table {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .cliente-info {
            color: #6c757d;
            font-size: 14px;
        }

        .no-reservas {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
            
            .canchas-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                justify-content: center;
            }

            .valoracion-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .filtros-grid {
                flex-direction: column;
                align-items: stretch;
            }
            
            .reservas-table {
                font-size: 14px;
            }
            
            .reservas-table th,
            .reservas-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Gestión de Valoraciones y Reservas</h1>
            <p>Administra las valoraciones y reservas de tus canchas</p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="mensaje success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="nav-buttons">
            <a href="index.php" class="btn btn-secondary">← Volver al Inicio</a>
            <a href="gestion_reservas.php" class="btn btn-secondary">← Volver a Gestión</a>
            <?php if ($cancha_detalle): ?>
                <a href="?#canchas" class="btn btn-primary">← Ver Todas las Canchas</a>
            <?php endif; ?>
        </div>

        <?php if ($vista_actual === 'general'): ?>
            <!-- Vista general: Listado de canchas con resumen de valoraciones -->
            
            <?php if (!empty($canchas)): ?>
                <?php
                // Calcular estadísticas generales
                $total_canchas = count($canchas);
                $total_valoraciones = array_sum(array_column($canchas, 'total_valoraciones'));
                ?>
                
                <div class="estadisticas-generales">
                    <h3>📈 Estadísticas Generales</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?= $total_canchas ?></span>
                            <div class="stat-label">Canchas Totales</div>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $total_valoraciones ?></span>
                            <div class="stat-label">Valoraciones Totales</div>
                        </div>
                    </div>
                </div>

                <div class="canchas-grid" id="canchas">
                    <?php foreach ($canchas as $cancha): ?>
                        <?php
                        // Calcular distribución de estrellas para esta cancha
                        if ($cancha['total_valoraciones'] > 0) {
                            $stmt = $pdo->prepare("
                                SELECT valor, COUNT(*) as cantidad 
                                FROM valoracion 
                                WHERE id_cancha = ? 
                                GROUP BY valor 
                                ORDER BY valor DESC
                            ");
                            $stmt->execute([$cancha['id_cancha']]);
                            $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Crear array con todas las valoraciones (1-5)
                            $dist_completa = [];
                            for ($i = 5; $i >= 1; $i--) {
                                $encontrado = false;
                                foreach ($distribucion as $d) {
                                    if ($d['valor'] == $i) {
                                        $dist_completa[$i] = $d['cantidad'];
                                        $encontrado = true;
                                        break;
                                    }
                                }
                                if (!$encontrado) {
                                    $dist_completa[$i] = 0;
                                }
                            }

                            // Obtener algunos comentarios para preview
                            $stmt_comentarios = $pdo->prepare("
                                SELECT v.comentario, u.nombre as usuario_nombre, v.valor
                                FROM valoracion v
                                INNER JOIN usuario u ON v.id_usuario = u.id_usuario
                                WHERE v.id_cancha = ? AND v.comentario IS NOT NULL AND v.comentario != ''
                                ORDER BY v.valor DESC
                                LIMIT 3
                            ");
                            $stmt_comentarios->execute([$cancha['id_cancha']]);
                            $comentarios_preview = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);
                        }

                        // Obtener estadísticas de reservas para esta cancha
                        $stmt_reservas = $pdo->prepare("
                            SELECT 
                                COUNT(*) as total_reservas,
                                COUNT(CASE WHEN estado = 'activa' THEN 1 END) as reservas_activas,
                                COUNT(CASE WHEN fecha = CURDATE() AND estado = 'activa' THEN 1 END) as reservas_hoy
                            FROM reserva 
                            WHERE id_cancha = ? AND fecha >= CURDATE()
                        ");
                        $stmt_reservas->execute([$cancha['id_cancha']]);
                        $stats_reservas = $stmt_reservas->fetch(PDO::FETCH_ASSOC);
                        ?>
                        
                        <div class="cancha-card">
                            <div class="cancha-header">
                                <div>
                                    <div class="cancha-title"><?= htmlspecialchars($cancha['nombre']) ?></div>
                                    <div class="cancha-ubicacion">📍 <?= htmlspecialchars($cancha['lugar']) ?></div>
                                </div>
                            </div>

                            <div class="valoracion-resumen">
                                <?php if ($cancha['total_valoraciones'] > 0): ?>
                                    <div class="estrellas-grandes">
                                        <div class="promedio-numero"><?= $cancha['promedio_redondeado'] ?></div>
                                        <div class="estrellas">
                                            <?php
                                            $promedio = $cancha['promedio_valoraciones'];
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($promedio >= $i) {
                                                    echo '<span class="estrella activa">★</span>';
                                                } elseif ($promedio >= ($i - 0.5)) {
                                                    echo '<span class="estrella activa">☆</span>';
                                                } else {
                                                    echo '<span class="estrella">☆</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stats-text">
                                        <?= $cancha['total_valoraciones'] ?> valoración<?= $cancha['total_valoraciones'] != 1 ? 'es' : '' ?>
                                        • Promedio: <?= number_format($cancha['promedio_valoraciones'], 2) ?>
                                    </div>

                                    <!-- Distribución de estrellas -->
                                    <div class="distribucion-estrellas">
                                        <?php foreach ($dist_completa as $estrella => $cantidad): ?>
                                            <div class="distribucion-item">
                                                <span class="star-label"><?= $estrella ?> ★</span>
                                                <div class="bar-container">
                                                    <?php $porcentaje = $cancha['total_valoraciones'] > 0 ? ($cantidad / $cancha['total_valoraciones']) * 100 : 0; ?>
                                                    <div class="bar-fill" style="width: <?= $porcentaje ?>%"></div>
                                                </div>
                                                <span class="count-label"><?= $cantidad ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Preview de comentarios -->
                                    <?php if (!empty($comentarios_preview)): ?>
                                        <div class="comentarios-preview">
                                            <h5 style="margin-bottom: 10px; color: #495057; font-size: 14px;">💬 Últimos comentarios:</h5>
                                            <?php foreach ($comentarios_preview as $comentario): ?>
                                                <div class="comentario-mini">
                                                    <div class="comentario-usuario-mini">
                                                        <?= htmlspecialchars($comentario['usuario_nombre']) ?>
                                                        <?php for ($i = 1; $i <= $comentario['valor']; $i++): ?>★<?php endfor; ?>
                                                    </div>
                                                    <div class="comentario-texto-mini">
                                                        "<?= htmlspecialchars(substr($comentario['comentario'], 0, 80)) ?><?= strlen($comentario['comentario']) > 80 ? '...' : '' ?>"
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($comentarios_preview) >= 3): ?>
                                                <div class="ver-mas-comentarios">
                                                    <button class="btn-mini" onclick="window.location.href='?detalle=<?= $cancha['id_cancha'] ?>'">Ver todos</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="text-align: center; color: #6c757d; padding: 20px;">
                                        <h4 style="margin-bottom: 10px;">Sin valoraciones aún</h4>
                                        <p>Esta cancha aún no ha sido valorada por ningún usuario.</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Nueva sección: Estadísticas de reservas -->
                                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-top: 15px;">
                                    <h5 style="color: #1976d2; margin-bottom: 10px; font-size: 14px;">📅 Reservas:</h5>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                                        <div>
                                            <div style="font-size: 18px; font-weight: bold; color: #1976d2;"><?= $stats_reservas['reservas_hoy'] ?></div>
                                            <div style="font-size: 11px; color: #666;">Hoy</div>
                                        </div>
                                        <div>
                                            <div style="font-size: 18px; font-weight: bold; color: #28a745;"><?= $stats_reservas['reservas_activas'] ?></div>
                                            <div style="font-size: 11px; color: #666;">Activas</div>
                                        </div>
                                        <div>
                                            <div style="font-size: 18px; font-weight: bold; color: #6c757d;"><?= $stats_reservas['total_reservas'] ?></div>
                                            <div style="font-size: 11px; color: #666;">Total</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <?php if ($cancha['total_valoraciones'] > 0): ?>
                                    <a href="?detalle=<?= $cancha['id_cancha'] ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                        📊 Ver Valoraciones
                                    </a>
                                <?php endif; ?>
                                <a href="?reservas=<?= $cancha['id_cancha'] ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                                    📅 Ver Reservas
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sin-valoraciones">
                    <h3>No tienes canchas registradas</h3>
                    <p>Crea tu primera cancha para comenzar a recibir valoraciones y reservas.</p>
                    <a href="dueño.php" class="btn btn-success" style="margin-top: 20px;">Crear Primera Cancha</a>
                </div>
            <?php endif; ?>

        <?php elseif ($vista_actual === 'detalle'): ?>
            <!-- Vista detallada: Valoraciones específicas de una cancha -->
            
            <div class="detalle-container">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                    <div>
                        <h2><?= htmlspecialchars($cancha_detalle['nombre']) ?></h2>
                        <p style="color: #6c757d; margin-top: 5px;">📍 <?= htmlspecialchars($cancha_detalle['lugar']) ?></p>
                    </div>
                </div>

                <?php if (!empty($valoraciones_detalle)): ?>
                    <?php
                    // Calcular estadísticas detalladas
                    $total_val = count($valoraciones_detalle);
                    $promedio_val = array_sum(array_column($valoraciones_detalle, 'valor')) / $total_val;
                    
                    // Distribución de estrellas
                    $distribucion_det = [];
                    for ($i = 1; $i <= 5; $i++) {
                        $distribucion_det[$i] = 0;
                    }
                    foreach ($valoraciones_detalle as $val) {
                        $distribucion_det[$val['valor']]++;
                    }

                    // Contar valoraciones con comentarios
                    $valoraciones_con_comentarios = count(array_filter($valoraciones_detalle, function($v) {
                        return !empty(trim($v['comentario']));
                    }));
                    ?>
                    
                    <div class="valoracion-resumen" style="margin-bottom: 30px;">
                        <div class="estrellas-grandes">
                            <div class="promedio-numero"><?= number_format($promedio_val, 1) ?></div>
                            <div>
                                <div class="estrellas">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="estrella <?= $i <= round($promedio_val) ? 'activa' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <div class="stats-text" style="margin-top: 10px;">
                                    Basado en <?= $total_val ?> valoración<?= $total_val != 1 ? 'es' : '' ?>
                                    • <?= $valoraciones_con_comentarios ?> con comentarios
                                </div>
                            </div>
                        </div>

                        <!-- Distribución detallada -->
                        <div class="distribucion-estrellas">
                            <h4 style="margin-bottom: 15px; color: #495057;">Distribución de Valoraciones</h4>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="distribucion-item">
                                    <span class="star-label"><?= $i ?> ★</span>
                                    <div class="bar-container">
                                        <?php $porcentaje = ($distribucion_det[$i] / $total_val) * 100; ?>
                                        <div class="bar-fill" style="width: <?= $porcentaje ?>%"></div>
                                    </div>
                                    <span class="count-label"><?= $distribucion_det[$i] ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <h3 style="margin-bottom: 20px; color: #495057;">💬 Todas las Valoraciones (<?= $total_val ?>)</h3>
                    
                    <div class="valoraciones-lista">
                        <?php foreach ($valoraciones_detalle as $valoracion): ?>
                            <div class="valoracion-item">
                                <div class="valoracion-header">
                                    <div class="valoracion-usuario">
                                        <div class="usuario-avatar">
                                            <?= strtoupper(substr($valoracion['usuario_nombre'], 0, 1)) ?>
                                        </div>
                                        <div class="usuario-info">
                                            <div class="usuario-nombre"><?= htmlspecialchars($valoracion['usuario_nombre']) ?></div>
                                            <div class="usuario-email"><?= htmlspecialchars($valoracion['usuario_email']) ?></div>
                                        </div>
                                    </div>
                                    <div class="valoracion-estrellas">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="estrella <?= $i <= $valoracion['valor'] ? 'activa' : '' ?>" style="font-size: 20px;">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <!-- Mostrar comentario si existe -->
                                <?php if (!empty(trim($valoracion['comentario']))): ?>
                                    <div class="comentario-texto">
                                        "<?= htmlspecialchars($valoracion['comentario']) ?>"
                                    </div>
                                <?php else: ?>
                                    <div class="sin-comentario">
                                        El usuario no dejó comentario escrito
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="sin-valoraciones">
                        <h3>Esta cancha aún no tiene valoraciones</h3>
                        <p>Cuando los usuarios comiencen a valorar tu cancha, aparecerán aquí.</p>
                        <div style="margin-top: 20px;">
                            <a href="calendario.php?id=<?= $cancha_detalle['id_cancha'] ?>" class="btn btn-primary">Ver Calendario de la Cancha</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($vista_actual === 'reservas'): ?>
            <!-- Vista de reservas: Reservas específicas de una cancha -->
            
            <div class="detalle-container">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                    <div>
                        <h2>📅 Reservas de <?= htmlspecialchars($cancha_detalle['nombre']) ?></h2>
                        <p style="color: #6c757d; margin-top: 5px;">📍 <?= htmlspecialchars($cancha_detalle['lugar']) ?></p>
                    </div>
                </div>

                <?php
                // Calcular estadísticas específicas de esta cancha
                $stmt_stats_cancha = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_reservas,
                        COUNT(CASE WHEN fecha = CURDATE() AND estado = 'activa' THEN 1 END) as reservas_hoy,
                        COUNT(CASE WHEN estado = 'activa' THEN 1 END) as reservas_activas,
                        COUNT(CASE WHEN estado = 'cancelada' THEN 1 END) as reservas_canceladas
                    FROM reserva 
                    WHERE id_cancha = ?
                ");
                $stmt_stats_cancha->execute([$cancha_detalle['id_cancha']]);
                $stats_cancha = $stmt_stats_cancha->fetch(PDO::FETCH_ASSOC);
                ?>

                <!-- Estadísticas específicas de la cancha -->
                <div class="estadisticas-generales" style="margin-bottom: 30px;">
                    <h3>📊 Estadísticas de esta Cancha</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?= $stats_cancha['reservas_hoy'] ?></span>
                            <div class="stat-label">Reservas Hoy</div>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $stats_cancha['reservas_activas'] ?></span>
                            <div class="stat-label">Reservas Activas</div>
                        </div>
                    </div>
                </div>

                <!-- Filtros para reservas -->
                <div class="filtros">
                    <h3>Filtros de búsqueda</h3>
                    <div class="filtros-grid">
                        <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="reservas" value="<?= $cancha_detalle['id_cancha'] ?>">
                            <label>Desde fecha:</label>
                            <input type="date" name="desde" value="<?= $_GET['desde'] ?? date('Y-m-d') ?>">
                            
                            <label>Estado:</label>
                            <a href="?reservas=<?= $cancha_detalle['id_cancha'] ?>&desde=<?= $_GET['desde'] ?? date('Y-m-d') ?>&estado=activa" 
                               class="btn <?= ($filtro_estado === 'activa') ? 'filtro-activo' : '' ?>">Activas</a>
                            <a href="?reservas=<?= $cancha_detalle['id_cancha'] ?>&desde=<?= $_GET['desde'] ?? date('Y-m-d') ?>&estado=cancelada" 
                               class="btn <?= ($filtro_estado === 'cancelada') ? 'filtro-activo' : '' ?>">Canceladas</a>
                            <a href="?reservas=<?= $cancha_detalle['id_cancha'] ?>&desde=<?= $_GET['desde'] ?? date('Y-m-d') ?>&estado=todas" 
                               class="btn <?= ($filtro_estado === 'todas') ? 'filtro-activo' : '' ?>">Todas</a>
                            
                            <button type="submit" class="btn">Aplicar fecha</button>
                            <a href="?reservas=<?= $cancha_detalle['id_cancha'] ?>" class="btn btn-secondary">Limpiar</a>
                        </form>
                    </div>
                </div>

                <h3>Reservas de esta cancha</h3>
                
                <?php if (!empty($reservas_cancha)): ?>
                    <table class="reservas-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Fecha y Hora</th>
                                <th>Cliente</th>
                                <th>Espacios</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas_cancha as $reserva): ?>
                                <?php
                                $fecha_reserva = $reserva['fecha'];
                                $fecha_actual = date('Y-m-d');
                                ?>
                                <tr>
                                    <td>
                                        <div class="codigo-reserva">
                                            <?= htmlspecialchars($reserva['codigo_reserva']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($reserva['fecha'])) ?></strong><br>
                                        <span style="color: #666; font-size: 12px;">
                                            <?php
                                            $dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado'];
                                            echo $dias[date('l', strtotime($reserva['fecha']))];
                                            ?>
                                        </span><br>
                                        <strong><?= substr($reserva['hora_inicio'], 0, 5) ?> - <?= substr($reserva['hora_final'], 0, 5) ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($reserva['usuario_nombre']) ?></strong><br>
                                        <div class="cliente-info"><?= htmlspecialchars($reserva['usuario_email']) ?></div>
                                        <?php if ($reserva['telefono']): ?>
                                            <div class="cliente-info">📞 <?= htmlspecialchars($reserva['telefono']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($reserva['observaciones']): ?>
                                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                                <strong>Obs:</strong> <?= htmlspecialchars($reserva['observaciones']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="espacios-info-table">
                                            🎾 <?= $reserva['espacios_reservados'] ?>/4 espacios
                                        </div>
                                    </td>
                                    <td>
                                        <span class="estado-badge estado-<?= $reserva['estado'] ?>">
                                            <?= strtoupper($reserva['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($reserva['estado'] === 'activa' && $fecha_reserva >= $fecha_actual): ?>
                                            <form method="post" style="display:inline;" 
                                                  onsubmit="return confirm('¿Seguro que quieres cancelar esta reserva?\n\nCódigo: <?= $reserva['codigo_reserva'] ?>\nCliente: <?= htmlspecialchars($reserva['usuario_nombre']) ?>\nEspacios: <?= $reserva['espacios_reservados'] ?>/4');">
                                                <input type="hidden" name="id_reserva" value="<?= $reserva['id_reserva'] ?>">
                                                <input type="hidden" name="redirect_to" value="?reservas=<?= $cancha_detalle['id_cancha'] ?>">
                                                <button type="submit" name="cancelar_reserva" class="btn btn-danger">
                                                    ❌ Cancelar
                                                </button>
                                            </form>
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
                        <h3>No hay reservas</h3>
                        <p>Esta cancha no tiene reservas <?= $filtro_estado !== 'todas' ? $filtro_estado . 's' : '' ?> 
                        <?= isset($_GET['desde']) ? 'desde la fecha seleccionada' : 'próximas' ?>.</p>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 30px; text-align: center;">
                    <a href="calendario.php?id=<?= $cancha_detalle['id_cancha'] ?>" class="btn btn-primary">Ver Calendario de esta Cancha</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>