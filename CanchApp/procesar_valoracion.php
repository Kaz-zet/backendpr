<?php
session_start();
require_once 'conexiones/conDB.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Debes iniciar sesión para valorar canchas'
    ]);
    exit;
}

// Verificar que sea un usuario normal (no admin o dueño)
if ($_SESSION['rol'] !== 'usuario') {
    echo json_encode([
        'success' => false,
        'message' => 'Solo los usuarios pueden valorar canchas'
    ]);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener y validar datos
$id_usuario = (int)$_SESSION['id'];
$id_cancha = (int)($_POST['id_cancha'] ?? 0);
$valor = (int)($_POST['valor'] ?? 0);

// Validaciones
if ($id_cancha <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de cancha inválido'
    ]);
    exit;
}

if ($valor < 1 || $valor > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'La valoración debe ser entre 1 y 5 estrellas'
    ]);
    exit;
}

try {
    // Verificar que la cancha existe
    $stmt = $pdo->prepare("SELECT id_cancha FROM cancha WHERE id_cancha = ?");
    $stmt->execute([$id_cancha]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'La cancha no existe'
        ]);
        exit;
    }
    
    // Verificar si el usuario ya valoró esta cancha
    $stmt = $pdo->prepare("SELECT id_valoracion FROM valoracion WHERE id_usuario = ? AND id_cancha = ?");
    $stmt->execute([$id_usuario, $id_cancha]);
    $valoracion_existente = $stmt->fetch();
    
    if ($valoracion_existente) {
        // Actualizar valoración existente
        $stmt = $pdo->prepare("UPDATE valoracion SET valor = ? WHERE id_usuario = ? AND id_cancha = ?");
        $stmt->execute([$valor, $id_usuario, $id_cancha]);
        
        $mensaje = "Tu valoración ha sido actualizada correctamente";
    } else {
        // Crear nueva valoración
        $stmt = $pdo->prepare("INSERT INTO valoracion (valor, id_usuario, id_cancha) VALUES (?, ?, ?)");
        $stmt->execute([$valor, $id_usuario, $id_cancha]);
        
        $mensaje = "¡Gracias por valorar esta cancha!";
    }
    
    // Calcular nuevo promedio
    $stmt = $pdo->prepare("
        SELECT 
            AVG(valor) as promedio,
            COUNT(*) as total
        FROM valoracion 
        WHERE id_cancha = ?
    ");
    $stmt->execute([$id_cancha]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar el campo valoracion en la tabla cancha (promedio redondeado)
    $promedio_redondeado = round($stats['promedio']);
    $stmt = $pdo->prepare("UPDATE cancha SET valoracion = ? WHERE id_cancha = ?");
    $stmt->execute([$promedio_redondeado, $id_cancha]);
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'nuevo_promedio' => round($stats['promedio'], 1),
        'total_valoraciones' => $stats['total']
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la valoración: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
}
?>